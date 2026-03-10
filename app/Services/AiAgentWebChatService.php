<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Controllers\Tenant\AiConfigurationController;
use App\Models\AiAgent;
use App\Models\AiUsageLog;
use App\Models\Lead;
use App\Models\PlanDefinition;
use App\Models\Tenant;
use App\Models\TenantTokenIncrement;
use App\Models\WebsiteConversation;
use App\Models\WebsiteMessage;
use Illuminate\Support\Facades\Log;

class AiAgentWebChatService
{
    /**
     * Process a web chat message synchronously via LLM.
     * Returns array compatible with widget.js response format.
     */
    public function processMessage(WebsiteConversation $conv, AiAgent $agent, string $userMessage): array
    {
        $fallback = ['replies' => ['Desculpe, estou com dificuldades no momento. Tente novamente em instantes.']];

        try {
            // Check token quota
            if (! $this->checkTokenQuota($conv->tenant_id)) {
                return ['replies' => ['Nosso atendimento automático está temporariamente indisponível. Por favor, tente novamente mais tarde.']];
            }

            // Build conversation history from WebsiteMessages
            $history = $this->buildHistory($conv);

            // Add the current user message
            $history[] = ['role' => 'user', 'content' => $userMessage];

            if (empty($history)) {
                return $fallback;
            }

            // Build system prompt for web chat
            $service = new AiAgentService();
            $system  = $service->buildWebChatSystemPrompt($agent);

            // LLM config
            $provider = (string) config('ai.provider', 'openai');
            $apiKey   = (string) config('ai.api_key', '');
            $model    = (string) config('ai.model', 'gpt-4o-mini');

            if ($apiKey === '') {
                Log::warning('AiAgentWebChat: LLM_API_KEY not configured');
                return $fallback;
            }

            $maxLength = max(200, $agent->max_message_length ?? 500);
            $maxTokens = $maxLength + 400; // extra for buttons/cards JSON

            $llmResult = AiConfigurationController::callLlm(
                provider:  $provider,
                apiKey:    $apiKey,
                model:     $model,
                messages:  $history,
                maxTokens: $maxTokens,
                system:    $system,
                forceJson: true,
            );

            $reply    = trim($llmResult['reply']);
            $llmUsage = $llmResult['usage'];

            // Log token usage
            try {
                AiUsageLog::create([
                    'tenant_id'         => $conv->tenant_id,
                    'conversation_id'   => $conv->id,
                    'model'             => $model,
                    'provider'          => $provider,
                    'tokens_prompt'     => $llmUsage['prompt'] ?? 0,
                    'tokens_completion' => $llmUsage['completion'] ?? 0,
                    'tokens_total'      => $llmUsage['total'] ?? 0,
                    'type'              => 'web_chat',
                ]);
            } catch (\Throwable $e) {
                Log::warning('AiAgentWebChat: failed to log token usage', ['error' => $e->getMessage()]);
            }

            if ($reply === '') {
                return $fallback;
            }

            // Parse JSON response
            $decoded = $this->parseJsonResponse($reply);

            $replyText = $decoded['reply'] ?? $reply;
            $buttons   = $decoded['buttons'] ?? [];
            $cards     = $decoded['cards'] ?? [];
            $inputType = $decoded['input_type'] ?? 'text';
            $actions   = $decoded['actions'] ?? [];

            // Process actions (set_stage, add_tags, notify_intent, assign_human)
            $this->processActions($actions, $conv, $agent);

            // Save outbound message
            $replyStr = is_array($replyText) ? implode("\n\n", $replyText) : (string) $replyText;
            WebsiteMessage::create([
                'conversation_id' => $conv->id,
                'direction'       => 'outbound',
                'content'         => $replyStr,
                'sent_at'         => now(),
            ]);

            // Build replies array (split into multiple bubbles if array)
            $replies = is_array($replyText) ? array_values(array_filter(array_map('trim', $replyText))) : [$replyStr];

            $result = ['replies' => $replies];
            if (! empty($buttons))  $result['buttons']    = $buttons;
            if (! empty($cards))    $result['cards']      = $cards;
            if ($inputType !== 'text') $result['input_type'] = $inputType;

            return $result;

        } catch (\Throwable $e) {
            Log::error('AiAgentWebChat: error processing message', [
                'conversation_id' => $conv->id,
                'error'           => $e->getMessage(),
                'file'            => $e->getFile() . ':' . $e->getLine(),
            ]);
            return $fallback;
        }
    }

