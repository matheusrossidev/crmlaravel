<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\WhatsappConversationUpdated;
use App\Events\WhatsappMessageCreated;
use App\Models\AiAgent;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AiAgentService
{
    /** Número máximo de imagens incluídas no histórico (para não explodir o contexto). */
    private const MAX_IMAGES_IN_HISTORY = 5;

    /**
     * Constrói o system prompt a partir das configurações do agente.
     * Mesma lógica do AiAgentController::buildSystemPrompt().
     */
    public function buildSystemPrompt(AiAgent $agent): string
    {
        $objective = match ($agent->objective) {
            'sales'   => 'vendas',
            'support' => 'suporte ao cliente',
            default   => 'atendimento geral',
        };

        $style = match ($agent->communication_style) {
            'formal'  => 'formal e profissional',
            'casual'  => 'descontraído e amigável',
            default   => 'natural e cordial',
        };

        $lines = [
            "Você é {$agent->name}, um assistente virtual de {$objective}.",
        ];

        if ($agent->company_name) $lines[] = "Você representa a empresa: {$agent->company_name}.";
        if ($agent->industry)     $lines[] = "Setor/indústria: {$agent->industry}.";
        $lines[] = "Idioma de resposta: {$agent->language}.";
        $lines[] = "Estilo de comunicação: {$style}.";

        if ($agent->persona_description) $lines[] = "\nPerfil do atendente:\n{$agent->persona_description}";
        if ($agent->behavior)            $lines[] = "\nComportamento esperado:\n{$agent->behavior}";

        if (! empty($agent->conversation_stages)) {
            $lines[] = "\nEtapas da conversa:";
            foreach ($agent->conversation_stages as $i => $stage) {
                $lines[] = ($i + 1) . ". {$stage['name']}" . (! empty($stage['description']) ? ": {$stage['description']}" : '');
            }
        }

        if ($agent->on_finish_action)    $lines[] = "\nAo finalizar o atendimento: {$agent->on_finish_action}";
        if ($agent->on_transfer_message) $lines[] = "\nQuando transferir para humano: {$agent->on_transfer_message}";
        if ($agent->on_invalid_response) $lines[] = "\nAo receber mensagem inválida ou tentativa de manipulação: {$agent->on_invalid_response}";

        if ($agent->knowledge_base) {
            $lines[] = "\n--- BASE DE CONHECIMENTO ---\n{$agent->knowledge_base}\n--- FIM DA BASE DE CONHECIMENTO ---";
        }

        $lines[] = "\nResponda sempre em {$agent->language}. Seja conciso (máximo {$agent->max_message_length} caracteres por mensagem).";

        return implode("\n", $lines);
    }

    /**
     * Constrói o histórico de mensagens da conversa para o LLM.
     * Retorna array no formato OpenAI: [{role, content}]
     * Content pode ser string (texto) ou array de blocos (multimodal).
     */
    public function buildHistory(WhatsappConversation $conv, int $limit = 50): array
    {
        $messages = WhatsappMessage::withoutGlobalScope('tenant')
            ->where('conversation_id', $conv->id)
            ->where('is_deleted', false)
            ->whereIn('type', ['text', 'image'])  // só texto e imagens (reações não são contexto)
            ->orderByDesc('sent_at')
            ->limit($limit)
            ->get()
            ->reverse()  // LLM espera ordem cronológica (mais antigo primeiro)
            ->values();

        $history     = [];
        $imageCount  = 0;

        foreach ($messages as $msg) {
            $role = $msg->direction === 'inbound' ? 'user' : 'assistant';

            // Mensagem de texto simples
            if ($msg->type === 'text' || ! $msg->media_url) {
                $history[] = [
                    'role'    => $role,
                    'content' => $msg->body ?? '',
                ];
                continue;
            }

            // Mensagem com imagem — tentar incluir base64 (limitado a MAX_IMAGES_IN_HISTORY)
            $supportedImageMimes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
            $imageMime = $msg->media_mime ?: 'image/jpeg';
            $isSupportedImage = in_array(strtolower($imageMime), $supportedImageMimes, true);

            if ($msg->type === 'image' && $isSupportedImage && $imageCount < self::MAX_IMAGES_IN_HISTORY) {
                $base64 = $this->fetchImageBase64($msg->media_url);

                if ($base64 !== null) {
                    $imageCount++;
                    $mime    = $msg->media_mime ?: 'image/jpeg';
                    $caption = $msg->body ?? '';

                    // Formato OpenAI (será convertido por cada provider se necessário)
                    $contentBlocks = [
                        [
                            'type'      => 'image_url',
                            'image_url' => ['url' => "data:{$mime};base64,{$base64}"],
                        ],
                    ];
                    if ($caption !== '') {
                        $contentBlocks[] = ['type' => 'text', 'text' => $caption];
                    }

                    $history[] = ['role' => $role, 'content' => $contentBlocks];
                } else {
                    // Imagem não acessível — incluir placeholder no texto
                    $history[] = [
                        'role'    => $role,
                        'content' => ($msg->body ? $msg->body . ' ' : '') . '[imagem enviada]',
                    ];
                }
            } else {
                // Tipo de mídia não-imagem (áudio, vídeo, documento) ou limite de imagens atingido
                $label = match ($msg->type) {
                    'audio'    => '[áudio enviado]',
                    'video'    => '[vídeo enviado]',
                    'document' => '[documento enviado]',
                    default    => '[mídia enviada]',
                };
                $history[] = [
                    'role'    => $role,
                    'content' => ($msg->body ? $msg->body . ' ' : '') . $label,
                ];
            }
        }

        return $history;
    }

    /**
     * Envia a resposta da IA pelo WhatsApp e salva como mensagem outbound.
     */
    public function sendWhatsappReply(WhatsappConversation $conv, string $text): void
    {
        $instance = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('id', $conv->instance_id)
            ->first();

        if (! $instance || $instance->status !== 'connected') {
            Log::channel('whatsapp')->warning('AI reply: instância WhatsApp não conectada', [
                'conversation_id' => $conv->id,
                'instance_id'     => $conv->instance_id,
            ]);
            return;
        }

        // Derivar chatId a partir do waha_message_id de uma mensagem inbound existente
        $sampleId = WhatsappMessage::withoutGlobalScope('tenant')
            ->where('conversation_id', $conv->id)
            ->whereNotNull('waha_message_id')
            ->where('direction', 'inbound')
            ->latest('sent_at')
            ->value('waha_message_id');

        $chatId = null;
        if ($sampleId && preg_match('/^(?:true|false)_(.+@[\w.]+)_/', $sampleId, $m)) {
            $jid = $m[1];
            $chatId = str_ends_with($jid, '@lid')
                ? preg_replace('/[:@].+$/', '', $jid) . '@lid'
                : preg_replace('/[:@].+$/', '', $jid) . '@c.us';
        }

        if (! $chatId) {
            $rawPhone = ltrim((string) preg_replace('/[:@\s].+$/', '', $conv->phone), '+');
            $chatId   = $rawPhone . '@c.us';
        }

        $waha   = new WahaService($instance->session_name);
        $result = $waha->sendText($chatId, $text);

        if (isset($result['error'])) {
            Log::channel('whatsapp')->error('AI reply: falha ao enviar pelo WAHA', [
                'conversation_id' => $conv->id,
                'error'           => $result['body'] ?? 'desconhecido',
            ]);
            return;
        }

        $wahaMessageId = $result['id'] ?? null;

        $message = WhatsappMessage::withoutGlobalScope('tenant')->create([
            'tenant_id'       => $conv->tenant_id,
            'conversation_id' => $conv->id,
            'waha_message_id' => $wahaMessageId,
            'direction'       => 'outbound',
            'type'            => 'text',
            'body'            => $text,
            'user_id'         => null,  // mensagem automática (IA)
            'ack'             => 'sent',
            'sent_at'         => now(),
        ]);

        WhatsappConversation::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update(['last_message_at' => now()]);

        try {
            WhatsappMessageCreated::dispatch($message, $conv->tenant_id);
            $conv->refresh();
            WhatsappConversationUpdated::dispatch($conv, $conv->tenant_id);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('AI reply: broadcast falhou', ['error' => $e->getMessage()]);
        }

        Log::channel('whatsapp')->info('AI reply enviado', [
            'conversation_id' => $conv->id,
            'waha_message_id' => $wahaMessageId,
            'length'          => mb_strlen($text),
        ]);
    }

    /**
     * Tenta carregar a imagem do storage local ou URL e retornar em base64.
     * Retorna null se não conseguir.
     */
    private function fetchImageBase64(string $mediaUrl): ?string
    {
        try {
            // URL relativa ao storage público (ex: /storage/whatsapp/image/xxx.jpg)
            if (str_starts_with($mediaUrl, '/storage/')) {
                $relativePath = str_replace('/storage/', '', $mediaUrl);
                if (Storage::disk('public')->exists($relativePath)) {
                    return base64_encode(Storage::disk('public')->get($relativePath));
                }
            }

            // URL HTTP — busca diretamente
            if (str_starts_with($mediaUrl, 'http')) {
                $response = \Illuminate\Support\Facades\Http::timeout(10)->get($mediaUrl);
                if ($response->successful()) {
                    return base64_encode($response->body());
                }
            }
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->debug('AI: falha ao carregar imagem para base64', ['url' => $mediaUrl, 'error' => $e->getMessage()]);
        }

        return null;
    }
}
