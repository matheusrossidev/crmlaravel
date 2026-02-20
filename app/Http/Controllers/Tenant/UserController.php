<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('tenant.settings.users', compact('users'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $authUser = auth()->user();
        $tenant   = $authUser->tenant;

        // Validação de campos
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:admin,manager,viewer',
        ]);

        $role = $request->input('role');

        // Admin não pode criar role admin
        if (!$authUser->isSuperAdmin() && $role === 'admin') {
            return response()->json([
                'success' => false,
                'errors'  => ['role' => ['Você não tem permissão para atribuir este papel.']],
            ], 403);
        }

        // Verificar limite de usuários
        if ($tenant && $tenant->max_users > 0) {
            $currentCount = User::where('tenant_id', $tenant->id)->count();
            if ($currentCount >= $tenant->max_users) {
                return response()->json([
                    'success' => false,
                    'message' => 'Limite de usuários atingido para este plano.',
                ], 422);
            }
        }

        $user = User::create([
            'tenant_id' => $authUser->tenant_id,
            'name'      => $request->input('name'),
            'email'     => $request->input('email'),
            'password'  => $request->input('password'),
            'role'      => $role,
        ]);

        return response()->json([
            'success' => true,
            'user'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role,
                'created_at' => $user->created_at->format('d/m/Y'),
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

        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email,' . $user->id,
            'role'  => 'required|in:admin,manager,viewer',
        ]);

        $role = $request->input('role');

        if (!$authUser->isSuperAdmin() && $role === 'admin') {
            return response()->json([
                'success' => false,
                'errors'  => ['role' => ['Você não tem permissão para atribuir este papel.']],
            ], 403);
        }

        $user->update([
            'name'  => $request->input('name'),
            'email' => $request->input('email'),
            'role'  => $role,
        ]);

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
