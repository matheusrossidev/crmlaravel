<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

        $token = Str::random(64);

        $user = User::create([
            'tenant_id'          => $tenant->id,
            'name'               => $data['name'],
            'email'              => $data['email'],
            'password'           => $data['password'],
            'role'               => $data['role'],
            'verification_token' => $token,
        ]);

        Mail::to($user->email)->send(new VerifyEmail($user, $tenant));

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

    public function update(Tenant $tenant, Request $request, int $user): JsonResponse
    {
        $userModel = User::findOrFail($user);

        if ((int) $userModel->tenant_id !== (int) $tenant->id) {
            return response()->json(['success' => false, 'message' => 'Usuário não pertence a esta empresa.'], 404);
        }

        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150|unique:users,email,' . $userModel->id,
            'role'     => 'required|in:admin,manager,viewer',
            'password' => 'nullable|string|min:8',
        ]);

        $update = [
            'name'  => $data['name'],
            'email' => $data['email'],
            'role'  => $data['role'],
        ];

        if (! empty($data['password'])) {
            $update['password'] = $data['password'];
        }

        $userModel->update($update);

        return response()->json(['success' => true, 'message' => 'Usuário atualizado.']);
    }

    public function destroy(Tenant $tenant, int $user): JsonResponse
    {
        $userModel = User::findOrFail($user);

        if ((int) $userModel->tenant_id !== (int) $tenant->id) {
            return response()->json(['success' => false, 'message' => 'Usuário não pertence a esta empresa.'], 404);
        }

        if ($userModel->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Você não pode excluir sua própria conta.'], 422);
        }

        try {
            $userModel->delete();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao excluir usuário: ' . $e->getMessage()], 500);
        }
    }
}
