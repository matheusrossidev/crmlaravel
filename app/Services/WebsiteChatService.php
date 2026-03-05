<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChatbotFlowEdge;
use App\Models\ChatbotFlowNode;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\WebsiteConversation;
use App\Models\WebsiteMessage;
use Illuminate\Support\Facades\Log;

class WebsiteChatService
{
    private const MAX_ITERATIONS = 30;

    /**
     * Processa a mensagem do visitante e retorna as respostas do bot.
     * Retorna array de strings com as mensagens de saída.
     */
    /**
     * Processa a mensagem do visitante e retorna um array com replies e buttons.
     * @return array{replies: string[], buttons: array<array{label: string, value: string}>}
     */
    public function processMessage(WebsiteConversation $conv, string $inboundBody): array
    {
        $conv->load(['flow.nodes', 'flow.edges']);

        if (! $conv->flow || ! $conv->flow->is_active) {
            return ['replies' => [], 'buttons' => [], 'input_type' => 'text'];
        }

        $flow    = $conv->flow;
        $vars    = $this->buildVars($conv);
        $replies = [];
        $buttons = [];

        $waitingNodeId = $conv->chatbot_node_id;

        // ── Resolver ponto de entrada ─────────────────────────────────────────
        if ($waitingNodeId) {
            $waitingNode = ChatbotFlowNode::withoutGlobalScope('tenant')->find($waitingNodeId);
            if (! $waitingNode) {
                $this->clearFlow($conv);
                return ['replies' => [], 'buttons' => [], 'input_type' => 'text'];
            }

            $conv->chatbot_node_id = null;

            if ($waitingNode->type === 'input') {
                [$nextNodeId, $vars] = $this->processInputReply($waitingNode, $flow->id, $inboundBody, $vars);
            } else {
                $nextNodeId = $this->resolveEdge($flow->id, $waitingNodeId, 'default');
            }

            if (! $nextNodeId) {
                $this->persistVars($conv, $vars);
                return ['replies' => $replies, 'buttons' => [], 'input_type' => 'text'];
            }

            $currentNode = ChatbotFlowNode::withoutGlobalScope('tenant')->find($nextNodeId);
        } else {
            $currentNode = $this->findStartNode($flow->id);
        }

        // ── Loop de execução ──────────────────────────────────────────────────
        $iterations = 0;
        while ($currentNode && $iterations < self::MAX_ITERATIONS) {
            $iterations++;

            switch ($currentNode->type) {
                case 'message':
                    $this->collectMessage($currentNode, $vars, $replies);
                    $nextId      = $this->resolveEdge($flow->id, $currentNode->id, 'default');
                    $currentNode = $nextId ? ChatbotFlowNode::withoutGlobalScope('tenant')->find($nextId) : null;
                    break;

                case 'input':
                    $this->collectMessage($currentNode, $vars, $replies);
                    $conv->chatbot_node_id = $currentNode->id;
                    // Gerar botões de quick reply se configurado
                    if (! empty($currentNode->config['show_buttons'])) {
                        $buttons = $this->collectButtons($currentNode);
                    }
                    $this->persistVars($conv, $vars);
                    $this->saveOutboundMessages($conv->id, $replies);
                    return ['replies' => $replies, 'buttons' => $buttons, 'input_type' => $currentNode->config['field_type'] ?? 'text'];

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
                    // No website channel delays are skipped (no real-time delivery)
                    $nextId      = $this->resolveEdge($flow->id, $currentNode->id, 'default');
                    $currentNode = $nextId ? ChatbotFlowNode::withoutGlobalScope('tenant')->find($nextId) : null;
                    break;

                case 'end':
                    $text = ChatbotVariableService::interpolate((string) ($currentNode->config['text'] ?? ''), $vars);
                    if ($text !== '') {
                        $replies[] = $text;
                    }
                    $this->persistVars($conv, $vars);
                    $this->clearFlow($conv);
                    $this->saveOutboundMessages($conv->id, $replies);
                    return ['replies' => $replies, 'buttons' => [], 'input_type' => 'text'];

                default:
                    $nextId      = $this->resolveEdge($flow->id, $currentNode->id, 'default');
                    $currentNode = $nextId ? ChatbotFlowNode::withoutGlobalScope('tenant')->find($nextId) : null;
                    break;
            }
        }

        $this->persistVars($conv, $vars);
        $this->saveOutboundMessages($conv->id, $replies);

        if ($iterations >= self::MAX_ITERATIONS) {
            Log::warning('WebsiteChatService: limite de iterações atingido', ['conversation_id' => $conv->id]);
        }

        return ['replies' => $replies, 'buttons' => [], 'input_type' => 'text'];
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    private function collectMessage(ChatbotFlowNode $node, array $vars, array &$replies): void
    {
        $text = ChatbotVariableService::interpolate((string) ($node->config['text'] ?? ''), $vars);
        if ($text !== '') {
            $replies[] = $text;
        }
        // Images are skipped in the text channel (website shows only text for now)
    }

    /**
     * Gera array de botões de quick reply a partir das branches do nó input.
     * Usa o label da branch como texto e o primeiro keyword como valor enviado.
     *
     * @return array<array{label: string, value: string}>
     */
    private function collectButtons(ChatbotFlowNode $node): array
    {
        $buttons = [];
        foreach ($node->config['branches'] ?? [] as $branch) {
            $label    = (string) ($branch['label'] ?? '');
            $keywords = (array) ($branch['keywords'] ?? []);
            $value    = $keywords[0] ?? $label;
            if ($label !== '') {
                $buttons[] = ['label' => $label, 'value' => $value];
            }
        }
        return $buttons;
    }

    private function processInputReply(ChatbotFlowNode $node, int $flowId, string $body, array $vars): array
    {
        $body      = trim($body);
        $config    = $node->config;
        $fieldType = $config['field_type'] ?? 'text';
        $value     = $fieldType === 'phone' ? $this->normalizePhone($body) : $body;

        $saveTo = $config['save_to'] ?? null;
        if ($saveTo && ! str_starts_with($saveTo, '$')) {
            $vars[$saveTo] = $value;
        }

        foreach ($config['branches'] ?? [] as $branch) {
            $handle   = $branch['handle'] ?? '';
            $keywords = array_map('strtolower', (array) ($branch['keywords'] ?? []));
            if (in_array(strtolower($body), $keywords, true)) {
                $nextId = $this->resolveEdge($flowId, $node->id, $handle);
                return [$nextId, $vars];
            }
        }

        $nextId = $this->resolveEdge($flowId, $node->id, 'default');
        return [$nextId, $vars];
    }

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

        $nextId = $this->resolveEdge($flowId, $node->id, 'default');
        return [$nextId, $vars];
    }

