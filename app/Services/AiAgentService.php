<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\WhatsappConversationUpdated;
use App\Events\WhatsappMessageCreated;
use App\Models\AiAgent;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Http;
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

        // Data/hora atual no fuso do servidor — essencial para saudações corretas
        $now     = \Carbon\Carbon::now(config('app.timezone', 'America/Sao_Paulo'));
        $weekdays = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
        $dayName  = $weekdays[$now->dayOfWeek];
        $dateStr  = $now->format('d/m/Y') . ' (' . $dayName . ') — ' . $now->format('H:i');

        $lines = [
            "Data e hora atual: {$dateStr}.",
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

        // Arquivos de conhecimento carregados
        $kbFiles = $agent->knowledgeFiles()->where('status', 'done')->get();
        foreach ($kbFiles as $kbFile) {
            if ($kbFile->extracted_text) {
                $lines[] = "\n--- ARQUIVO: {$kbFile->original_name} ---\n{$kbFile->extracted_text}\n--- FIM DO ARQUIVO ---";
            }
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
            ->whereIn('type', ['text', 'image', 'audio'])
            ->orderByDesc('sent_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        $history = [];

        foreach ($messages as $msg) {
            $role = $msg->direction === 'inbound' ? 'user' : 'assistant';

            // Texto puro, sem mídia, ou áudio com transcrição disponível no body
            if ($msg->type === 'text' || ! $msg->media_url || ($msg->type === 'audio' && $msg->body)) {
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
     * Transcreve um arquivo de áudio via OpenAI Whisper.
     * Aceita URLs absolutas (http/https) ou caminhos relativos do storage público.
     */
    public function transcribeAudio(string $mediaUrl): ?string
    {
        $apiKey = (string) config('ai.whisper_api_key');
        if ($apiKey === '') {
            return null;
        }

        // Obter conteúdo do áudio
        $audioContent = null;
        $ext          = 'ogg';

        if (str_starts_with($mediaUrl, 'http://') || str_starts_with($mediaUrl, 'https://')) {
            $dlResponse = Http::timeout(30)->get($mediaUrl);
            if (! $dlResponse->successful()) {
                Log::channel('whatsapp')->warning('Whisper: falha ao baixar áudio', [
                    'url'    => $mediaUrl,
                    'status' => $dlResponse->status(),
                ]);
                return null;
            }
            $audioContent = $dlResponse->body();
            // Strip path parameters (e.g. ".ogg;codecs=opus" → ".ogg") before extracting extension
            $urlPath = explode(';', parse_url($mediaUrl, PHP_URL_PATH) ?? '')[0];
            $ext     = pathinfo($urlPath, PATHINFO_EXTENSION) ?: 'ogg';
            // Whisper não aceita .opus — usar .ogg (mesmo container, codecs compatíveis)
            if ($ext === 'opus') {
                $ext = 'ogg';
            }
        } else {
            // Caminho relativo de storage público
            $path = Storage::disk('public')->path($mediaUrl);
            if (! file_exists($path)) {
                Log::channel('whatsapp')->warning('Whisper: arquivo de áudio não encontrado', ['path' => $path]);
                return null;
            }
            $audioContent = file_get_contents($path);
            $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'ogg';
        }

        if (! $audioContent) {
            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->attach('file', $audioContent, 'audio.' . $ext)
                ->attach('model', 'whisper-1')
                ->attach('language', 'pt')
                ->post('https://api.openai.com/v1/audio/transcriptions');

            if (! $response->successful()) {
                Log::channel('whatsapp')->warning('Whisper: API retornou erro', [
                    'status' => $response->status(),
                    'body'   => mb_substr($response->body(), 0, 500),
                ]);
                return null;
            }

            return $response->json('text') ?: null;
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('Whisper: exceção ao transcrever', [
                'error' => $e->getMessage(),
            ]);
            return null;
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
