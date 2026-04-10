<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ChatbotFlowEdge;
use App\Models\ChatbotFlowNode;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use App\Models\ChatbotFlow;
use App\Models\InstagramConversation;
use App\Models\InstagramMessage;
use App\Models\Lead;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Services\ChatbotVariableService;
use App\Services\InstagramService;
use App\Services\WahaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessChatbotStep
{
    private const MAX_ITERATIONS = 30;

    /** Pausa automática (segundos) entre envios de mensagem para simular digitação. */
    private const DEFAULT_MESSAGE_DELAY = 3;

    public function __construct(
        private readonly int    $conversationId,
        private readonly string $inboundBody,
        private readonly string $channel = 'whatsapp',
    ) {}

    public function handle(): void
    {
        $conv = $this->loadConversation();

        if (! $conv || ! $conv->chatbot_flow_id || ! $conv->chatbotFlow) {
            return;
        }

        // Verificar opt-out do lead
        if ($conv->lead_id) {
            $lead = \App\Models\Lead::withoutGlobalScope('tenant')->find($conv->lead_id);
            if ($lead && $lead->opted_out) {
                return;
            }
        }

        // Bloquear se tenant com serviço bloqueado (trial expirado, suspenso, etc.)
        $tenant = Tenant::find($conv->tenant_id);
        if ($tenant && $tenant->isServiceBlocked()) {
            Log::channel($this->logChannel())->info('Chatbot: tenant com serviço bloqueado', [
                'conversation_id' => $conv->id,
                'tenant_id'       => $conv->tenant_id,
            ]);
            return;
        }

        $flow = $conv->chatbotFlow;
        if (! $flow->is_active) {
            return;
        }

        Log::channel($this->logChannel())->info('Chatbot: iniciando step', [
            'conversation_id' => $conv->id,
            'flow_id'         => $flow->id,
            'channel'         => $this->channel,
            'waiting_node_id' => $conv->chatbot_node_id,
            'body'            => mb_substr($this->inboundBody, 0, 80),
        ]);

        // Carregar variáveis mescladas (sessão + sistema)
        $vars = ChatbotVariableService::buildVars($conv);

        $waitingNodeId = $conv->chatbot_node_id;
        $iterations    = 0;

        // ── Se temos um nó aguardando resposta ────────────────────────────────
        if ($waitingNodeId) {
            $waitingNode = ChatbotFlowNode::withoutGlobalScope('tenant')->find($waitingNodeId);
            if (! $waitingNode) {
                $this->clearFlow($conv);
                return;
            }

            if ($waitingNode->type === 'input') {
                // Nó de input: processa a resposta do user e avança pro próximo
                $conv->chatbot_node_id = null;
                [$nextNodeId, $vars] = $this->processInputReply($waitingNode, $conv, $vars);

                if (! $nextNodeId) {
                    $this->persistVars($conv, $vars);
                    return;
                }

                $currentNode = ChatbotFlowNode::withoutGlobalScope('tenant')->find($nextNodeId);
            } else {
                // Nó NÃO é input (ex: message, action, condition) — executa ele
                // direto em vez de pular. Isso acontece quando o chatbot é atribuído
                // via dropdown/auto-trigger e o node_id aponta pro nó de start, que
                // é um nó de message (não de input). O correto é executar esse nó.
                $conv->chatbot_node_id = null;
                $currentNode = $waitingNode;
            }
        } else {
            // ── Início do fluxo: busca primeiro nó (sem incoming edge) ──────
            $currentNode = $this->findStartNode($flow->id);
        }

        // ── Loop de execução ──────────────────────────────────────────────────
        while ($currentNode && $iterations < self::MAX_ITERATIONS) {
            $iterations++;

            Log::channel($this->logChannel())->info('Chatbot: executando nó', [
                'conversation_id' => $conv->id,
                'node_id'         => $currentNode->id,
                'type'            => $currentNode->type,
            ]);

            switch ($currentNode->type) {
                case 'message':
                    $this->executeMessage($currentNode, $conv, $vars);
                    $nextId      = $this->resolveEdge($flow->id, $currentNode->id, 'default');
                    $currentNode = $nextId ? ChatbotFlowNode::withoutGlobalScope('tenant')->find($nextId) : null;
                    break;

                case 'input':
                    // Envia a pergunta e PARA aguardando resposta
                    $this->executeInputSend($currentNode, $conv, $vars);
                    $conv->chatbot_node_id = $currentNode->id;
                    $this->persistVars($conv, $vars);
                    return;

                case 'condition':
                    [$nextId, $vars] = $this->executeCondition($currentNode, $flow->id, $vars);
                    $currentNode = $nextId ? ChatbotFlowNode::withoutGlobalScope('tenant')->find($nextId) : null;
                    break;

                case 'action':
                    $vars = $this->executeAction($currentNode, $conv, $vars);
                    // Se a action desativou o chatbot (ex: assign_ai_agent), para o loop
                    if ($conv->chatbot_flow_id === null) {
                        return;
                    }
                    $nextId      = $this->resolveEdge($flow->id, $currentNode->id, 'default');
                    $currentNode = $nextId ? ChatbotFlowNode::withoutGlobalScope('tenant')->find($nextId) : null;
                    break;

                case 'delay':
                    $seconds = max(1, min(30, (int) ($currentNode->config['seconds'] ?? 3)));
                    Log::channel($this->logChannel())->info('Chatbot: aguardando', [
                        'conversation_id' => $conv->id,
                        'seconds'         => $seconds,
                    ]);
                    sleep($seconds);
                    $nextId      = $this->resolveEdge($flow->id, $currentNode->id, 'default');
                    $currentNode = $nextId ? ChatbotFlowNode::withoutGlobalScope('tenant')->find($nextId) : null;
                    break;

                case 'end':
                    $this->executeEnd($currentNode, $conv, $vars);
                    $this->clearFlow($conv);
                    return;

                default:
                    // Tipo desconhecido — avança
                    $nextId      = $this->resolveEdge($flow->id, $currentNode->id, 'default');
                    $currentNode = $nextId ? ChatbotFlowNode::withoutGlobalScope('tenant')->find($nextId) : null;
                    break;
            }
        }

        $this->persistVars($conv, $vars);

        if ($iterations >= self::MAX_ITERATIONS) {
            Log::channel($this->logChannel())->warning('Chatbot: limite de iterações atingido', [
                'conversation_id' => $conv->id,
                'flow_id'         => $flow->id,
            ]);
        }
    }

    // ── Nó: message ──────────────────────────────────────────────────────────

    private function executeMessage(ChatbotFlowNode $node, WhatsappConversation|InstagramConversation $conv, array $vars): void
    {
        $text     = ChatbotVariableService::interpolate((string) ($node->config['text'] ?? ''), $vars);
        $imageUrl = (string) ($node->config['image_url'] ?? '');
        $audioUrl = (string) ($node->config['audio_url'] ?? '');

        if ($audioUrl !== '' && $this->channel === 'whatsapp' && $conv instanceof WhatsappConversation) {
            $this->sendVoice($conv, $audioUrl);
            // If there's also text, send it after the audio
            if ($text !== '') {
                $this->sendText($conv, $text);
            }
        } elseif ($imageUrl !== '') {
            $this->sendImage($conv, $imageUrl, $text);
        } elseif ($text !== '') {
            $this->sendText($conv, $text);
        }
    }

    // ── Nó: input — envio da pergunta ────────────────────────────────────────

    private function executeInputSend(ChatbotFlowNode $node, WhatsappConversation|InstagramConversation $conv, array $vars): void
    {
        $text     = ChatbotVariableService::interpolate((string) ($node->config['text'] ?? ''), $vars);
        $imageUrl = (string) ($node->config['image_url'] ?? '');
        $branches = $node->config['branches'] ?? [];

        // Se tem branches com labels mas sem texto, gera texto default
        // a partir dos labels. Sem isso, o nó de input ficava mudo
        // (elseif text !== '' pulava tudo quando text era vazio).
        if ($text === '' && ! empty($branches)) {
            $labels = array_filter(array_map(fn ($b) => $b['label'] ?? '', $branches));
            if (! empty($labels)) {
                $text = 'Escolha uma opção:';
            }
        }

        if ($imageUrl !== '') {
            $this->sendImage($conv, $imageUrl, $text);
        } elseif ($text !== '') {
            // Instagram: enviar buttons se há branches
            if ($this->channel === 'instagram' && !empty($branches) && $conv instanceof InstagramConversation) {
                // Check if any branch has web_url type → use Button Template
                $hasWebUrl = collect($branches)->contains(fn ($b) => ($b['button_type'] ?? 'postback') === 'web_url');

                if ($hasWebUrl) {
                    // Button Template: supports web_url + postback (max 3 buttons)
                    $templateButtons = [];
                    foreach (array_slice($branches, 0, 3) as $branch) {
                        $label = mb_substr($branch['label'] ?? ($branch['keywords'][0] ?? 'Opção'), 0, 20);
                        $type  = $branch['button_type'] ?? 'postback';
                        if ($type === 'web_url' && !empty($branch['button_url'])) {
                            $templateButtons[] = ['type' => 'web_url', 'title' => $label, 'url' => $branch['button_url']];
                        } else {
                            $templateButtons[] = ['type' => 'postback', 'title' => $label, 'payload' => 'BTN_' . $label];
                        }
                    }
                    if (!empty($templateButtons)) {
                        $this->sendInstagramButtonTemplate($conv, $text, $templateButtons);
                        return;
                    }
                } else {
                    // Quick Replies: simple postback buttons
                    $buttons = [];
                    foreach ($branches as $branch) {
                        $keywords = (array) ($branch['keywords'] ?? []);
                        if (! empty($keywords)) {
                            $buttons[] = mb_substr($keywords[0], 0, 20);
                        }
                    }
                    if (! empty($buttons)) {
                        $this->sendInstagramButtons($conv, $text, $buttons);
                        return;
                    }
                }
            }

            // WhatsApp: enviar lista interativa se há branches com labels
            if ($this->channel === 'whatsapp' && ! empty($branches) && $conv instanceof WhatsappConversation) {
                $rows = [];
                foreach ($branches as $i => $branch) {
                    $label = $branch['label'] ?? '';
                    if ($label !== '') {
                        $rows[] = [
                            'title'       => mb_substr($label, 0, 24),
                            'rowId'       => 'btn_' . $i,
                            'description' => null,
                        ];
                    }
                }
                if (! empty($rows)) {
                    $this->sendWahaList($conv, $text, $rows);
                    return;
                }
            }

            $this->sendText($conv, $text);
        }
    }

    // ── Nó: input — processar resposta recebida ───────────────────────────────

    private function processInputReply(ChatbotFlowNode $node, WhatsappConversation|InstagramConversation $conv, array $vars): array
    {
        $body     = trim($this->inboundBody);
        $flowId   = $node->flow_id;
        $config   = $node->config;

        // Salvar resposta em variável de sessão (read-write, sem prefixo $)
        $saveTo = $config['save_to'] ?? null;
        if ($saveTo && ! str_starts_with($saveTo, '$')) {
            $vars[$saveTo] = $body;
        }

        // Verificar branches (keywords especiais)
        $branches = $config['branches'] ?? [];
        $lowerBody = strtolower($body);
        foreach ($branches as $i => $branch) {
            $handle   = $branch['handle'] ?? 'branch_' . $i;
            $keywords = array_map('strtolower', (array) ($branch['keywords'] ?? []));
            if (in_array($lowerBody, $keywords, true)) {
                $nextId = $this->resolveEdge($flowId, $node->id, $handle);
                return [$nextId, $vars];
            }
        }

        // Fallback: match por label do branch (lista interativa WhatsApp
        // envia o título da row como body — pode não estar nas keywords)
        foreach ($branches as $i => $branch) {
            $handle = $branch['handle'] ?? 'branch_' . $i;
            $label  = $branch['label'] ?? '';
            if ($label !== '' && $lowerBody === strtolower($label)) {
                $nextId = $this->resolveEdge($flowId, $node->id, $handle);
                return [$nextId, $vars];
            }
        }

        // Branch padrão
        $nextId = $this->resolveEdge($flowId, $node->id, 'default');
        return [$nextId, $vars];
    }

    // ── Nó: condition ────────────────────────────────────────────────────────

    private function executeCondition(ChatbotFlowNode $node, int $flowId, array $vars): array
    {
        $config     = $node->config;
        $varName    = $config['variable'] ?? '';
        $varValue   = strtolower((string) ($vars[$varName] ?? ''));
        $conditions = $config['conditions'] ?? [];

        foreach ($conditions as $cond) {
            $handle   = $cond['handle'] ?? 'default';
            $operator = $cond['operator'] ?? 'equals';
            $value    = strtolower((string) ($cond['value'] ?? ''));

            $matched = match ($operator) {
                'equals'      => $varValue === $value,
                'not_equals'  => $varValue !== $value,
                'contains'    => str_contains($varValue, $value),
                'starts_with' => str_starts_with($varValue, $value),
                'ends_with'   => str_ends_with($varValue, $value),
                'gt'          => is_numeric($varValue) && is_numeric($value) && (float) $varValue > (float) $value,
                'lt'          => is_numeric($varValue) && is_numeric($value) && (float) $varValue < (float) $value,
                default       => false,
            };

            if ($matched) {
                $nextId = $this->resolveEdge($flowId, $node->id, $handle);
                return [$nextId, $vars];
            }
        }

        // Nenhuma condição bateu → handle default
        $nextId = $this->resolveEdge($flowId, $node->id, 'default');
        return [$nextId, $vars];
    }

    // ── Nó: action ───────────────────────────────────────────────────────────

    private function executeAction(ChatbotFlowNode $node, WhatsappConversation|InstagramConversation $conv, array $vars): array
    {
        $config = $node->config;
        $type   = $config['type'] ?? '';
        $model  = $this->getConversationModel();

        switch ($type) {
            case 'change_stage':
                if ($conv->lead_id && isset($config['stage_id'])) {
                    Lead::withoutGlobalScope('tenant')
                        ->where('id', $conv->lead_id)
                        ->update(['stage_id' => (int) $config['stage_id']]);
                    Log::channel($this->logChannel())->info('Chatbot: lead movido de etapa', [
                        'lead_id'  => $conv->lead_id,
                        'stage_id' => $config['stage_id'],
                    ]);
                }
                break;

            case 'add_tag':
                $this->modifyTag($conv, (string) ($config['value'] ?? ''), 'add');
                break;

            case 'remove_tag':
                $this->modifyTag($conv, (string) ($config['value'] ?? ''), 'remove');
                break;

            case 'assign_human':
                $updateData = ['chatbot_flow_id' => null, 'chatbot_node_id' => null];
                if (! empty($config['user_id'])) {
                    $updateData['assigned_user_id'] = (int) $config['user_id'];
                }
                $model::withoutGlobalScope('tenant')
                    ->where('id', $conv->id)
                    ->update($updateData);
                $conv->chatbot_flow_id = null;
                $conv->chatbot_node_id = null;
                Log::channel($this->logChannel())->info('Chatbot: conversa transferida para humano', [
                    'id'      => $conv->id,
                    'user_id' => $config['user_id'] ?? null,
                ]);
                break;

            case 'close_conversation':
                $model::withoutGlobalScope('tenant')
                    ->where('id', $conv->id)
                    ->update(['status' => 'closed', 'closed_at' => now()]);
                Log::channel($this->logChannel())->info('Chatbot: conversa fechada', ['id' => $conv->id]);
                break;

            case 'save_variable':
                $varName = (string) ($config['variable'] ?? '');
                if ($varName && ! str_starts_with($varName, '$')) {
                    $vars[$varName] = ChatbotVariableService::interpolate(
                        (string) ($config['value'] ?? ''),
                        $vars,
                    );
                }
                break;

            case 'send_webhook':
                $vars = $this->executeSendWebhook($config, $vars, $conv->id);
                break;

            case 'set_custom_field':
                $fieldName  = (string) ($config['field_name'] ?? '');
                $fieldValue = ChatbotVariableService::interpolate((string) ($config['value'] ?? ''), $vars);
                if ($fieldName && $conv->lead_id) {
                    $this->setChatbotCustomField($conv->lead_id, $fieldName, $fieldValue);
                    Log::channel($this->logChannel())->info('Chatbot: campo personalizado preenchido', [
                        'lead_id'    => $conv->lead_id,
                        'field_name' => $fieldName,
                        'value'      => $fieldValue,
                    ]);
                }
                break;

            case 'send_whatsapp':
                // Ação WhatsApp-específica — só executa no canal WhatsApp
                if ($this->channel === 'whatsapp' && $conv instanceof WhatsappConversation) {
                    $phoneMode = $config['phone_mode'] ?? 'variable';
                    if ($phoneMode === 'custom' && ! empty($config['custom_phone'])) {
                        $phone   = preg_replace('/\D/', '', (string) $config['custom_phone']);
                        $message = ChatbotVariableService::interpolate((string) ($config['message'] ?? ''), $vars);
                        if ($message !== '') {
                            $chatId = $phone . '@c.us';
                            $instance = $conv->instance;
                            if ($instance) {
                                try {
                                    \App\Services\WhatsappServiceFactory::for($instance)->sendText($chatId, $message);
                                    Log::channel('whatsapp')->info('Chatbot: WhatsApp enviado para número fixo', ['conv' => $conv->id, 'phone' => $phone]);
                                } catch (\Throwable $e) {
                                    Log::channel('whatsapp')->error('Chatbot: erro ao enviar WhatsApp', ['conv' => $conv->id, 'error' => $e->getMessage()]);
                                }
                            }
                        }
                    } else {
                        $message = ChatbotVariableService::interpolate((string) ($config['message'] ?? ''), $vars);
                        if ($message !== '') {
                            $this->sendWahaMessage($conv, $message);
                        }
                    }
                }
                break;

            case 'assign_ai_agent':
                $agentId = (int) ($config['agent_id'] ?? 0);
                if ($agentId <= 0) break;

                // Verificar que o agent existe, pertence ao tenant e está ativo
                $aiAgent = \App\Models\AiAgent::withoutGlobalScope('tenant')
                    ->where('id', $agentId)
                    ->where('tenant_id', $conv->tenant_id)
                    ->where('is_active', true)
                    ->first();

                if (! $aiAgent) {
                    Log::channel($this->logChannel())->warning('Chatbot: assign_ai_agent falhou — agent não encontrado ou inativo', [
                        'conversation_id' => $conv->id,
                        'agent_id'        => $agentId,
                    ]);
                    break;
                }

                // INVARIANTE: chatbot e IA sao mutuamente exclusivos.
                // Incrementa completions_count do flow (igual executeEnd faz).
                if ($conv->chatbot_flow_id) {
                    ChatbotFlow::withoutGlobalScope('tenant')
                        ->where('id', $conv->chatbot_flow_id)
                        ->increment('completions_count');
                }

                // Limpa chatbot e atribui agente IA
                $model::withoutGlobalScope('tenant')
                    ->where('id', $conv->id)
                    ->update([
                        'ai_agent_id'       => $aiAgent->id,
                        'chatbot_flow_id'   => null,
                        'chatbot_node_id'   => null,
                        'chatbot_variables' => null,
                    ]);

                // Atualiza a instancia local pra o loop saber que o bot terminou
                $conv->chatbot_flow_id = null;
                $conv->chatbot_node_id = null;
                $conv->ai_agent_id     = $aiAgent->id;

                Log::channel($this->logChannel())->info('Chatbot: conversa atribuída a agente IA', [
                    'conversation_id' => $conv->id,
                    'agent_id'        => $aiAgent->id,
                    'agent_name'      => $aiAgent->name,
                ]);

                // Dispara a IA imediatamente pra dar boas-vindas contextualizada.
                // A IA le o historico de mensagens (que inclui toda conversa do bot)
                // e responde como continuidade natural do atendimento.
                try {
                    $conv->refresh();
                    (new ProcessAiResponse($conv->id, 0))->process();
                } catch (\Throwable $e) {
                    Log::channel($this->logChannel())->error('Chatbot: falha ao disparar IA apos assign_ai_agent', [
                        'conversation_id' => $conv->id,
                        'error'           => $e->getMessage(),
                    ]);
                }
                break;

            case 'create_task':
                $subject = ChatbotVariableService::interpolate((string) ($config['subject'] ?? ''), $vars);
                if (empty($subject)) break;

                $dueDate = now()->addDays((int) ($config['due_date_offset'] ?? 0))->format('Y-m-d');

                $assignedTo = null;
                if (($config['assigned_to_mode'] ?? 'automatic') === 'user') {
                    $assignedTo = ((int) ($config['assigned_to_user_id'] ?? 0)) ?: null;
                } else {
                    $assignedTo = $conv->lead?->assigned_to;
                }

                $taskData = [
                    'tenant_id'  => $conv->tenant_id,
                    'subject'    => $subject,
                    'description' => ChatbotVariableService::interpolate((string) ($config['description'] ?? ''), $vars),
                    'type'       => $config['task_type'] ?? 'task',
                    'priority'   => $config['priority'] ?? 'medium',
                    'due_date'   => $dueDate,
                    'due_time'   => $config['due_time'] ?? null,
                    'lead_id'    => $conv->lead_id,
                    'assigned_to' => $assignedTo,
                ];

                if ($conv instanceof WhatsappConversation) {
                    $taskData['whatsapp_conversation_id'] = $conv->id;
                } elseif ($conv instanceof InstagramConversation) {
                    $taskData['instagram_conversation_id'] = $conv->id;
                }

                Task::create($taskData);
                Log::channel($this->logChannel())->info('Chatbot: tarefa criada', ['conv' => $conv->id, 'subject' => $subject]);
                break;
        }

        return $vars;
    }

    // ── Nó: end ──────────────────────────────────────────────────────────────

    private function executeEnd(ChatbotFlowNode $node, WhatsappConversation|InstagramConversation $conv, array $vars): void
    {
        $text = ChatbotVariableService::interpolate((string) ($node->config['text'] ?? ''), $vars);
        if ($text !== '') {
            $this->sendText($conv, $text);
        }

        // Track completion
        if ($conv->chatbot_flow_id) {
            ChatbotFlow::withoutGlobalScope('tenant')
                ->where('id', $conv->chatbot_flow_id)
                ->increment('completions_count');
        }

        Log::channel($this->logChannel())->info('Chatbot: fluxo concluído', [
            'conversation_id' => $conv->id,
            'flow_id'         => $conv->chatbot_flow_id,
        ]);
    }

    // ── Métodos-ponte (multi-canal) ──────────────────────────────────────────

    private function sendText(WhatsappConversation|InstagramConversation $conv, string $text): void
    {
        if ($this->channel === 'instagram' && $conv instanceof InstagramConversation) {
            $this->sendInstagramMessage($conv, $text);
        } else {
            $this->sendWahaMessage($conv, $text);
        }
    }

    private function sendImage(WhatsappConversation|InstagramConversation $conv, string $imageUrl, string $caption = ''): void
    {
        if ($this->channel === 'instagram' && $conv instanceof InstagramConversation) {
            $this->sendInstagramImage($conv, $imageUrl, $caption);
        } else {
            $this->sendWahaImage($conv, $imageUrl, $caption);
        }
    }

    private function sendVoice(WhatsappConversation $conv, string $audioUrl): void
    {
        try {
            $instance = $conv->instance;
            if (! $instance) {
                Log::channel('whatsapp')->warning('Chatbot: instância não encontrada para áudio', ['conv' => $conv->id]);
                return;
            }

            $chatId = $this->resolveChatId($conv);
            $waha   = \App\Services\WhatsappServiceFactory::for($instance);
            $waha->sendVoice($chatId, $audioUrl);

            sleep(self::DEFAULT_MESSAGE_DELAY);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('Chatbot: erro ao enviar áudio', [
                'conversation_id' => $conv->id,
                'audio_url'       => $audioUrl,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    // ── Instagram senders ────────────────────────────────────────────────────

    private function sendInstagramMessage(InstagramConversation $conv, string $text): void
    {
        try {
            $instance = $conv->instance;
            if (! $instance) {
                Log::channel('instagram')->warning('Chatbot: instância IG não encontrada', ['conv' => $conv->id]);
                return;
            }

            $service = new InstagramService(decrypt($instance->access_token));
            $service->sendMessage($conv->igsid, $text);

            $this->saveInstagramOutbound($conv, $text);
            sleep(self::DEFAULT_MESSAGE_DELAY);
        } catch (\Throwable $e) {
            Log::channel('instagram')->error('Chatbot: erro ao enviar mensagem IG', [
                'conversation_id' => $conv->id,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    private function sendInstagramImage(InstagramConversation $conv, string $imageUrl, string $caption = ''): void
    {
        try {
            $instance = $conv->instance;
            if (! $instance) {
                Log::channel('instagram')->warning('Chatbot: instância IG não encontrada para imagem', ['conv' => $conv->id]);
                return;
            }

            $service = new InstagramService(decrypt($instance->access_token));
            $service->sendImageAttachment($conv->igsid, $imageUrl);
            if ($caption !== '') {
                $service->sendMessage($conv->igsid, $caption);
            }
            sleep(self::DEFAULT_MESSAGE_DELAY);
        } catch (\Throwable $e) {
            Log::channel('instagram')->error('Chatbot: erro ao enviar imagem IG', [
                'conversation_id' => $conv->id,
                'image_url'       => $imageUrl,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    private function sendInstagramButtons(InstagramConversation $conv, string $text, array $buttons): void
    {
        try {
            $instance = $conv->instance;
            if (! $instance) {
                Log::channel('instagram')->warning('Chatbot: instância IG não encontrada para buttons', ['conv' => $conv->id]);
                return;
            }

            $service = new InstagramService(decrypt($instance->access_token));
            $service->sendMessageWithButtons($conv->igsid, $text, $buttons);

            $this->saveInstagramOutbound($conv, $text);
            sleep(self::DEFAULT_MESSAGE_DELAY);
        } catch (\Throwable $e) {
            Log::channel('instagram')->error('Chatbot: erro ao enviar buttons IG', [
                'conversation_id' => $conv->id,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    private function sendInstagramButtonTemplate(InstagramConversation $conv, string $text, array $buttons): void
    {
        try {
            $instance = $conv->instance;
            if (! $instance) {
                Log::channel('instagram')->warning('Chatbot: instância IG não encontrada para button template', ['conv' => $conv->id]);
                return;
            }

            $service = new InstagramService(decrypt($instance->access_token));
            $service->sendButtonTemplate($conv->igsid, $text, $buttons);

            $this->saveInstagramOutbound($conv, $text);
            sleep(self::DEFAULT_MESSAGE_DELAY);
        } catch (\Throwable $e) {
            Log::channel('instagram')->error('Chatbot: erro ao enviar button template IG', [
                'conversation_id' => $conv->id,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    /**
     * Save outbound Instagram message to database (so it appears in inbox).
     */
    private function saveInstagramOutbound(InstagramConversation $conv, string $text): void
    {
        try {
            InstagramMessage::withoutGlobalScope('tenant')->create([
                'tenant_id'       => $conv->tenant_id,
                'conversation_id' => $conv->id,
                'direction'       => 'outbound',
                'type'            => 'text',
                'body'            => $text,
                'sent_by'         => 'chatbot',
                'ack'             => 'sent',
                'sent_at'         => now(),
            ]);
        } catch (\Throwable) {}
    }

    // ── WhatsApp senders ─────────────────────────────────────────────────────

    private function sendWahaMessage(WhatsappConversation $conv, string $text): void
    {
        try {
            $instance = $conv->instance;
            if (! $instance) {
                Log::channel('whatsapp')->warning('Chatbot: instância não encontrada', ['conv' => $conv->id]);
                return;
            }

            // Registra intent ANTES de enviar — quando o webhook do WAHA voltar
            // com fromMe=true (echo), ProcessWahaWebhook le esse cache e marca a
            // mensagem como sent_by=chatbot. Sem isso, ela cairia em human_phone.
            $this->cacheOutboundIntent($conv->id, $text, 'chatbot');

            $chatId = $this->resolveChatId($conv);
            $waha   = \App\Services\WhatsappServiceFactory::for($instance);
            $waha->sendText($chatId, $text);
            sleep(self::DEFAULT_MESSAGE_DELAY);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('Chatbot: erro ao enviar mensagem', [
                'conversation_id' => $conv->id,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cache de intent: registra "essa mensagem vai sair pelo {sent_by}" antes
     * de pedir pro WAHA enviar. Quando o webhook voltar com fromMe=true (echo),
     * ProcessWahaWebhook le essa chave pra atribuir a autoria correta.
     *
     * Chave inclui conversation_id pra evitar colisao entre conversas
     * diferentes que mandam o mesmo texto. TTL de 120s e mais que suficiente
     * pro echo do WAHA voltar (normalmente em 1-3s).
     */
    private function cacheOutboundIntent(int $convId, string $body, string $sentBy, ?int $agentId = null): void
    {
        \Illuminate\Support\Facades\Cache::put(
            "outbound_intent:{$convId}:" . md5(trim($body)),
            ['sent_by' => $sentBy, 'sent_by_agent_id' => $agentId],
            120
        );
    }

    private function sendWahaImage(WhatsappConversation $conv, string $imageUrl, string $caption = ''): void
    {
        try {
            $instance = $conv->instance;
            if (! $instance) {
                Log::channel('whatsapp')->warning('Chatbot: instância não encontrada para imagem', ['conv' => $conv->id]);
                return;
            }

            // Registra intent ANTES de enviar (caption serve como body pro hash)
            $this->cacheOutboundIntent($conv->id, $caption ?: $imageUrl, 'chatbot');

            $chatId    = $this->resolveChatId($conv);
            $waha      = \App\Services\WhatsappServiceFactory::for($instance);
            $localPath = $this->resolveLocalImagePath($imageUrl);

            if ($localPath !== null && file_exists($localPath)) {
                $mime = mime_content_type($localPath) ?: 'image/jpeg';
                $waha->sendImageBase64($chatId, $localPath, $mime, $caption);
            } else {
                $waha->sendImage($chatId, $imageUrl, $caption);
            }

            sleep(self::DEFAULT_MESSAGE_DELAY);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('Chatbot: erro ao enviar imagem', [
                'conversation_id' => $conv->id,
                'image_url'       => $imageUrl,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    private function sendWahaList(WhatsappConversation $conv, string $description, array $rows): void
    {
        try {
            $instance = $conv->instance;
            if (! $instance) {
                Log::channel('whatsapp')->warning('Chatbot: instância não encontrada para lista', ['conv' => $conv->id]);
                return;
            }

            // Registra intent ANTES de enviar
            $this->cacheOutboundIntent($conv->id, $description, 'chatbot');

            $chatId = $this->resolveChatId($conv);
            $waha   = \App\Services\WhatsappServiceFactory::for($instance);

            Log::channel('whatsapp')->info('Chatbot: enviando lista interativa', [
                'conversation_id' => $conv->id,
                'chatId'          => $chatId,
                'rows_count'      => count($rows),
            ]);

            $result = $waha->sendList($chatId, $description, $rows);

            Log::channel('whatsapp')->info('Chatbot: lista enviada', [
                'conversation_id' => $conv->id,
                'result'          => $result,
            ]);

            sleep(self::DEFAULT_MESSAGE_DELAY);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('Chatbot: erro ao enviar lista interativa', [
                'conversation_id' => $conv->id,
                'chatId'          => $this->resolveChatId($conv),
                'error'           => $e->getMessage(),
            ]);
            // Fallback: enviar como texto puro se lista falhar
            $this->sendWahaMessage($conv, $description);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function loadConversation(): WhatsappConversation|InstagramConversation|null
    {
        if ($this->channel === 'instagram') {
            return InstagramConversation::withoutGlobalScope('tenant')
                ->with(['chatbotFlow.nodes', 'chatbotFlow.edges', 'instance'])
                ->find($this->conversationId);
        }

        return WhatsappConversation::withoutGlobalScope('tenant')
            ->with(['chatbotFlow.nodes', 'chatbotFlow.edges'])
            ->find($this->conversationId);
    }

    private function getConversationModel(): string
    {
        return $this->channel === 'instagram'
            ? InstagramConversation::class
            : WhatsappConversation::class;
    }

    private function logChannel(): string
    {
        return $this->channel === 'instagram' ? 'instagram' : 'whatsapp';
    }

    private function resolveEdge(int $flowId, int $sourceNodeId, string $sourceHandle): ?int
    {
        $edge = ChatbotFlowEdge::withoutGlobalScope('tenant')
            ->where('flow_id', $flowId)
            ->where('source_node_id', $sourceNodeId)
            ->where('source_handle', $sourceHandle)
            ->first();

        return $edge?->target_node_id;
    }

    private function findStartNode(int $flowId): ?ChatbotFlowNode
    {
        $targetIds = ChatbotFlowEdge::withoutGlobalScope('tenant')
            ->where('flow_id', $flowId)
            ->pluck('target_node_id')
            ->toArray();

        return ChatbotFlowNode::withoutGlobalScope('tenant')
            ->where('flow_id', $flowId)
            ->when(! empty($targetIds), fn ($q) => $q->whereNotIn('id', $targetIds))
            ->orderBy('canvas_y')
            ->first();
    }

    private function resolveChatId(WhatsappConversation $conv): ?string
    {
        $sampleId = WhatsappMessage::withoutGlobalScope('tenant')
            ->where('conversation_id', $conv->id)
            ->whereNotNull('waha_message_id')
            ->where('direction', 'inbound')
            ->latest('sent_at')
            ->value('waha_message_id');

        if ($sampleId && preg_match('/^(?:true|false)_(.+@[\w.]+)_/', $sampleId, $m)) {
            $jid = $m[1];
            return str_ends_with($jid, '@lid')
                ? preg_replace('/[:@].+$/', '', $jid) . '@lid'
                : preg_replace('/[:@].+$/', '', $jid) . '@c.us';
        }

        $rawPhone = ltrim((string) preg_replace('/[:@\s].+$/', '', $conv->phone), '+');
        return $rawPhone . '@c.us';
    }

    private function resolveLocalImagePath(string $url): ?string
    {
        $appUrl        = rtrim((string) config('app.url'), '/');
        $storagePrefix = $appUrl . '/storage/';

        if (str_starts_with($url, $storagePrefix)) {
            $relative = substr($url, strlen($storagePrefix));
            return storage_path('app/public/' . $relative);
        }

        return null;
    }

    private function modifyTag(WhatsappConversation|InstagramConversation $conv, string $tagName, string $action): void
    {
        if ($tagName === '') {
            return;
        }

        $tags = $conv->tags ?? [];
        if ($action === 'add' && ! in_array($tagName, $tags, true)) {
            $tags[] = $tagName;
        } elseif ($action === 'remove') {
            $tags = array_values(array_filter($tags, fn ($t) => $t !== $tagName));
        }

        $model = $this->getConversationModel();
        $model::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update(['tags' => json_encode($tags)]);
        $conv->tags = $tags;
    }

    private function setChatbotCustomField(int $leadId, string $fieldName, string $value): void
    {
        $def = CustomFieldDefinition::withoutGlobalScope('tenant')
            ->where('name', $fieldName)
            ->first();

        if (! $def) {
            Log::channel($this->logChannel())->warning('Chatbot: campo personalizado não encontrado', ['name' => $fieldName]);
            return;
        }

        $col = match (true) {
            in_array($def->field_type, ['number', 'currency', 'percent'], true) => 'value_number',
            $def->field_type === 'date'                                          => 'value_date',
            in_array($def->field_type, ['boolean', 'checkbox'], true)           => 'value_boolean',
            in_array($def->field_type, ['select', 'multiselect'], true)         => 'value_json',
            default                                                              => 'value_text',
        };

        $typed = match ($col) {
            'value_number'  => is_numeric($value) ? (float) $value : null,
            'value_date'    => $value !== '' ? $value : null,
            'value_boolean' => in_array(strtolower($value), ['1', 'true', 'sim', 'yes'], true),
            'value_json'    => [$value],
            default         => $value,
        };

        CustomFieldValue::withoutGlobalScope('tenant')->updateOrCreate(
            ['lead_id' => $leadId, 'field_id' => $def->id],
            ['tenant_id' => $def->tenant_id, $col => $typed],
        );
    }

    private function executeSendWebhook(array $config, array $vars, int $convId): array
    {
        try {
            $url     = ChatbotVariableService::interpolate((string) ($config['url'] ?? ''), $vars);
            $method  = strtoupper((string) ($config['method'] ?? 'POST'));
            $body    = ChatbotVariableService::interpolate((string) ($config['body'] ?? ''), $vars);

            $headers = [];
            foreach ($config['headers'] ?? [] as $h) {
                $key   = ChatbotVariableService::interpolate((string) ($h['key'] ?? ''), $vars);
                $value = ChatbotVariableService::interpolate((string) ($h['value'] ?? ''), $vars);
                if ($key !== '') {
                    $headers[$key] = $value;
                }
            }

            $hasBody = $body !== '' && in_array($method, ['POST', 'PUT', 'PATCH'], true);
            $headersNorm = array_change_key_case($headers, CASE_LOWER);
            if ($hasBody && ! isset($headersNorm['content-type'])) {
                $headers['Content-Type'] = 'application/json';
            }

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->send($method, $url, $hasBody ? ['body' => $body] : []);

            $saveResponseTo = $config['save_response_to'] ?? null;
            if ($saveResponseTo && ! str_starts_with($saveResponseTo, '$')) {
                $vars[$saveResponseTo] = $response->body();
            }

            Log::channel($this->logChannel())->info('Chatbot: webhook enviado', [
                'conversation_id' => $convId,
                'url'             => $url,
                'status'          => $response->status(),
            ]);
        } catch (\Throwable $e) {
            Log::channel($this->logChannel())->error('Chatbot: webhook falhou', [
                'conversation_id' => $convId,
                'error'           => $e->getMessage(),
            ]);
        }

        return $vars;
    }

    private function clearFlow(WhatsappConversation|InstagramConversation $conv): void
    {
        $model = $this->getConversationModel();
        // Keep chatbot_flow_id so conversation appears in results/analytics.
        // Only clear node_id (stops processing) and variables.
        $model::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update([
                'chatbot_node_id'    => null,
                'chatbot_variables'  => null,
            ]);
    }

    private function persistVars(WhatsappConversation|InstagramConversation $conv, array $vars): void
    {
        // Salvar apenas variáveis de sessão (sem prefixo $)
        $session = array_filter($vars, fn ($k) => ! str_starts_with($k, '$'), ARRAY_FILTER_USE_KEY);

        $model = $this->getConversationModel();
        $model::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update([
                'chatbot_node_id'   => $conv->chatbot_node_id,
                'chatbot_variables' => ! empty($session) ? json_encode($session) : null,
            ]);
    }
}
