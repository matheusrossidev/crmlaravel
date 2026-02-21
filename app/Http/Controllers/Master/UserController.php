<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store(Tenant $tenant, Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:admin,manager,viewer',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => $data['password'],
            'role'      => $data['role'],
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

    public function update(Tenant $tenant, User $user, Request $request): JsonResponse
    {
        if ($user->tenant_id !== $tenant->id) {
            abort(404);
        }

        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150|unique:users,email,' . $user->id,
            'role'     => 'required|in:admin,manager,viewer',
            'password' => 'nullable|string|min:8',
        ]);

        $update = [
            'name'  => $data['name'],
            'email' => $data['email'],
            'role'  => $data['role'],
        ];

        if (!empty($data['password'])) {
            $update['password'] = $data['password'];
        }

        $user->update($update);

        return response()->json(['success' => true, 'message' => 'Usuário atualizado.']);
    }

    public function destroy(Tenant $tenant, User $user): JsonResponse
    {
        if ($user->tenant_id !== $tenant->id) {
            abort(404);
        }

        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Você não pode excluir sua própria conta.'], 422);
        }

        $user->delete();

        return response()->json(['success' => true]);
    }
}
