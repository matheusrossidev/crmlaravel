<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ChatbotFlowEdge;
use App\Models\ChatbotFlowNode;
use App\Models\Lead;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Services\ChatbotVariableService;
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
    ) {}

    public function handle(): void
    {
        $conv = WhatsappConversation::withoutGlobalScope('tenant')
            ->with(['chatbotFlow.nodes', 'chatbotFlow.edges'])
            ->find($this->conversationId);

        if (! $conv || ! $conv->chatbot_flow_id || ! $conv->chatbotFlow) {
            return;
        }

        $flow = $conv->chatbotFlow;
        if (! $flow->is_active) {
            return;
        }

        Log::channel('whatsapp')->info('Chatbot: iniciando step', [
            'conversation_id' => $conv->id,
            'flow_id'         => $flow->id,
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

            // Limpa o nó de espera para avançar
            $conv->chatbot_node_id = null;

            if ($waitingNode->type === 'input') {
                [$nextNodeId, $vars] = $this->processInputReply($waitingNode, $conv, $vars);
            } else {
                // Fallback: avança pelo handle default
                $nextNodeId = $this->resolveEdge($flow->id, $waitingNodeId, 'default');
            }

            if (! $nextNodeId) {
                $this->persistVars($conv, $vars);
                return;
            }

            $currentNode = ChatbotFlowNode::withoutGlobalScope('tenant')->find($nextNodeId);
        } else {
            // ── Início do fluxo: busca primeiro nó (sem incoming edge) ──────
            $currentNode = $this->findStartNode($flow->id);
        }

        // ── Loop de execução ──────────────────────────────────────────────────
        while ($currentNode && $iterations < self::MAX_ITERATIONS) {
            $iterations++;

            Log::channel('whatsapp')->info('Chatbot: executando nó', [
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
                    $vars        = $this->executeAction($currentNode, $conv, $vars);
                    $nextId      = $this->resolveEdge($flow->id, $currentNode->id, 'default');
                    $currentNode = $nextId ? ChatbotFlowNode::withoutGlobalScope('tenant')->find($nextId) : null;
                    break;

                case 'delay':
                    $seconds = max(1, min(30, (int) ($currentNode->config['seconds'] ?? 3)));
                    Log::channel('whatsapp')->info('Chatbot: aguardando', [
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
            Log::channel('whatsapp')->warning('Chatbot: limite de iterações atingido', [
                'conversation_id' => $conv->id,
                'flow_id'         => $flow->id,
            ]);
        }
    }

    // ── Nó: message ──────────────────────────────────────────────────────────

    private function executeMessage(ChatbotFlowNode $node, WhatsappConversation $conv, array $vars): void
    {
        $text = ChatbotVariableService::interpolate((string) ($node->config['text'] ?? ''), $vars);
        if ($text === '') {
            return;
        }
        $this->sendWahaMessage($conv, $text);
    }

    // ── Nó: input — envio da pergunta ────────────────────────────────────────

    private function executeInputSend(ChatbotFlowNode $node, WhatsappConversation $conv, array $vars): void
    {
        $text = ChatbotVariableService::interpolate((string) ($node->config['text'] ?? ''), $vars);
        if ($text !== '') {
            $this->sendWahaMessage($conv, $text);
        }
    }

    // ── Nó: input — processar resposta recebida ───────────────────────────────

    private function processInputReply(ChatbotFlowNode $node, WhatsappConversation $conv, array $vars): array
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
        foreach ($branches as $branch) {
            $handle   = $branch['handle'] ?? '';
            $keywords = array_map('strtolower', (array) ($branch['keywords'] ?? []));
            if (in_array(strtolower($body), $keywords, true)) {
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

    private function executeAction(ChatbotFlowNode $node, WhatsappConversation $conv, array $vars): array
    {
        $config = $node->config;
        $type   = $config['type'] ?? '';

        switch ($type) {
            case 'change_stage':
                if ($conv->lead_id && isset($config['stage_id'])) {
                    Lead::withoutGlobalScope('tenant')
                        ->where('id', $conv->lead_id)
                        ->update(['stage_id' => (int) $config['stage_id']]);
                    Log::channel('whatsapp')->info('Chatbot: lead movido de etapa', [
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
                WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('id', $conv->id)
                    ->update($updateData);
                $conv->chatbot_flow_id = null;
                $conv->chatbot_node_id = null;
                Log::channel('whatsapp')->info('Chatbot: conversa transferida para humano', [
                    'id'      => $conv->id,
                    'user_id' => $config['user_id'] ?? null,
                ]);
                break;

            case 'close_conversation':
                WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('id', $conv->id)
                    ->update(['status' => 'closed', 'closed_at' => now()]);
                Log::channel('whatsapp')->info('Chatbot: conversa fechada', ['id' => $conv->id]);
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
        }

        return $vars;
    }

    // ── Nó: end ──────────────────────────────────────────────────────────────

    private function executeEnd(ChatbotFlowNode $node, WhatsappConversation $conv, array $vars): void
    {
        $text = ChatbotVariableService::interpolate((string) ($node->config['text'] ?? ''), $vars);
        if ($text !== '') {
            $this->sendWahaMessage($conv, $text);
        }
        Log::channel('whatsapp')->info('Chatbot: fluxo concluído', [
            'conversation_id' => $conv->id,
            'flow_id'         => $conv->chatbot_flow_id,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

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
        // Nó de início = nó que não é alvo de nenhuma edge no flow
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

    private function sendWahaMessage(WhatsappConversation $conv, string $text): void
    {
        try {
            $instance = $conv->instance;
            if (! $instance) {
                Log::channel('whatsapp')->warning('Chatbot: instância não encontrada', ['conv' => $conv->id]);
                return;
            }

            // Deriva chatId a partir do JID original armazenado nos waha_message_ids
            // (igual ao WhatsappMessageController) para suportar @lid corretamente.
            $chatId   = null;
            $sampleId = WhatsappMessage::withoutGlobalScope('tenant')
                ->where('conversation_id', $conv->id)
                ->whereNotNull('waha_message_id')
                ->where('direction', 'inbound')
                ->latest('sent_at')
                ->value('waha_message_id');

            if ($sampleId && preg_match('/^(?:true|false)_(.+@[\w.]+)_/', $sampleId, $m)) {
                $jid    = $m[1];
                $chatId = str_ends_with($jid, '@lid')
                    ? preg_replace('/[:@].+$/', '', $jid) . '@lid'
                    : preg_replace('/[:@].+$/', '', $jid) . '@c.us';
            }

            if (! $chatId) {
                $rawPhone = ltrim((string) preg_replace('/[:@\s].+$/', '', $conv->phone), '+');
                $chatId   = $rawPhone . '@c.us';
            }

            $waha = new WahaService($instance->session_name);
            $waha->sendText($chatId, $text);
            sleep(self::DEFAULT_MESSAGE_DELAY);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('Chatbot: erro ao enviar mensagem', [
                'conversation_id' => $conv->id,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    private function modifyTag(WhatsappConversation $conv, string $tagName, string $action): void
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

        WhatsappConversation::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update(['tags' => json_encode($tags)]);
        $conv->tags = $tags;
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

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->send($method, $url, ['body' => $body]);

            $saveResponseTo = $config['save_response_to'] ?? null;
            if ($saveResponseTo && ! str_starts_with($saveResponseTo, '$')) {
                $vars[$saveResponseTo] = $response->body();
            }

            Log::channel('whatsapp')->info('Chatbot: webhook enviado', [
                'conversation_id' => $convId,
                'url'             => $url,
                'status'          => $response->status(),
            ]);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('Chatbot: webhook falhou', [
                'conversation_id' => $convId,
                'error'           => $e->getMessage(),
            ]);
        }

        return $vars;
    }

    private function clearFlow(WhatsappConversation $conv): void
    {
        WhatsappConversation::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update([
                'chatbot_flow_id'    => null,
                'chatbot_node_id'    => null,
                'chatbot_variables'  => null,
            ]);
    }

    private function persistVars(WhatsappConversation $conv, array $vars): void
    {
        // Salvar apenas variáveis de sessão (sem prefixo $)
        $session = array_filter($vars, fn ($k) => ! str_starts_with($k, '$'), ARRAY_FILTER_USE_KEY);

        WhatsappConversation::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update([
                'chatbot_node_id'   => $conv->chatbot_node_id,
                'chatbot_variables' => ! empty($session) ? json_encode($session) : null,
            ]);
    }
}
