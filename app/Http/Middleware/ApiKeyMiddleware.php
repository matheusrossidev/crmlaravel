<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawKey = $request->header('X-API-Key');

        if (!$rawKey) {
            return response()->json(['message' => 'API Key não fornecida.'], 401);
        }

        $keyHash = hash('sha256', $rawKey);
        $apiKey = ApiKey::where('key_hash', $keyHash)
            ->where('is_active', true)
            ->first();

        if (!$apiKey) {
            return response()->json(['message' => 'API Key inválida.'], 401);
        }

        if ($apiKey->expires_at && $apiKey->expires_at->isPast()) {
            return response()->json(['message' => 'API Key expirada.'], 401);
        }

        $tenant = Tenant::find($apiKey->tenant_id);
        if (!$tenant || $tenant->status !== 'active') {
            return response()->json(['message' => 'Conta inativa.'], 403);
        }

        $apiKey->update(['last_used_at' => now()]);

        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_id', $tenant->id);

        // Autentica um usuário do tenant para que BelongsToTenant funcione normalmente
        $user = \App\Models\User::where('tenant_id', $tenant->id)
            ->orderByRaw("FIELD(role, 'admin', 'manager', 'viewer')")
            ->first();
        if ($user) {
            auth()->setUser($user);
        }

        return $next($request);
    }
}
