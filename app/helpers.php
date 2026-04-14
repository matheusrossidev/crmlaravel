<?php

declare(strict_types=1);

if (! function_exists('activeTenant')) {
    /**
     * Retorna o tenant ativo, considerando impersonação de agência.
     * Usa: app('active_tenant_id') > session('impersonating_tenant_id') > auth user tenant.
     */
    function activeTenant(): ?\App\Models\Tenant
    {
        if (app()->has('active_tenant_id')) {
            return \App\Models\Tenant::find(app('active_tenant_id'));
        }
        $impId = session('impersonating_tenant_id');
        if ($impId) {
            return \App\Models\Tenant::find($impId);
        }
        return auth()->user()?->tenant;
    }
}

if (! function_exists('activeTenantId')) {
    /**
     * Retorna o ID do tenant ativo (mais eficiente que activeTenant() quando só precisa do ID).
     */
    function activeTenantId(): ?int
    {
        if (app()->has('active_tenant_id')) {
            return (int) app('active_tenant_id');
        }
        $impId = session('impersonating_tenant_id');
        if ($impId) {
            return (int) $impId;
        }
        return auth()->user()?->tenant_id;
    }
}

if (! function_exists('formatBrPhone')) {
    /**
     * Formata um número de telefone brasileiro para exibição.
     * Entrada: "556192008997" | "61992008997" | "992008997"
     * Saída:   "(61) 9200-8997" | "(61) 99200-8997"
     */
    function formatBrPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        // Remove código do país (55) se presente e número tiver >= 12 dígitos
        if (strlen($digits) >= 12 && str_starts_with($digits, '55')) {
            $digits = substr($digits, 2);
        }

        // Celular: DDD (2) + 9 dígitos = 11 dígitos → (DD) DDDDD-DDDD
        if (strlen($digits) === 11) {
            return '(' . substr($digits, 0, 2) . ') '
                . substr($digits, 2, 5) . '-'
                . substr($digits, 7);
        }

        // Fixo: DDD (2) + 8 dígitos = 10 dígitos → (DD) DDDD-DDDD
        if (strlen($digits) === 10) {
            return '(' . substr($digits, 0, 2) . ') '
                . substr($digits, 2, 4) . '-'
                . substr($digits, 6);
        }

        return $phone; // fallback: retorna original sem formatação
    }
}

if (! function_exists('whatsappUrl')) {
    /**
     * Gera URL de click-to-chat do WhatsApp para um número.
     * Garante que o código do país 55 está presente.
     * Saída: "https://wa.me/5561992008997"
     */
    function whatsappUrl(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if ($digits === '') {
            return '#';
        }

        if (! str_starts_with($digits, '55')) {
            $digits = '55' . $digits;
        }

        return 'https://wa.me/' . $digits;
    }
}

if (! function_exists('tenantHasCloudApi')) {
    /**
     * Retorna true se o tenant atual tem pelo menos uma instância
     * WhatsApp Cloud API (provider='cloud_api') conectada.
     *
     * Usado em menus, gates de UI e middleware pra esconder/bloquear
     * funcionalidades específicas do Cloud API (templates HSM, etc.)
     * pra tenants que só têm WAHA ou nenhuma integração.
     *
     * Cache de 60s pra não bater DB a cada render de menu.
     * Invalidação via WhatsappInstanceObserver quando provider=cloud_api.
     */
    function tenantHasCloudApi(): bool
    {
        $tenantId = activeTenantId();
        if (! $tenantId) {
            return false;
        }

        return (bool) cache()->remember(
            "tenant:{$tenantId}:has_cloud_api",
            60,
            fn () => \App\Models\WhatsappInstance::where('tenant_id', $tenantId)
                ->where('provider', 'cloud_api')
                ->exists(),
        );
    }
}
