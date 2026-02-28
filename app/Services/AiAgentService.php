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
        array   $stages             = [],
        array   $availTags          = [],
        bool    $enableIntentNotify = false,
        array   $calendarEvents     = [],
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

        // ── Diretrizes de humanização baseadas no estilo ─────────────────────
        $lines[] = "\nDIRETRIZES DE HUMANIZAÇÃO:";
        if ($agent->communication_style === 'casual') {
            $lines[] = "- Use linguagem descontraída, mas sem exagerar em gírias.";
            $lines[] = "- Varie as saudações (ex: 'Oi!', 'Olá!', 'E aí?', 'Tudo bem?') — NUNCA repita a mesma saudação duas vezes seguidas.";
            $lines[] = "- Use emojis com moderação para expressar empatia e simpatia.";
            $lines[] = "- Demonstre entusiasmo genuíno com o cliente.";
        } elseif ($agent->communication_style === 'formal') {
            $lines[] = "- Mantenha tom profissional e respeitoso em todas as mensagens.";
            $lines[] = "- Varie as formas de tratamento (ex: 'Bom dia', 'Boa tarde', 'Como posso ajudá-lo(a)?').";
            $lines[] = "- Evite abreviações e informalidades.";
            $lines[] = "- Expresse cuidado e atenção de forma elegante (ex: 'Compreendo sua situação', 'Fico à disposição').";
        } else {
            $lines[] = "- Use tom natural e cordial, equilibrando proximidade e profissionalismo.";
            $lines[] = "- Varie as saudações e formas de iniciar frases — evite padrões repetitivos.";
            $lines[] = "- Demonstre empatia quando o cliente expressar dúvidas ou dificuldades.";
        }
        $lines[] = "- Adapte o comprimento das respostas ao contexto: respostas curtas para confirmações, mais detalhadas para dúvidas.";
        $lines[] = "- NUNCA use frases genéricas como 'Claro, posso ajudar com isso!' sem complementar com algo específico.";
        $lines[] = "- Incorpore sua personalidade de {$agent->name} nas respostas — você não é um bot genérico.";
        $lines[] = "- Quando a resposta contiver mais de uma ideia ou uma pergunta após uma declaração, separe-as com quebra de linha dupla (parágrafo separado).";

        if (! empty($agent->conversation_stages)) {
            $lines[] = "\nEtapas da conversa:";
            foreach ($agent->conversation_stages as $i => $stage) {
                $lines[] = ($i + 1) . ". {$stage['name']}" . (! empty($stage['description']) ? ": {$stage['description']}" : '');
            }

            $lines[] = "\nREGRA DE CONTINUIDADE — MUITO IMPORTANTE:";
            $lines[] = "Analise o histórico completo da conversa antes de responder.";
            $lines[] = "Identifique em qual etapa da conversa o cliente se encontra ATUALMENTE com base nas mensagens anteriores.";
            $lines[] = "NUNCA reinicie o fluxo do zero se o cliente já interagiu anteriormente.";
            $lines[] = "Continue sempre de onde a conversa parou, respeitando o contexto já estabelecido.";
            $lines[] = "Se o cliente sumir e voltar, cumprimente-o de forma contextual e continue de onde pararam — não repita a apresentação inicial.";
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
            $lines[] = "\n--- CONTROLE DE FUNIL ---";
            $lines[] = "Etapas disponíveis:";
            foreach ($stages as $s) {
                $annotation = '';
                if ($s['is_won']) {
                    $annotation = ' [ETAPA FINAL: GANHO — use SOMENTE quando o cliente confirmar explicitamente que quer contratar ou comprar]';
                } elseif ($s['is_lost']) {
                    $annotation = ' [ETAPA FINAL: PERDIDO — use SOMENTE quando o cliente recusar explicitamente o serviço ou demonstrar total desinteresse]';
                }
                $lines[] = "  {$s['id']}: {$s['name']}{$annotation}";
            }
            if ($currentStage) {
                $lines[] = "Etapa atual do lead: {$currentStage['name']}";
            }
            $lines[] = "REGRAS PARA MUDANÇA DE ETAPA:";
            $lines[] = "- Avance etapas gradualmente conforme a conversa evolui.";
            $lines[] = "- Mova para GANHO SOMENTE se o cliente confirmar explicitamente que deseja contratar/comprar.";
            $lines[] = "- Mova para PERDIDO SOMENTE se o cliente recusar o serviço de forma explícita.";
            $lines[] = "- Se o cliente demonstrar INTERESSE em contratar → avance para a próxima etapa intermediária, NUNCA para PERDIDO.";
            $lines[] = "- Em caso de dúvida sobre qual etapa usar → mantenha a etapa atual (actions: []).";

            // Calcular próxima etapa intermediária sugerida
            $currentIdx = array_search(true, array_column($stages, 'current'));
            $nextStage  = ($currentIdx !== false
                           && isset($stages[$currentIdx + 1])
                           && ! $stages[$currentIdx + 1]['is_won']
                           && ! $stages[$currentIdx + 1]['is_lost'])
                ? $stages[$currentIdx + 1]
                : null;
            if ($nextStage) {
                $lines[] = "PRÓXIMA ETAPA SUGERIDA (se o cliente demonstrar interesse/avançar): "
                         . "{$nextStage['name']} (use stage_id: {$nextStage['id']})";
            }
            $lines[] = "IMPORTANTE: use apenas os stage_ids listados acima. Se em dúvida, NÃO inclua set_stage nas actions.";
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

        // ── Ferramenta de Agenda (Google Calendar) ────────────────────────────
        if ($agent->enable_calendar_tool) {
            $lines[] = "\n--- FERRAMENTA DE AGENDA (Google Calendar) ---";

            if ($agent->calendar_tool_instructions) {
                $lines[] = "Instruções específicas:\n{$agent->calendar_tool_instructions}";
            }

            if (! empty($calendarEvents)) {
                $lines[] = "\nCompromissos agendados (próximos 7 dias):";
                foreach ($calendarEvents as $ev) {
                    $start = $ev['start'] ?? '';
                    $end   = $ev['end']   ?? '';
                    $title = $ev['title'] ?? 'Sem título';
                    $loc   = $ev['location'] ?? '';
                    $line  = "- [{$start} → {$end}] {$title}";
                    if ($loc) $line .= " | Local: {$loc}";
                    if (! empty($ev['id'])) $line .= " (id: {$ev['id']})";
                    $lines[] = $line;
                }
            } else {
                $lines[] = "Nenhum compromisso agendado nos próximos 7 dias.";
            }

            $lines[] = <<<'CALINSTR'

AÇÕES DE AGENDA disponíveis (use APENAS quando o usuário pedir explicitamente):
- calendar_create: Criar um novo evento.
  {"type":"calendar_create","title":"...","start":"YYYY-MM-DDTHH:MM","end":"YYYY-MM-DDTHH:MM","description":"...","location":"..."}
- calendar_reschedule: Reagendar um evento existente (use o id do evento).
  {"type":"calendar_reschedule","event_id":"...","start":"YYYY-MM-DDTHH:MM","end":"YYYY-MM-DDTHH:MM"}
- calendar_cancel: Cancelar/excluir um evento existente.
  {"type":"calendar_cancel","event_id":"..."}
- calendar_list: Listar eventos de uma data específica.
  {"type":"calendar_list","date":"YYYY-MM-DD"}

REGRAS CRÍTICAS:
1. O sistema executa a ação INSTANTANEAMENTE junto com sua resposta. NÃO diga "Um momento", "Vou verificar" ou "Aguarde" — isso cria expectativa de uma segunda mensagem que NUNCA virá.
2. Quando incluir uma ação de agenda no JSON, na "reply" já confirme como CONCLUÍDO. Exemplo: "Reunião agendada para amanhã às 10h! ✓"
3. Se o usuário já informou data e hora, execute diretamente sem pedir confirmação novamente.
4. Use os ids exatos dos eventos listados acima ao reagendar ou cancelar.
--- FIM DA FERRAMENTA DE AGENDA ---
CALINSTR;
        }

        // ── Detecção de intenção de compra/agendamento ────────────────────────
        if ($enableIntentNotify) {
            $lines[] = <<<'INTENTINSTR'

--- DETECÇÃO DE INTENÇÃO ---
Quando o contato demonstrar intenção CLARA e EXPLÍCITA de:
- Comprar, contratar ou adquirir o produto/serviço → intent: "buy"
- Agendar reunião, demonstração, visita ou ligação → intent: "schedule"
- Fechar negócio ou confirmar contratação → intent: "close"
Use a ação: {"type": "notify_intent", "intent": "buy|schedule|close", "context": "resumo em 1 frase do que o cliente disse"}
NÃO use notify_intent para interesse vago ou curiosidade — apenas intenção clara e explícita.
--- FIM DA DETECÇÃO DE INTENÇÃO ---
INTENTINSTR;
        }

        // ── Formato JSON obrigatório quando há pipeline, tags, intent ou calendar ─
        if (! empty($stages) || ! empty($availTags) || $enableIntentNotify || $agent->enable_calendar_tool) {
            $intentExample = $enableIntentNotify
                ? "\n    {\"type\": \"notify_intent\", \"intent\": \"buy\", \"context\": \"cliente confirmou interesse em contratar\"},"
                : '';
            $calendarExample = $agent->enable_calendar_tool
                ? "\n    {\"type\": \"calendar_create\", \"title\": \"Reunião\", \"start\": \"YYYY-MM-DDTHH:MM\", \"end\": \"YYYY-MM-DDTHH:MM\"},"
                : '';
            $calendarActions = $agent->enable_calendar_tool
                ? "\n- calendar_create / calendar_reschedule / calendar_cancel / calendar_list: ações de agenda (ver instruções acima)."
                : '';
            $lines[] = <<<JSONINSTR

FORMATO DE RESPOSTA OBRIGATÓRIO — responda APENAS com JSON válido, sem markdown:
{
  "reply": "sua resposta ao cliente aqui",
  "actions": [
    {"type": "set_stage", "stage_id": <id_numérico>},
    {"type": "add_tags", "tags": ["tag1", "tag2"]},$intentExample$calendarExample
    {"type": "assign_human"}
  ]
}
Se não precisar de ações, use "actions": [].
NUNCA inclua texto fora do JSON.
Ações disponíveis:
- set_stage: mova o lead para uma etapa do funil (use o stage_id correto da lista acima).
- add_tags: adicione tags à conversa/lead.
- assign_human: use quando o cliente pedir explicitamente para falar com uma pessoa ou quando você não conseguir responder. Inclua essa action junto com a resposta de transferência.$calendarActions
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
     * Divide uma resposta em múltiplas mensagens para envio sequencial humanizado.
     *
     * Estratégia:
     * 1. Dividir por qualquer sequência de newlines (\n+ em vez de apenas \n{2,})
     * 2. Agrupar partes curtas consecutivas para não gerar mensagens minúsculas
     * 3. Se resultado for 1 bloco único E length > maxLength → split ao redor do ponto médio
     */
    public function splitIntoMessages(string $text, int $maxLength): array
    {
        // 1. Dividir por qualquer newline (simples ou duplo)
        $parts = preg_split('/\n+/', $text);
        $parts = array_values(array_filter(array_map('trim', $parts)));

        if (count($parts) > 1) {
            // Agrupar partes curtas consecutivas para não gerar mensagens de 1-2 palavras
            $messages = [];
            $current  = '';
            foreach ($parts as $part) {
                $candidate = $current !== '' ? $current . "\n" . $part : $part;
                if ($current !== '' && mb_strlen($candidate) > $maxLength) {
                    $messages[] = trim($current);
                    $current    = $part;
                } else {
                    $current = $candidate;
                }
            }
            if ($current !== '') {
                $messages[] = trim($current);
            }
            return array_values(array_filter($messages));
        }

        // 2. Texto único sem newlines: se excede maxLength → split ao redor do ponto médio
        if (mb_strlen($text) > $maxLength) {
            $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
            if (count($sentences) > 1) {
                $mid     = (int) (mb_strlen($text) / 2);
                $acc     = 0;
                $splitAt = 0;
                foreach ($sentences as $i => $s) {
                    $acc += mb_strlen($s) + 1;
                    if ($acc >= $mid) {
                        $splitAt = $i + 1;
                        break;
                    }
                }
                if ($splitAt > 0 && $splitAt < count($sentences)) {
                    return array_filter([
                        trim(implode(' ', array_slice($sentences, 0, $splitAt))),
                        trim(implode(' ', array_slice($sentences, $splitAt))),
                    ]);
                }
            }
        }

        // 3. Fallback: texto como mensagem única
        return [$text];
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