    /**
     * Build conversation history from WebsiteMessages.
     */
    private function buildHistory(WebsiteConversation $conv, int $limit = 40): array
    {
        $messages = WebsiteMessage::where('conversation_id', $conv->id)
            ->orderBy('sent_at')
            ->limit($limit)
            ->get();

        $history = [];
        foreach ($messages as $msg) {
            $role = $msg->direction === 'inbound' ? 'user' : 'assistant';
            $history[] = ['role' => $role, 'content' => $msg->content];
        }

        return $history;
    }

    /**
     * Parse the JSON response from the LLM, handling edge cases.
     */
    private function parseJsonResponse(string $reply): ?array
    {
        // Strip markdown code fences
        $clean = preg_replace('/```(?:json)?\s*([\s\S]*?)```/i', '$1', $reply);
        $clean = trim($clean ?? $reply);

        // Find JSON start
        if (! str_starts_with($clean, '{')) {
            $jsonStart = strpos($clean, '{');
            if ($jsonStart !== false) {
                $clean = substr($clean, $jsonStart);
            }
        }

        if (str_starts_with($clean, '{')) {
            $decoded = json_decode($clean, true);
            if (is_array($decoded) && isset($decoded['reply'])) {
                return $decoded;
            }
        }

        // If not valid JSON, return as plain text
        return ['reply' => $reply];
    }

    /**
     * Process actions from the LLM response (set_stage, add_tags, etc.)
     */
    private function processActions(array $actions, WebsiteConversation $conv, AiAgent $agent): void
    {
        if (empty($actions)) return;

        foreach ($actions as $action) {
            $type = $action['type'] ?? '';

            if ($type === 'set_stage' && $conv->lead_id) {
                $stageId = (int) ($action['stage_id'] ?? 0);
                if ($stageId > 0) {
                    try {
                        $lead = Lead::withoutGlobalScope('tenant')->find($conv->lead_id);
                        if ($lead) {
                            $lead->update(['stage_id' => $stageId]);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('AiAgentWebChat: set_stage failed', ['error' => $e->getMessage()]);
                    }
                }
            } elseif ($type === 'add_tags' && $conv->lead_id) {
                $tags = (array) ($action['tags'] ?? []);
                if (! empty($tags)) {
                    try {
                        $lead = Lead::withoutGlobalScope('tenant')->find($conv->lead_id);
                        if ($lead) {
                            $existing = $lead->tags ?? [];
                            $merged   = array_values(array_unique(array_merge($existing, $tags)));
                            $lead->update(['tags' => $merged]);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('AiAgentWebChat: add_tags failed', ['error' => $e->getMessage()]);
                    }
                }
            } elseif ($type === 'assign_human') {
                try {
                    WebsiteConversation::withoutGlobalScope('tenant')
                        ->where('id', $conv->id)
                        ->update(['status' => 'open']);
                } catch (\Throwable $e) {
                    Log::warning('AiAgentWebChat: assign_human failed', ['error' => $e->getMessage()]);
                }
            } elseif ($type === 'notify_intent') {
                // Could fire an event similar to WhatsApp intent detection
                Log::info('AiAgentWebChat: intent detected', [
                    'conversation_id' => $conv->id,
                    'intent'          => $action['intent'] ?? 'unknown',
                    'context'         => $action['context'] ?? '',
                ]);
            }
        }
    }

    /**
     * Check if the tenant has available token quota.
     */
    private function checkTokenQuota(int $tenantId): bool
    {
        $tenant = Tenant::withoutGlobalScope('tenant')->find($tenantId);
        if (! $tenant) return true;

        if ($tenant->isExemptFromBilling()) return true;

        $plan = PlanDefinition::where('name', $tenant->plan)->first();
        $base = (int) ($plan?->features_json['ai_tokens_monthly'] ?? 0);

        if ($base === 0) return false;

        $extra = (int) TenantTokenIncrement::where('tenant_id', $tenant->id)
            ->where('status', 'paid')
            ->whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month)
            ->sum('tokens_added');

        $limit = $base + $extra;

        $used = (int) AiUsageLog::where('tenant_id', $tenant->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('tokens_total');

        if ($used >= $limit) {
            if (! $tenant->ai_tokens_exhausted) {
                Tenant::withoutGlobalScope('tenant')
                    ->where('id', $tenant->id)
                    ->update(['ai_tokens_exhausted' => true]);
            }
            return false;
        }

        return true;
    }
}
