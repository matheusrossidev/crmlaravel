<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadSequence;
use App\Models\NurtureSequence;
use App\Models\NurtureSequenceStep;
use App\Models\ScheduledMessage;
use App\Models\WhatsappConversation;
use Illuminate\Support\Facades\Log;

class NurtureSequenceService
{
    /**
     * Enroll a lead in a sequence.
     */
    public function enroll(Lead $lead, NurtureSequence $sequence): ?LeadSequence
    {
        // Already enrolled?
        $existing = LeadSequence::withoutGlobalScope('tenant')
            ->where('lead_id', $lead->id)
            ->where('sequence_id', $sequence->id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return null;
        }

        $firstStep = $sequence->steps()->orderBy('position')->first();
        if (! $firstStep) {
            return null;
        }

        $ls = LeadSequence::create([
            'tenant_id'             => $lead->tenant_id,
            'lead_id'               => $lead->id,
            'sequence_id'           => $sequence->id,
            'current_step_position' => $firstStep->position,
            'status'                => 'active',
            'next_step_at'          => now()->addMinutes($firstStep->delay_minutes),
            'started_at'            => now(),
        ]);

        NurtureSequence::withoutGlobalScope('tenant')
            ->where('id', $sequence->id)
            ->increment('stats_enrolled');

        return $ls;
    }

    /**
     * Process a single due LeadSequence — execute current step and advance.
     */
    public function processStep(LeadSequence $ls): void
    {
        $sequence = NurtureSequence::withoutGlobalScope('tenant')
            ->with('steps')
            ->find($ls->sequence_id);

        if (! $sequence || ! $sequence->is_active) {
            return;
        }

        $lead = Lead::withoutGlobalScope('tenant')->find($ls->lead_id);
        if (! $lead) {
            $ls->markExited('manual');
            return;
        }

        // ── Exit condition: lead replied or human took over ──
        if ($this->shouldExitOnReply($ls, $lead)) {
            $ls->markExited('replied');
            return;
        }

        // ── Check: IA/Chatbot active → pause, don't send ──
        $conv = $this->resolveConversation($lead);
        if ($conv && ($conv->ai_agent_id || $conv->chatbot_flow_id)) {
            // Pause — will retry next cycle
            $ls->update(['status' => 'paused']);
            return;
        }

        // Find current step
        $step = $sequence->steps
            ->where('position', $ls->current_step_position)
            ->where('is_active', true)
            ->first();

        if (! $step) {
            $ls->markCompleted();
            return;
        }

        try {
            $this->executeStep($step, $lead, $conv, $ls);
        } catch (\Throwable $e) {
            Log::warning('NurtureSequence: step execution failed', [
                'lead_sequence_id' => $ls->id,
                'step_id'          => $step->id,
                'error'            => $e->getMessage(),
            ]);
        }

        // Advance to next step
        $this->advanceToNextStep($ls, $sequence);
    }

    /**
     * Resume paused sequences where IA/Chatbot was removed.
     */
    public function resumePaused(): int
    {
        $resumed = 0;

        $paused = LeadSequence::withoutGlobalScope('tenant')
            ->where('status', 'paused')
            ->with('lead')
            ->get();

        foreach ($paused as $ls) {
            $conv = $this->resolveConversation($ls->lead);
            if ($conv && !$conv->ai_agent_id && !$conv->chatbot_flow_id) {
                $ls->update([
                    'status'      => 'active',
                    'next_step_at' => now(),
                ]);
                $resumed++;
            }
        }

        return $resumed;
    }

    /**
     * Exit all active sequences for a lead (used by webhook on reply/human msg).
     */
    public function exitAllForLead(int $leadId, string $reason): int
    {
        $active = LeadSequence::withoutGlobalScope('tenant')
            ->where('lead_id', $leadId)
            ->whereIn('status', ['active', 'paused'])
            ->get();

        foreach ($active as $ls) {
            $ls->markExited($reason);
        }

        return $active->count();
    }

    // ── Private helpers ──────────────────────────────────────────────

    private function executeStep(NurtureSequenceStep $step, Lead $lead, ?WhatsappConversation $conv, LeadSequence $ls): void
    {
        if ($lead->opted_out) {
            $ls->markExited('opted_out');
            return;
        }

        match ($step->type) {
            'message'    => $this->executeMessage($step, $lead, $conv),
            'action'     => $this->executeAction($step, $lead),
            'wait_reply' => $this->executeWaitReply($step, $ls),
            'condition'  => $this->executeCondition($step, $lead, $ls),
            default      => null,
        };
    }

