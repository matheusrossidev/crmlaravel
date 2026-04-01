<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('tenant.tasks.index', [
            'users'      => $users,
            'typeLabels' => Task::TYPE_LABELS,
            'typeIcons'  => Task::TYPE_ICONS,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Task::with(['lead:id,name,phone,email,company', 'assignedTo:id,name', 'createdBy:id,name'])
            ->orderBy('due_date')
            ->orderBy('due_time');

        if ($request->filled('status')) {
            if ($request->status === 'overdue') {
                $query->overdue();
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->boolean('my_tasks')) {
            $query->where('assigned_to', auth()->id());
        }

        if ($request->filled('lead_id')) {
            $query->where('lead_id', $request->lead_id);
        }

        if ($request->filled('date_from')) {
            $query->where('due_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('due_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $query->where('subject', 'like', '%' . $request->search . '%');
        }

        $tasks = $query->get()->map(fn (Task $t) => $this->format($t));

        return response()->json(['data' => $tasks]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject'                   => 'required|string|max:191',
            'description'               => 'nullable|string|max:5000',
            'type'                      => 'required|in:' . implode(',', Task::TYPES),
            'priority'                  => 'nullable|in:low,medium,high',
            'status'                    => 'nullable|in:pending,in_progress,completed,cancelled',
            'due_date'                  => 'required|date',
            'due_time'                  => 'nullable|date_format:H:i',
            'lead_id'                   => 'nullable|integer|exists:leads,id',
            'whatsapp_conversation_id'  => 'nullable|integer|exists:whatsapp_conversations,id',
            'instagram_conversation_id' => 'nullable|integer|exists:instagram_conversations,id',
            'assigned_to'               => 'nullable|integer|exists:users,id',
        ], [
            'subject.required' => 'O assunto é obrigatório.',
            'subject.max'      => 'O assunto pode ter no máximo 191 caracteres.',
            'type.required'    => 'Selecione o tipo de tarefa.',
            'type.in'          => 'Tipo de tarefa inválido.',
            'due_date.required' => 'Informe a data de vencimento.',
        ]);

        $task = Task::create([
            'tenant_id'                 => auth()->user()->tenant_id,
            'subject'                   => $data['subject'],
            'description'               => $data['description'] ?? null,
            'type'                      => $data['type'],
            'priority'                  => $data['priority'] ?? 'medium',
            'status'                    => $data['status'] ?? 'pending',
            'due_date'                  => $data['due_date'],
            'due_time'                  => $data['due_time'] ?? null,
            'lead_id'                   => $data['lead_id'] ?? null,
            'whatsapp_conversation_id'  => $data['whatsapp_conversation_id'] ?? null,
            'instagram_conversation_id' => $data['instagram_conversation_id'] ?? null,
            'assigned_to'               => $data['assigned_to'] ?? null,
            'created_by'                => auth()->id(),
        ]);

        if ($task->lead_id) {
            LeadEvent::create([
                'tenant_id'    => $task->tenant_id,
                'lead_id'      => $task->lead_id,
                'event_type'   => 'task_created',
                'description'  => 'Tarefa criada: ' . $task->subject,
                'performed_by' => auth()->id(),
                'created_at'   => now(),
            ]);
        }

        $task->load(['lead:id,name,phone,email,company', 'assignedTo:id,name']);

        return response()->json(['success' => true, 'task' => $this->format($task)], 201);
    }

    public function show(Task $task): JsonResponse
    {
        $task->load(['lead:id,name,phone,email,company', 'assignedTo:id,name', 'createdBy:id,name']);

        return response()->json(['task' => $this->format($task)]);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $data = $request->validate([
            'subject'      => 'sometimes|required|string|max:191',
            'description'  => 'nullable|string|max:5000',
            'type'         => 'sometimes|required|in:' . implode(',', Task::TYPES),
            'priority'     => 'nullable|in:low,medium,high',
            'status'       => 'nullable|in:pending,in_progress,completed,cancelled',
            'due_date'     => 'sometimes|required|date',
            'due_time'     => 'nullable|string|max:8',
            'assigned_to'  => 'nullable|integer|exists:users,id',
            'lead_id'      => 'nullable|integer|exists:leads,id',
            'notes'        => 'nullable|string|max:5000',
        ]);

        $oldStatus = $task->status;

        $task->update($data);

        if (isset($data['status']) && $data['status'] === 'completed' && $oldStatus !== 'completed') {
            $task->update(['completed_at' => now()]);
        }

        if (isset($data['status']) && $data['status'] !== 'completed' && $oldStatus === 'completed') {
            $task->update(['completed_at' => null]);
        }

        $task->load(['lead:id,name,phone,email,company', 'assignedTo:id,name']);

        return response()->json(['success' => true, 'task' => $this->format($task)]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json(['success' => true]);
    }

    public function toggleStatus(Task $task): JsonResponse
    {
        if ($task->status === 'completed') {
            $task->update(['status' => 'pending', 'completed_at' => null]);
        } else {
            $task->update(['status' => 'completed', 'completed_at' => now()]);
        }

        if ($task->lead_id) {
            LeadEvent::create([
                'tenant_id'    => $task->tenant_id,
                'lead_id'      => $task->lead_id,
                'event_type'   => 'task_updated',
                'description'  => $task->status === 'completed'
                    ? 'Tarefa concluída: ' . $task->subject
                    : 'Tarefa reaberta: ' . $task->subject,
                'performed_by' => auth()->id(),
                'created_at'   => now(),
            ]);
        }

        $task->load(['lead:id,name,phone,email,company', 'assignedTo:id,name']);

        return response()->json(['success' => true, 'task' => $this->format($task)]);
    }

    public function searchLeads(Request $request): JsonResponse
    {
        $q = $request->get('q', '');
        $leads = Lead::where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('company', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'phone', 'email', 'company']);

        return response()->json([
            'data' => $leads->map(fn (Lead $l) => [
                'id'      => $l->id,
                'name'    => $l->name,
                'phone'   => $l->phone,
                'email'   => $l->email,
                'company' => $l->company,
            ]),
        ]);
    }

    public function forLead(Lead $lead): JsonResponse
    {
        $tasks = Task::where('lead_id', $lead->id)
            ->with(['assignedTo:id,name'])
            ->orderByRaw("FIELD(status, 'pending', 'in_progress', 'completed', 'cancelled')")
            ->orderBy('due_date')
            ->get()
            ->map(fn (Task $t) => $this->format($t));

        return response()->json(['data' => $tasks]);
    }

    private function format(Task $task): array
    {
        return [
            'id'           => $task->id,
            'subject'      => $task->subject,
            'description'  => $task->description,
            'type'         => $task->type,
            'type_label'   => Task::TYPE_LABELS[$task->type] ?? $task->type,
            'type_icon'    => Task::TYPE_ICONS[$task->type] ?? 'bi-check2-square',
            'status'       => $task->status,
            'priority'     => $task->priority,
            'due_date'     => $task->due_date?->toDateString(),
            'due_date_fmt' => $task->due_date?->translatedFormat('d/m/Y'),
            'due_time'     => $task->due_time ? substr((string) $task->due_time, 0, 5) : null,
            'completed_at' => $task->completed_at?->toISOString(),
            'is_overdue'   => $task->isOverdue(),
            'urgency_color' => $task->urgencyColor(),
            'lead_id'      => $task->lead_id,
            'lead_name'    => $task->lead?->name,
            'lead_phone'   => $task->lead?->phone,
            'lead_email'   => $task->lead?->email,
            'lead_company' => $task->lead?->company,
            'assigned_to'  => $task->assigned_to,
            'assigned_name' => $task->assignedTo?->name,
            'created_by'   => $task->createdBy?->name,
            'notes'        => $task->notes,
            'created_at'   => $task->created_at?->toISOString(),
        ];
    }
}