    private function executeAction(ChatbotFlowNode $node, WebsiteConversation $conv, array $vars): array
    {
        $config = $node->config;
        $type   = $config['type'] ?? '';

        switch ($type) {
            case 'create_lead':
                $this->executeCreateLead($config, $conv, $vars);
                break;

            case 'change_stage':
                if ($conv->lead_id && isset($config['stage_id'])) {
                    Lead::withoutGlobalScope('tenant')
                        ->where('id', $conv->lead_id)
                        ->update(['stage_id' => (int) $config['stage_id']]);
                }
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

            case 'close_conversation':
                WebsiteConversation::withoutGlobalScope('tenant')
                    ->where('id', $conv->id)
                    ->update(['status' => 'closed']);
                break;
        }

        return $vars;
    }

    private function executeCreateLead(array $config, WebsiteConversation $conv, array $vars): void
    {
        $name    = trim((string) ($vars[$config['name_var']  ?? ''] ?? ''));
        $email   = trim((string) ($vars[$config['email_var'] ?? ''] ?? ''));
        $phone   = trim((string) ($vars[$config['phone_var'] ?? ''] ?? ''));
        $stageId = isset($config['stage_id']) ? (int) $config['stage_id'] : null;

        if (! $name && ! $email && ! $phone) {
            return;
        }

        // Try to find existing lead by email first, then phone
        $lead = null;
        if ($email) {
            $lead = Lead::withoutGlobalScope('tenant')
                ->where('tenant_id', $conv->tenant_id)
                ->where('email', $email)
                ->first();
        }
        if (! $lead && $phone) {
            $lead = Lead::withoutGlobalScope('tenant')
                ->where('tenant_id', $conv->tenant_id)
                ->where('phone', $phone)
                ->first();
        }

        $leadData = array_filter([
            'tenant_id'    => $conv->tenant_id,
            'name'         => $name ?: ($email ?: $phone),
            'email'        => $email ?: null,
            'phone'        => $phone ?: null,
            'stage_id'     => $stageId,
            'source'       => 'website',
            'utm_source'   => $conv->utm_source   ?: null,
            'utm_medium'   => $conv->utm_medium   ?: null,
            'utm_campaign' => $conv->utm_campaign ?: null,
            'utm_content'  => $conv->utm_content  ?: null,
            'utm_term'     => $conv->utm_term     ?: null,
        ]);

        if ($lead) {
            $lead->update(array_filter([
                'name'         => $name ?: null,
                'email'        => $email ?: null,
                'phone'        => $phone ?: null,
                'stage_id'     => $stageId ?: $lead->stage_id,
                'utm_source'   => $lead->utm_source   ?: $conv->utm_source,
                'utm_medium'   => $lead->utm_medium   ?: $conv->utm_medium,
                'utm_campaign' => $lead->utm_campaign ?: $conv->utm_campaign,
                'utm_content'  => $lead->utm_content  ?: $conv->utm_content,
                'utm_term'     => $lead->utm_term     ?: $conv->utm_term,
            ]));
        } else {
            $lead = Lead::withoutGlobalScope('tenant')->create($leadData);

            // Auto-link campaign by utm_campaign value
            if ($conv->utm_campaign) {
                $campaign = Campaign::withoutGlobalScope('tenant')
                    ->where('tenant_id', $conv->tenant_id)
                    ->where(function ($q) use ($conv) {
                        $q->whereRaw('LOWER(utm_campaign) = ?', [strtolower($conv->utm_campaign)])
                          ->orWhereRaw('LOWER(name) = ?',        [strtolower($conv->utm_campaign)]);
                    })
                    ->first();
                if ($campaign) {
                    $lead->update(['campaign_id' => $campaign->id]);
                }
            }
        }

        WebsiteConversation::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update([
                'lead_id'       => $lead->id,
                'contact_name'  => $name ?: $conv->contact_name,
                'contact_email' => $email ?: $conv->contact_email,
                'contact_phone' => $phone ?: $conv->contact_phone,
            ]);

        $conv->lead_id       = $lead->id;
        $conv->contact_name  = $name ?: $conv->contact_name;
        $conv->contact_email = $email ?: $conv->contact_email;
        $conv->contact_phone = $phone ?: $conv->contact_phone;

        Log::info('WebsiteChatService: lead criado/atualizado', [
            'conversation_id' => $conv->id,
            'lead_id'         => $lead->id,
        ]);
    }

