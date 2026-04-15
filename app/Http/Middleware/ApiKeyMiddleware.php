<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Map route names/prefixes to required permissions.
     */
    private const PERMISSION_MAP = [
        'leads'     => ['GET' => 'leads:read',  'POST' => 'leads:write', 'PUT' => 'leads:write', 'DELETE' => 'leads:write'],
        'pipelines' => ['GET' => 'pipelines:read'],
        'campaigns' => ['GET' => 'campaigns:read', 'POST' => 'campaigns:write', 'PUT' => 'campaigns:write', 'DELETE' => 'campaigns:write'],
    ];

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
        if (!$tenant || !in_array($tenant->status, ['active', 'trial'], true)) {
            return response()->json(['message' => 'Conta inativa.'], 403);
        }

        // Check permissions based on the requested resource and method
        $requiredPermission = $this->resolvePermission($request);
        if ($requiredPermission && !$apiKey->hasPermission($requiredPermission)) {
            return response()->json([
                'message' => 'API Key sem permissão para esta operação.',
                'required_permission' => $requiredPermission,
            ], 403);
        }

        // Rate limit por API Key (F-12): 60 req/min por key independente de IP
        $limiterKey = "api-key:{$apiKey->id}";
        if (RateLimiter::tooManyAttempts($limiterKey, 60)) {
            $retryAfter = RateLimiter::availableIn($limiterKey);
            return response()->json([
                'message'     => 'Rate limit excedido para esta API Key.',
                'retry_after' => $retryAfter,
            ], 429)->header('Retry-After', (string) $retryAfter);
        }
        RateLimiter::hit($limiterKey, 60);

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

    /**
     * Resolve the required permission based on the request path and method.
     */
    private function resolvePermission(Request $request): ?string
    {
        $path   = $request->path(); // e.g. "api/v1/leads" or "api/v1/leads/5/stage"
        $method = $request->method();

        foreach (self::PERMISSION_MAP as $resource => $methods) {
            if (str_contains($path, $resource)) {
                return $methods[$method] ?? $methods['GET'] ?? null;
            }
        }

        return null;
    }
}
