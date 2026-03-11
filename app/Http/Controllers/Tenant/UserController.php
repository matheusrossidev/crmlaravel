<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Models\Department;
use App\Models\Pipeline;
use App\Models\User;
use App\Services\PlanLimitChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    private function authorizeAdmin(): void
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isSuperAdmin()) {
            abort(403, 'Sem permissão para gerenciar usuários.');
        }
    }

    public function index(): View
    {
        $this->authorizeAdmin();

        $users = User::where('tenant_id', auth()->user()->tenant_id)
            ->with(['departments', 'pipelines'])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        $pipelines = Pipeline::orderBy('sort_order')->get(['id', 'name']);

        return view('tenant.settings.users', compact('users', 'departments', 'pipelines'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $authUser = auth()->user();
        $tenant   = $authUser->tenant;

        $request->validate([
            'name'                       => 'required|string|max:100',
            'email'                      => 'required|email|max:150|unique:users,email',
            'password'                   => 'required|string|min:8',
            'role'                       => 'required|in:admin,manager,viewer',
            'department_ids'             => 'nullable|array',
            'department_ids.*'           => 'integer|exists:departments,id',
            'pipeline_ids'               => 'nullable|array',
            'pipeline_ids.*'             => 'integer|exists:pipelines,id',
            'can_see_all_conversations'  => 'nullable|boolean',
        ]);

        $role = $request->input('role');

        // Admin não pode criar role admin
        if (!$authUser->isSuperAdmin() && $role === 'admin') {
            return response()->json([
                'success' => false,
                'errors'  => ['role' => ['Você não tem permissão para atribuir este papel.']],
            ], 403);
        }

        $limitMsg = PlanLimitChecker::check('users');
        if ($limitMsg) {
            return response()->json(['success' => false, 'message' => $limitMsg, 'limit_reached' => true], 422);
        }

        $token = Str::random(64);

        $user = User::create([
            'tenant_id'                      => $authUser->tenant_id,
            'name'                           => $request->input('name'),
            'email'                          => $request->input('email'),
            'password'                       => $request->input('password'),
            'role'                           => $role,
            'can_see_all_conversations'      => $request->boolean('can_see_all_conversations', true),
            'verification_token'             => $token,
            'verification_token_expires_at'  => now()->addHours(48),
        ]);

        if ($request->has('department_ids')) {
            $user->departments()->sync($request->input('department_ids', []));
        }

        $user->pipelines()->sync($request->input('pipeline_ids', []));

        try {
            Mail::to($user->email)->send(new VerifyEmail($user, $tenant));
        } catch (\Throwable $e) {
            \Log::warning('Falha ao enviar email de verificação', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'user'    => [
                'id'                          => $user->id,
                'name'                        => $user->name,
                'email'                       => $user->email,
                'role'                        => $user->role,
                'can_see_all_conversations'   => $user->can_see_all_conversations,
                'department_ids'              => $user->departments->pluck('id')->toArray(),
                'pipeline_ids'                => $user->pipelines->pluck('id')->toArray(),
                'created_at'                  => $user->created_at->format('d/m/Y'),
            ],
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin();

        $authUser = auth()->user();

        // Só pode editar usuários do próprio tenant
        if ($user->tenant_id !== $authUser->tenant_id) {
            abort(403);
        }

        // Impedir que o usuário altere seu próprio role
        if ($user->id === $authUser->id && $request->input('role') !== $user->role) {
            return response()->json([
                'success' => false,
                'errors'  => ['role' => ['Você não pode alterar seu próprio papel.']],
            ], 403);
        }

        $request->validate([
            'name'                       => 'required|string|max:100',
            'email'                      => 'required|email|max:150|unique:users,email,' . $user->id,
            'role'                       => 'required|in:admin,manager,viewer',
            'department_ids'             => 'nullable|array',
            'department_ids.*'           => 'integer|exists:departments,id',
            'pipeline_ids'               => 'nullable|array',
            'pipeline_ids.*'             => 'integer|exists:pipelines,id',
            'can_see_all_conversations'  => 'nullable|boolean',
        ]);

        $role = $request->input('role');

        if (!$authUser->isSuperAdmin() && $role === 'admin') {
            return response()->json([
                'success' => false,
                'errors'  => ['role' => ['Você não tem permissão para atribuir este papel.']],
            ], 403);
        }

        $user->update([
            'name'                       => $request->input('name'),
            'email'                      => $request->input('email'),
            'role'                       => $role,
            'can_see_all_conversations'  => $request->boolean('can_see_all_conversations', $user->can_see_all_conversations),
        ]);

        if ($request->has('department_ids')) {
            $user->departments()->sync($request->input('department_ids', []));
        }

        $user->pipelines()->sync($request->input('pipeline_ids', []));

        return response()->json(['success' => true, 'message' => 'Usuário atualizado.']);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorizeAdmin();

        $authUser = auth()->user();

        if ($user->id === $authUser->id) {
            return response()->json(['success' => false, 'message' => 'Você não pode deletar seu próprio usuário.'], 422);
        }

        if ($user->tenant_id !== $authUser->tenant_id) {
            abort(403);
        }

        $user->delete();

        return response()->json(['success' => true]);
    }
}