    private function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw);
        if (strlen($digits) >= 10 && ! str_starts_with($digits, '55')) {
            $digits = '55' . $digits;
        }
        return $digits;
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
        // Prefer explicit start node (is_start = true)
        $startNode = ChatbotFlowNode::withoutGlobalScope('tenant')
            ->where('flow_id', $flowId)
            ->where('is_start', true)
            ->first();

        if ($startNode) {
            return $startNode;
        }

        // Fallback for old flows: first node with no incoming edges
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

    private function saveOutboundMessages(int $conversationId, array $replies): void
    {
        $now = now();
        foreach ($replies as $text) {
            WebsiteMessage::create([
                'conversation_id' => $conversationId,
                'direction'       => 'outbound',
                'content'         => $text,
                'sent_at'         => $now,
            ]);
        }

        if (! empty($replies)) {
            WebsiteConversation::withoutGlobalScope('tenant')
                ->where('id', $conversationId)
                ->update(['last_message_at' => $now]);
        }
    }

    private function buildVars(WebsiteConversation $conv): array
    {
        $session = $conv->chatbot_variables ?? [];
        $system  = [
            '$contact_name'  => $conv->contact_name ?? '',
            '$contact_email' => $conv->contact_email ?? '',
            '$contact_phone' => $conv->contact_phone ?? '',
            '$lead_exists'   => $conv->lead_id ? 'sim' : 'não',
        ];

        return array_merge($session, $system);
    }

    private function persistVars(WebsiteConversation $conv, array $vars): void
    {
        $session = array_filter($vars, fn ($k) => ! str_starts_with($k, '$'), ARRAY_FILTER_USE_KEY);

        WebsiteConversation::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update([
                'chatbot_node_id'   => $conv->chatbot_node_id,
                'chatbot_variables' => ! empty($session) ? json_encode($session) : null,
            ]);
    }

    private function clearFlow(WebsiteConversation $conv): void
    {
        WebsiteConversation::withoutGlobalScope('tenant')
            ->where('id', $conv->id)
            ->update([
                'chatbot_node_id'   => null,
                'chatbot_variables' => null,
            ]);
    }
}
