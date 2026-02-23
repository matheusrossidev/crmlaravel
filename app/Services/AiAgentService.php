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
    /**
     * Constrói o system prompt a partir das configurações do agente.
     *
     * @param array $stages     Ex: [['id'=>1,'name'=>'Novo Lead','current'=>true], ...]
     * @param array $availTags  Ex: ['interessado','vip','retorno']
     */
    public function buildSystemPrompt(
        AiAgent $agent,
        array   $stages    = [],
        array   $availTags = [],
    ): string {
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

        // ── Contexto de pipeline (se disponível) ──────────────────────────────
        if (! empty($stages)) {
            $currentStage = collect($stages)->firstWhere('current', true);
            $stageList    = collect($stages)->map(fn ($s) => "{$s['id']}: {$s['name']}")->implode(', ');
            $lines[] = "\n--- CONTROLE DE FUNIL ---";
            $lines[] = "Etapas disponíveis ({$stageList})";
            if ($currentStage) {
                $lines[] = "Etapa atual do lead: {$currentStage['name']}";
            }
            $lines[] = "Você pode mover o lead de etapa conforme a conversa evoluir.";
            $lines[] = "--- FIM DO CONTROLE DE FUNIL ---";
        }

        // ── Contexto de tags (se disponível) ──────────────────────────────────
        if (! empty($availTags)) {
            $tagList = implode(', ', $availTags);
            $lines[] = "\n--- TAGS DISPONÍVEIS ---";
            $lines[] = "Tags existentes: {$tagList}";
            $lines[] = "Você pode adicionar tags ao lead conforme o contexto da conversa.";
            $lines[] = "--- FIM DAS TAGS ---";
        }

        $lines[] = "\nResponda sempre em {$agent->language}. Seja conciso (máximo {$agent->max_message_length} caracteres por mensagem).";

        // ── Formato JSON obrigatório quando há pipeline ou tags ───────────────
        if (! empty($stages) || ! empty($availTags)) {
            $lines[] = <<<'JSONINSTR'

FORMATO DE RESPOSTA OBRIGATÓRIO — responda APENAS com JSON válido, sem markdown:
{
  "reply": "sua resposta ao cliente aqui",
  "actions": [
    {"type": "set_stage", "stage_id": <id_numérico>},
    {"type": "add_tags", "tags": ["tag1", "tag2"]}
  ]
}
Se não precisar de ações, use "actions": [].
NUNCA inclua texto fora do JSON.
JSONINSTR;
        }

        return implode("\n", $lines);
    }

    /**
     * Constrói o histórico de mensagens da conversa para o LLM.
     * Retorna array no formato OpenAI: [{role, content}]
     */
    public function buildHistory(WhatsappConversation $conv, int $limit = 50): array
    {
        $messages = WhatsappMessage::withoutGlobalScope('tenant')
            ->where('conversation_id', $conv->id)
            ->where('is_deleted', false)
            ->whereIn('type', ['text', 'image'])
            ->orderByDesc('sent_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        $history = [];

        foreach ($messages as $msg) {
            $role = $msg->direction === 'inbound' ? 'user' : 'assistant';

            if ($msg->type === 'text' || ! $msg->media_url) {
                $history[] = [
                    'role'    => $role,
                    'content' => $msg->body ?? '',
                ];
                continue;
            }

            $label = match ($msg->type) {
                'image'    => '[imagem enviada]',
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

        return $history;
    }

    /**
     * Divide uma resposta longa em múltiplas mensagens por parágrafos/sentenças.
     */
    public function splitIntoMessages(string $text, int $maxLength): array
    {
        // Dividir por parágrafos (quebra dupla de linha)
        $parts = preg_split('/\n{2,}/', $text);
        $parts = array_values(array_filter(array_map('trim', $parts)));

        if (count($parts) <= 1) {
            return $parts ?: [$text];
        }

        // Se algum parágrafo for longo demais, dividir por sentença
        $messages = [];
        foreach ($parts as $part) {
            if (mb_strlen($part) <= $maxLength) {
                $messages[] = $part;
            } else {
                $sentences = preg_split('/(?<=[.!?])\s+/', $part, -1, PREG_SPLIT_NO_EMPTY);
                $current   = '';
                foreach ($sentences as $sentence) {
                    $candidate = $current ? $current . ' ' . $sentence : $sentence;
                    if (mb_strlen($candidate) > $maxLength && $current !== '') {
                        $messages[] = trim($current);
                        $current    = $sentence;
                    } else {
                        $current = $candidate;
                    }
                }
                if ($current !== '') {
                    $messages[] = trim($current);
                }
            }
        }

        return array_values(array_filter($messages));
    }

    /**
     * Envia múltiplas partes de resposta com delay entre elas.
     */
    public function sendWhatsappReplies(WhatsappConversation $conv, array $messages, int $delaySeconds = 2): void
    {
        foreach ($messages as $i => $text) {
            if ($i > 0 && $delaySeconds > 0) {
                sleep($delaySeconds);
            }
            $this->sendWhatsappReply($conv, $text);
        }
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
            'user_id'         => null,
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
}
