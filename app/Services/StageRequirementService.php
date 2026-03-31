<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\PipelineStage;
use App\Models\StageRequiredTask;
use App\Models\Task;
use Illuminate\Support\Collection;

class StageRequirementService
{
    /**
     * Create mandatory tasks for a lead entering a new stage.
     */
    public function createRequiredTasks(Lead $lead, PipelineStage $stage): void
    {
        $requirements = $stage->requiredTasks;

        if ($requirements->isEmpty()) {
            return;
        }

        foreach ($requirements as $req) {
            $subject = str_replace(
                ['{{lead_name}}', '{{stage_name}}'],
                [$lead->name ?? '', $stage->name ?? ''],
                $req->subject
            );

            $task = Task::create([
                'tenant_id'            => $lead->tenant_id,
                'lead_id'              => $lead->id,
                'subject'              => $subject,
                'description'          => $req->description,
                'type'                 => $req->task_type,
                'priority'             => $req->priority,
                'status'               => 'pending',
                'due_date'             => now()->addDays($req->due_date_offset),
                'assigned_to'          => $lead->assigned_to,
                'created_by'           => auth()->id(),
                'stage_requirement_id' => $req->id,
            ]);

            LeadEvent::create([
                'tenant_id'    => $lead->tenant_id,
                'lead_id'      => $lead->id,
                'event_type'   => 'task_created',
                'description'  => 'Atividade obrigatória criada: ' . $subject,
                'performed_by' => auth()->id(),
                'created_at'   => now(),
            ]);
        }
    }

    /**
     * Check if a lead can leave its current stage (all mandatory tasks completed).
     *
     * @return array{allowed: bool, pending: string[], total: int, completed: int}
     */
    public function canLeaveStage(Lead $lead, ?int $stageId = null): array
    {
        $stageId = $stageId ?? $lead->stage_id;

        $requirements = StageRequiredTask::where('pipeline_stage_id', $stageId)->get();

        if ($requirements->isEmpty()) {
            return ['allowed' => true, 'pending' => [], 'total' => 0, 'completed' => 0];
        }

        $reqIds = $requirements->pluck('id')->toArray();

        // Find completed tasks for this lead matching the requirements
        $completedReqIds = Task::where('lead_id', $lead->id)
            ->whereIn('stage_requirement_id', $reqIds)
            ->where('status', 'completed')
            ->pluck('stage_requirement_id')
            ->unique()
            ->toArray();

        $pending = [];
        foreach ($requirements as $req) {
            if (!in_array($req->id, $completedReqIds, true)) {
                $pending[] = $req->subject;
            }
        }

        return [
            'allowed'   => empty($pending),
            'pending'   => $pending,
            'total'     => $requirements->count(),
            'completed' => $requirements->count() - count($pending),
        ];
    }

    /**
     * Batch-get completion status for multiple leads in a stage (for Kanban performance).
     *
     * @return array<int, array{total: int, completed: int}>  keyed by lead_id
     */
    public function getCompletionStatusBatch(Collection $leads, int $stageId): array
    {
        $requirements = StageRequiredTask::where('pipeline_stage_id', $stageId)->get();

        if ($requirements->isEmpty()) {
            return [];
        }

        $reqIds = $requirements->pluck('id')->toArray();
        $total = $requirements->count();
        $leadIds = $leads->pluck('id')->toArray();

        if (empty($leadIds)) {
            return [];
        }

        // One query: count completed tasks per lead for these requirements
        $completedCounts = Task::whereIn('lead_id', $leadIds)
            ->whereIn('stage_requirement_id', $reqIds)
            ->where('status', 'completed')
            ->selectRaw('lead_id, COUNT(DISTINCT stage_requirement_id) as done')
            ->groupBy('lead_id')
            ->pluck('done', 'lead_id')
            ->toArray();

        $result = [];
        foreach ($leadIds as $id) {
            $result[$id] = [
                'total'     => $total,
                'completed' => (int) ($completedCounts[$id] ?? 0),
            ];
        }

        return $result;
    }
}
