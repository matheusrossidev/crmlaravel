<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Models\ChatbotFlow;
use App\Models\Department;
use App\Models\User;
use App\Services\PlanLimitChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    private function authorizeAdmin(): void
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isSuperAdmin()) {
            abort(403, 'Sem permissão para gerenciar departamentos.');
        }
    }

    public function index(): View
    {
        $this->authorizeAdmin();

        $departments = Department::with(['users', 'defaultAiAgent', 'defaultChatbotFlow'])
            ->orderBy('name')
            ->get();

        $users = User::where('tenant_id', activeTenantId())
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $aiAgents = AiAgent::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $chatbotFlows = ChatbotFlow::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('tenant.settings.departments', compact(
            'departments', 'users', 'aiAgents', 'chatbotFlows'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $limitMsg = PlanLimitChecker::check('departments');
        if ($limitMsg) {
            return response()->json(['success' => false, 'message' => $limitMsg, 'limit_reached' => true], 422);
        }

        $request->validate([
            'name'                     => 'required|string|max:100',
            'description'              => 'nullable|string|max:191',
            'icon'                     => 'nullable|string|max:50',
            'color'                    => 'nullable|string|max:7',
            'default_ai_agent_id'      => 'nullable|integer|exists:ai_agents,id',
            'default_chatbot_flow_id'  => 'nullable|integer|exists:chatbot_flows,id',
            'assignment_strategy'      => 'nullable|in:round_robin,least_busy',
            'user_ids'                 => 'nullable|array',
            'user_ids.*'               => 'integer|exists:users,id',
        ]);

        $department = Department::create([
            'tenant_id'                => activeTenantId(),
            'name'                     => $request->input('name'),
            'description'              => $request->input('description'),
            'icon'                     => $request->input('icon', 'bi-building'),
            'color'                    => $request->input('color', '#3B82F6'),
            'default_ai_agent_id'      => $request->input('default_ai_agent_id'),
            'default_chatbot_flow_id'  => $request->input('default_chatbot_flow_id'),
            'assignment_strategy'      => $request->input('assignment_strategy', 'round_robin'),
        ]);

        if ($request->has('user_ids')) {
            $department->users()->sync($request->input('user_ids', []));
        }

        $department->load(['users', 'defaultAiAgent', 'defaultChatbotFlow']);

        return response()->json([
            'success'    => true,
            'department' => $this->formatDepartment($department),
        ], 201);
    }

    public function update(Request $request, Department $department): JsonResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'name'                     => 'required|string|max:100',
            'description'              => 'nullable|string|max:191',
            'icon'                     => 'nullable|string|max:50',
            'color'                    => 'nullable|string|max:7',
            'default_ai_agent_id'      => 'nullable|integer|exists:ai_agents,id',
            'default_chatbot_flow_id'  => 'nullable|integer|exists:chatbot_flows,id',
            'assignment_strategy'      => 'nullable|in:round_robin,least_busy',
            'is_active'                => 'nullable|boolean',
            'user_ids'                 => 'nullable|array',
            'user_ids.*'               => 'integer|exists:users,id',
        ]);

        $department->update([
            'name'                     => $request->input('name'),
            'description'              => $request->input('description'),
            'icon'                     => $request->input('icon', $department->icon),
            'color'                    => $request->input('color', $department->color),
            'default_ai_agent_id'      => $request->input('default_ai_agent_id'),
            'default_chatbot_flow_id'  => $request->input('default_chatbot_flow_id'),
            'assignment_strategy'      => $request->input('assignment_strategy', $department->assignment_strategy),
            'is_active'                => $request->boolean('is_active', $department->is_active),
        ]);

        if ($request->has('user_ids')) {
            $department->users()->sync($request->input('user_ids', []));
        }

        $department->load(['users', 'defaultAiAgent', 'defaultChatbotFlow']);

        return response()->json([
            'success'    => true,
            'department' => $this->formatDepartment($department),
        ]);
    }

    public function destroy(Department $department): JsonResponse
    {
        $this->authorizeAdmin();

        $department->delete();

        return response()->json(['success' => true]);
    }

    private function formatDepartment(Department $department): array
    {
        return [
            'id'                      => $department->id,
            'name'                    => $department->name,
            'description'             => $department->description,
            'icon'                    => $department->icon,
            'color'                   => $department->color,
            'default_ai_agent_id'     => $department->default_ai_agent_id,
            'default_ai_agent_name'   => $department->defaultAiAgent?->name,
            'default_chatbot_flow_id' => $department->default_chatbot_flow_id,
            'default_chatbot_flow_name' => $department->defaultChatbotFlow?->name,
            'assignment_strategy'     => $department->assignment_strategy,
            'is_active'               => $department->is_active,
            'user_ids'                => $department->users->pluck('id')->toArray(),
            'users_count'             => $department->users->count(),
        ];
    }
}
