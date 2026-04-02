<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Automation;
use App\Models\CustomFieldDefinition;
use App\Models\Lead;
use App\Models\NurtureSequence;
use App\Models\NurtureSequenceStep;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Sale;
use App\Models\ScoringRule;
use App\Models\Task;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SophiaActionExecutor
{
    /** Only these action types can be executed — hardcoded whitelist. */
    private const ALLOWED_ACTIONS = [
        'create_scoring_rule',
        'create_sequence',
        'create_pipeline',
        'create_automation',
        'create_custom_field',
        'create_task',
        'create_lead',
        'query_leads',
        'query_performance',
    ];

    private const READ_ONLY_ACTIONS = ['query_leads', 'query_performance'];

    private const RATE_LIMIT_MAX  = 10;
    private const RATE_LIMIT_MINS = 1;

    /**
     * Execute a batch of actions after user confirmation.
     *
     * @return array{success: bool, results: array, message: string}
     */
    public function executeBatch(array $actions, int $tenantId, int $userId): array
    {
        // Rate limit check
        $cacheKey = "sophia:actions:{$tenantId}";
        $count = (int) Cache::get($cacheKey, 0);
        if ($count >= self::RATE_LIMIT_MAX) {
            return [
                'success' => false,
                'results' => [],
                'message' => 'Rate limit exceeded. Max ' . self::RATE_LIMIT_MAX . ' actions per minute.',
            ];
        }

        $results   = [];
        $succeeded = 0;
        $failed    = 0;

        foreach ($actions as $action) {
            $type    = $action['type'] ?? '';
            $payload = $action;
            unset($payload['type']);

            $result = $this->execute($type, $payload, $tenantId, $userId);
            $results[] = array_merge(['type' => $type], $result);

            if ($result['success']) {
                $succeeded++;
            } else {
                $failed++;
            }
        }

        // Increment rate limit
        Cache::put($cacheKey, $count + count($actions), now()->addMinutes(self::RATE_LIMIT_MINS));

        return [
            'success' => $failed === 0,
            'results' => $results,
            'message' => "{$succeeded} action(s) executed" . ($failed > 0 ? ", {$failed} failed" : ''),
        ];
    }

    /**
     * Execute a single action.
     */
    public function execute(string $type, array $payload, int $tenantId, int $userId): array
    {
        if (!in_array($type, self::ALLOWED_ACTIONS, true)) {
            return ['success' => false, 'message' => "Action '{$type}' not allowed."];
        }

        try {
            $result = match ($type) {
                'create_scoring_rule' => $this->createScoringRule($payload),
                'create_sequence'     => $this->createSequence($payload),
                'create_pipeline'     => $this->createPipeline($payload),
                'create_automation'   => $this->createAutomation($payload),
                'create_custom_field' => $this->createCustomField($payload),
                'create_task'         => $this->createTask($payload, $userId),
                'create_lead'         => $this->createLead($payload, $userId),
                'query_leads'         => $this->queryLeads($payload),
                'query_performance'   => $this->queryPerformance($tenantId),
                default               => ['success' => false, 'message' => 'Unknown action.'],
            };

            // Audit log
            Log::info('Sophia action executed', [
                'tenant_id' => $tenantId,
                'user_id'   => $userId,
                'type'      => $type,
                'success'   => $result['success'],
                'payload'   => $payload,
            ]);

            return $result;
        } catch (\Throwable $e) {
            Log::error('Sophia action failed', [
                'tenant_id' => $tenantId,
                'user_id'   => $userId,
                'type'      => $type,
                'error'     => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Check if an action type requires user confirmation.
     */
    public static function needsConfirmation(string $type): bool
    {
        return !in_array($type, self::READ_ONLY_ACTIONS, true);
    }

    // ── Executors ────────────────────────────────────────────────────────────

    private function createScoringRule(array $p): array
    {
        $v = Validator::make($p, [
            'name'           => 'required|string|max:100',
            'category'       => 'required|in:engagement,pipeline,profile',
            'event_type'     => 'required|string|max:50',
            'points'         => 'required|integer|min:-100|max:100',
            'cooldown_hours' => 'nullable|integer|min:0|max:720',
        ]);

        if ($v->fails()) {
            return ['success' => false, 'message' => $v->errors()->first()];
        }

        $rule = ScoringRule::create([
            'name'           => $p['name'],
            'category'       => $p['category'],
            'event_type'     => $p['event_type'],
            'points'         => (int) $p['points'],
            'is_active'      => true,
            'cooldown_hours' => (int) ($p['cooldown_hours'] ?? 0),
            'sort_order'     => ScoringRule::max('sort_order') + 1,
        ]);

        return ['success' => true, 'message' => "Scoring rule '{$rule->name}' created.", 'id' => $rule->id];
    }

    private function createSequence(array $p): array
    {
        $v = Validator::make($p, [
            'name'                  => 'required|string|max:100',
            'description'           => 'nullable|string|max:191',
            'steps'                 => 'required|array|min:1',
            'steps.*.type'          => 'required|in:message,wait_reply,condition,action',
            'steps.*.delay_minutes' => 'required|integer|min:0',
            'steps.*.config'        => 'required|array',
        ]);

        if ($v->fails()) {
            return ['success' => false, 'message' => $v->errors()->first()];
        }

        return DB::transaction(function () use ($p) {
            $seq = NurtureSequence::create([
                'name'                 => $p['name'],
                'description'          => $p['description'] ?? null,
                'is_active'            => false,
                'exit_on_reply'        => $p['exit_on_reply'] ?? true,
                'exit_on_stage_change' => $p['exit_on_stage_change'] ?? false,
            ]);

            foreach ($p['steps'] as $i => $step) {
                NurtureSequenceStep::create([
                    'sequence_id'   => $seq->id,
                    'position'      => $i + 1,
                    'type'          => $step['type'],
                    'delay_minutes' => (int) $step['delay_minutes'],
                    'config'        => $step['config'],
                ]);
            }

            return ['success' => true, 'message' => "Sequence '{$seq->name}' created with " . count($p['steps']) . " steps.", 'id' => $seq->id];
        });
    }

    private function createPipeline(array $p): array
    {
        $v = Validator::make($p, [
            'name'            => 'required|string|max:100',
            'stages'          => 'required|array|min:1',
            'stages.*.name'   => 'required|string|max:100',
            'stages.*.color'  => 'nullable|string|max:20',
        ]);

        if ($v->fails()) {
            return ['success' => false, 'message' => $v->errors()->first()];
        }

        return DB::transaction(function () use ($p) {
            $pipeline = Pipeline::create([
                'name'       => $p['name'],
                'color'      => $p['color'] ?? '#0085f3',
                'sort_order' => Pipeline::max('sort_order') + 1,
                'is_default' => false,
            ]);

            foreach ($p['stages'] as $i => $stage) {
                PipelineStage::create([
                    'pipeline_id' => $pipeline->id,
                    'name'        => $stage['name'],
                    'color'       => $stage['color'] ?? '#6b7280',
                    'position'    => $i + 1,
                    'is_won'      => $stage['is_won'] ?? false,
                    'is_lost'     => $stage['is_lost'] ?? false,
                ]);
            }

            return ['success' => true, 'message' => "Pipeline '{$pipeline->name}' created with " . count($p['stages']) . " stages.", 'id' => $pipeline->id];
        });
    }

    private function createAutomation(array $p): array
    {
        $v = Validator::make($p, [
            'name'         => 'required|string|max:100',
            'trigger_type' => 'required|in:message_received,conversation_created,lead_created,lead_stage_changed,lead_won,lead_lost,date_field',
            'actions'      => 'required|array|min:1',
        ]);

        if ($v->fails()) {
            return ['success' => false, 'message' => $v->errors()->first()];
        }

        $automation = Automation::create([
            'name'           => $p['name'],
            'trigger_type'   => $p['trigger_type'],
            'trigger_config' => $p['trigger_config'] ?? null,
            'conditions'     => $p['conditions'] ?? null,
            'actions'        => $p['actions'],
            'is_active'      => true,
        ]);

        return ['success' => true, 'message' => "Automation '{$automation->name}' created.", 'id' => $automation->id];
    }

    private function createCustomField(array $p): array
    {
        $v = Validator::make($p, [
            'name'       => 'required|string|max:50',
            'label'      => 'required|string|max:100',
            'field_type' => 'required|in:text,textarea,number,currency,date,select,multiselect,checkbox,url,phone,email',
        ]);

        if ($v->fails()) {
            return ['success' => false, 'message' => $v->errors()->first()];
        }

        $field = CustomFieldDefinition::create([
            'name'         => $p['name'],
            'label'        => $p['label'],
            'field_type'   => $p['field_type'],
            'options_json' => $p['options'] ?? null,
            'is_active'    => true,
            'sort_order'   => CustomFieldDefinition::max('sort_order') + 1,
        ]);

        return ['success' => true, 'message' => "Custom field '{$field->label}' created.", 'id' => $field->id];
    }

    private function createTask(array $p, int $userId): array
    {
        $v = Validator::make($p, [
            'subject'  => 'required|string|max:191',
            'type'     => 'required|in:call,email,task,visit,whatsapp,meeting',
            'due_date' => 'required|date',
        ]);

        if ($v->fails()) {
            return ['success' => false, 'message' => $v->errors()->first()];
        }

        $task = Task::create([
            'subject'     => $p['subject'],
            'description' => $p['description'] ?? null,
            'type'        => $p['type'],
            'priority'    => $p['priority'] ?? 'medium',
            'status'      => 'pending',
            'due_date'    => $p['due_date'],
            'due_time'    => $p['due_time'] ?? null,
            'assigned_to' => $p['assigned_to'] ?? $userId,
            'created_by'  => $userId,
        ]);

        return ['success' => true, 'message' => "Task '{$task->subject}' created.", 'id' => $task->id];
    }

    private function createLead(array $p, int $userId): array
    {
        $v = Validator::make($p, [
            'name' => 'required|string|max:255',
        ]);

        if ($v->fails()) {
            return ['success' => false, 'message' => $v->errors()->first()];
        }

        // Use default pipeline if not specified
        $pipelineId = $p['pipeline_id'] ?? Pipeline::where('is_default', true)->first()?->id ?? Pipeline::first()?->id;
        $stageId    = $p['stage_id'] ?? PipelineStage::where('pipeline_id', $pipelineId)->orderBy('position')->first()?->id;

        if (!$pipelineId || !$stageId) {
            return ['success' => false, 'message' => 'No pipeline configured. Create a pipeline first.'];
        }

        $lead = Lead::create([
            'name'        => $p['name'],
            'phone'       => $p['phone'] ?? null,
            'email'       => $p['email'] ?? null,
            'company'     => $p['company'] ?? null,
            'source'      => $p['source'] ?? 'sophia',
            'pipeline_id' => $pipelineId,
            'stage_id'    => $stageId,
            'created_by'  => $userId,
        ]);

        return ['success' => true, 'message' => "Lead '{$lead->name}' created.", 'id' => $lead->id];
    }

    // ── Read-only queries (no confirmation needed) ───────────────────────────

    private function queryLeads(array $p): array
    {
        $query = Lead::where('status', '!=', 'merged')->orderByDesc('created_at');

        if (!empty($p['search'])) {
            $s = $p['search'];
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%"));
        }

        if (!empty($p['stage_id'])) {
            $query->where('stage_id', $p['stage_id']);
        }

        $leads = $query->limit(10)->get(['id', 'name', 'phone', 'email', 'company', 'source', 'stage_id', 'created_at']);

        return [
            'success' => true,
            'message' => $leads->count() . ' lead(s) found.',
            'data'    => $leads->map(fn ($l) => [
                'id'      => $l->id,
                'name'    => $l->name,
                'phone'   => $l->phone,
                'email'   => $l->email,
                'company' => $l->company,
                'stage'   => $l->stage?->name,
                'created' => $l->created_at?->format('d/m/Y'),
            ])->toArray(),
        ];
    }

    private function queryPerformance(int $tenantId): array
    {
        $startOfMonth = now()->startOfMonth();

        $leadsCount  = Lead::where('status', '!=', 'merged')->where('created_at', '>=', $startOfMonth)->count();
        $salesCount  = Sale::where('closed_at', '>=', $startOfMonth)->count();
        $salesValue  = Sale::where('closed_at', '>=', $startOfMonth)->sum('value');
        $convRate    = $leadsCount > 0 ? round(($salesCount / $leadsCount) * 100, 1) : 0;

        return [
            'success' => true,
            'message' => "Month performance: {$leadsCount} leads, {$salesCount} sales, R$" . number_format((float) $salesValue, 2, ',', '.'),
            'data'    => [
                'leads_count'     => $leadsCount,
                'sales_count'     => $salesCount,
                'sales_value'     => (float) $salesValue,
                'conversion_rate' => $convRate,
                'period'          => $startOfMonth->format('Y-m'),
            ],
        ];
    }
}
