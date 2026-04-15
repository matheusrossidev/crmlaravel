<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CsAgentController extends Controller
{
    public function index(): View
    {
        $agents = User::where('is_cs_agent', true)->with('tenant:id,name')->orderBy('name')->get();
        $tenants = Tenant::orderBy('name')->get(['id', 'name']);
        return view('master.cs-agents.index', compact('agents', 'tenants'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:8',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $user = User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'password'          => $data['password'],
            'role'              => 'viewer',
            'tenant_id'         => $data['tenant_id'] ?? null,
            'email_verified_at' => now(),
        ]);
        // is_cs_agent não é mass-assignable (proteção contra escalação)
        $user->is_cs_agent = true;
        $user->save();

        return response()->json(['success' => true, 'message' => "Agente CS {$data['name']} criado."]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        if (!$user->is_cs_agent) {
            return response()->json(['success' => false, 'message' => 'Usuário não é agente CS.'], 422);
        }

        $data = $request->validate([
            'name'      => 'sometimes|string|max:100',
            'email'     => "sometimes|email|unique:users,email,{$user->id}",
            'password'  => 'nullable|string|min:8',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $update = [];
        if (isset($data['name'])) $update['name'] = $data['name'];
        if (isset($data['email'])) $update['email'] = $data['email'];
        if (!empty($data['password'])) $update['password'] = $data['password'];
        if (array_key_exists('tenant_id', $data)) $update['tenant_id'] = $data['tenant_id'];

        $user->update($update);
        return response()->json(['success' => true, 'message' => "Agente {$user->name} atualizado."]);
    }

    public function destroy(User $user): JsonResponse
    {
        if (!$user->is_cs_agent) {
            return response()->json(['success' => false, 'message' => 'Usuário não é agente CS.'], 422);
        }
        $user->is_cs_agent = false;
        $user->save();
        return response()->json(['success' => true, 'message' => "Acesso CS removido de {$user->name}."]);
    }
}