    private function executeMessage(NurtureSequenceStep $step, Lead $lead, ?WhatsappConversation $conv): void
    {
        if (! $conv) {
            return;
        }

        $config = $step->config;
        $body   = $this->interpolate($config['body'] ?? '', $lead);

        if (empty($body)) {
            return;
        }

        ScheduledMessage::create([
            'tenant_id'       => $lead->tenant_id,
            'lead_id'         => $lead->id,
            'conversation_id' => $conv->id,
            'created_by'      => null,
            'type'            => $config['media_type'] ?? 'text',
            'body'            => $body,
            'media_path'      => $config['media_path'] ?? null,
            'media_mime'      => $config['media_mime'] ?? null,
            'media_filename'  => $config['media_filename'] ?? null,
            'send_at'         => now(),
            'status'          => 'pending',
        ]);
    }

    private function executeAction(NurtureSequenceStep $step, Lead $lead): void
    {
        $config     = $step->config;
        $actionType = $config['type'] ?? '';
        $params     = $config['params'] ?? [];

        match ($actionType) {
            'move_stage' => $lead->update(['stage_id' => $params['stage_id'] ?? $lead->stage_id]),
            'add_tag'    => $lead->update(['tags' => array_unique(array_merge($lead->tags ?? [], [$params['tag'] ?? '']))]),
            'assign_user' => $lead->update(['assigned_to' => $params['user_id'] ?? $lead->assigned_to]),
            default => null,
        };
    }

    private function executeWaitReply(NurtureSequenceStep $step, LeadSequence $ls): void
    {
        $timeout = $step->config['timeout_minutes'] ?? 1440; // default 24h
        $ls->update(['next_step_at' => now()->addMinutes($timeout)]);
    }

    private function executeCondition(NurtureSequenceStep $step, Lead $lead, LeadSequence $ls): void
    {
        $config   = $step->config;
        $field    = $config['field'] ?? '';
        $operator = $config['operator'] ?? 'equals';
        $value    = $config['value'] ?? '';

        $actual = match ($field) {
            'score'   => $lead->score,
            'source'  => $lead->source,
            'has_email' => !empty($lead->email),
            'has_company' => !empty($lead->company),
            default   => null,
        };

        $passes = match ($operator) {
            'equals'   => $actual == $value,
            'gt'       => $actual > $value,
            'lt'       => $actual < $value,
            'contains' => is_string($actual) && str_contains($actual, (string) $value),
            default    => true,
        };

        if (! $passes && !empty($config['step_if_false'])) {
            $ls->update(['current_step_position' => (int) $config['step_if_false']]);
        }
    }

    private function advanceToNextStep(LeadSequence $ls, NurtureSequence $sequence): void
    {
        $steps   = $sequence->steps->sortBy('position');
        $current = $ls->current_step_position;

        $nextStep = $steps->first(fn ($s) => $s->position > $current && $s->is_active);

        if (! $nextStep) {
            $ls->markCompleted();
            return;
        }

        $ls->update([
            'current_step_position' => $nextStep->position,
            'next_step_at'          => now()->addMinutes($nextStep->delay_minutes),
        ]);
    }

    private function shouldExitOnReply(LeadSequence $ls, Lead $lead): bool
    {
        $conv = $this->resolveConversation($lead);
        if (! $conv) {
            return false;
        }

        // Check if there's an inbound message after the sequence started
        $hasReply = $conv->messages()
            ->where('direction', 'inbound')
            ->where('sent_at', '>', $ls->started_at)
            ->exists();

        if ($hasReply) {
            return true;
        }

        // Check if a human sent a manual outbound message (user_id not null)
        $hasHumanMsg = $conv->messages()
            ->where('direction', 'outbound')
            ->whereNotNull('user_id')
            ->where('sent_at', '>', $ls->started_at)
            ->exists();

        return $hasHumanMsg;
    }

    private function resolveConversation(Lead $lead): ?WhatsappConversation
    {
        return WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $lead->tenant_id)
            ->where('lead_id', $lead->id)
            ->latest('last_message_at')
            ->first();
    }

    private function interpolate(string $text, Lead $lead): string
    {
        $vars = [
            '{{nome}}'     => $lead->name ?? '',
            '{{name}}'     => $lead->name ?? '',
            '{{phone}}'    => $lead->phone ?? '',
            '{{email}}'    => $lead->email ?? '',
            '{{empresa}}'  => $lead->company ?? '',
            '{{company}}'  => $lead->company ?? '',
            '{{pipeline}}' => $lead->pipeline?->name ?? '',
            '{{etapa}}'    => $lead->stage?->name ?? '',
            '{{stage}}'    => $lead->stage?->name ?? '',
            '{{score}}'    => (string) ($lead->score ?? 0),
        ];

        return str_replace(array_keys($vars), array_values($vars), $text);
    }
}
