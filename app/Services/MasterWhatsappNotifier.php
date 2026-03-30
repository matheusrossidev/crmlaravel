<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Sends admin notifications to the master WhatsApp group via WAHA.
 */
class MasterWhatsappNotifier
{
    private const GROUP_ID = '120363403276686046@g.us';
    private const SESSION  = 'tenant_12';

    // ── Public dispatchers ────────────────────────────────────────────────────

    /**
     * New tenant registration (regular user).
     */
    public static function newRegistration(Tenant $tenant, User $user, ?string $agencyName = null): void
    {
        $locale  = $tenant->locale ?? 'pt_BR';
        $gateway = strtoupper($tenant->billing_provider ?? 'asaas');
        $trial   = $tenant->trial_ends_at
            ? (int) now()->diffInDays($tenant->trial_ends_at)
            : 14;

        $msg = "🆕 *NOVO CADASTRO*\n"
             . "━━━━━━━━━━━━━━━━━━\n\n"
             . "🏢 *Empresa:* {$tenant->name}\n"
             . "👤 *Nome:* {$user->name}\n"
             . "📧 *Email:* {$user->email}\n"
             . "📋 *Plano:* Free (Trial {$trial} dias)\n"
             . "🌍 *Idioma:* {$locale}\n"
             . "💳 *Gateway:* {$gateway}\n"
             . "🔗 *Indicado por:* " . ($agencyName ?: 'Orgânico') . "\n\n"
             . "📅 " . now()->format('d/m/Y H:i');

        self::send($msg);
    }

    /**
     * New agency partner registration.
     */
    public static function newAgencyRegistration(Tenant $tenant, User $user, string $code): void
    {
        $msg = "🤝 *NOVA AGÊNCIA PARCEIRA*\n"
             . "━━━━━━━━━━━━━━━━━━\n\n"
             . "🏢 *Agência:* {$tenant->name}\n"
             . "👤 *Nome:* {$user->name}\n"
             . "📧 *Email:* {$user->email}\n"
             . "🔑 *Código:* {$code}\n"
             . "📋 *Plano:* Partner\n\n"
             . "📅 " . now()->format('d/m/Y H:i');

        self::send($msg);
    }

    /**
     * Subscription payment confirmed (Asaas or Stripe).
     */
    public static function paymentConfirmed(
        Tenant $tenant,
        float $value,
        string $gateway,
        ?string $paymentId = null,
    ): void {
        $currency = ($tenant->billing_currency ?? 'BRL') === 'BRL' ? 'R$' : '$';

        $msg = "💰 *PAGAMENTO CONFIRMADO*\n"
             . "━━━━━━━━━━━━━━━━━━\n\n"
             . "🏢 *Empresa:* {$tenant->name}\n"
             . "📋 *Plano:* {$tenant->plan}\n"
             . "💵 *Valor:* {$currency} " . number_format($value, 2, ',', '.') . "\n"
             . "🏦 *Gateway:* {$gateway}\n"
             . ($paymentId ? "🆔 *ID:* {$paymentId}\n" : '')
             . "\n📅 " . now()->format('d/m/Y H:i');

        self::send($msg);
    }

    /**
     * Token increment purchase confirmed.
     */
    public static function tokenPurchase(
        Tenant $tenant,
        int $tokens,
        float $price,
        string $gateway,
    ): void {
        $currency = ($tenant->billing_currency ?? 'BRL') === 'BRL' ? 'R$' : '$';

        $msg = "🪙 *COMPRA DE TOKENS*\n"
             . "━━━━━━━━━━━━━━━━━━\n\n"
             . "🏢 *Empresa:* {$tenant->name}\n"
             . "🔢 *Tokens:* " . number_format($tokens, 0, '', '.') . "\n"
             . "💵 *Valor:* {$currency} " . number_format($price, 2, ',', '.') . "\n"
             . "🏦 *Gateway:* {$gateway}\n\n"
             . "📅 " . now()->format('d/m/Y H:i');

        self::send($msg);
    }

    /**
     * Weekly platform report (called by scheduled command).
     */
    public static function weeklyReport(): void
    {
        $total     = Tenant::count();
        $trial     = Tenant::where('status', 'trial')->count();
        $active    = Tenant::where('status', 'active')->count();
        $partner   = Tenant::where('status', 'partner')->count();
        $suspended = Tenant::where('status', 'suspended')->count();

        // New tenants this week vs last week
        $thisWeek = Tenant::where('created_at', '>=', now()->startOfWeek())->count();
        $lastWeek = Tenant::whereBetween('created_at', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek(),
        ])->count();

        // Conversion: trials that became active this month
        $convertedThisMonth = Tenant::where('status', 'active')
            ->where('updated_at', '>=', now()->startOfMonth())
            ->count();

        // Churned this month (became suspended)
        $churnedThisMonth = Tenant::where('status', 'suspended')
            ->where('updated_at', '>=', now()->startOfMonth())
            ->count();

        // Growth %
        $growthPct = $lastWeek > 0
            ? round((($thisWeek - $lastWeek) / $lastWeek) * 100, 1)
            : ($thisWeek > 0 ? 100 : 0);
        $growthEmoji = $growthPct >= 0 ? '📈' : '📉';
        $growthSign  = $growthPct >= 0 ? '+' : '';

        // Revenue this month
        $revenueMonth = \App\Models\PaymentLog::where('status', 'confirmed')
            ->where('type', 'subscription')
            ->where('paid_at', '>=', now()->startOfMonth())
            ->sum('amount');

        // Total users
        $totalUsers = User::count();

        $msg = "📊 *RELATÓRIO SEMANAL*\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . now()->format('d/m/Y') . " (sexta-feira)\n\n"
             . "👥 *Total de contas:* {$total}\n"
             . "   🟡 Trial: {$trial}\n"
             . "   🟢 Pagantes: {$active}\n"
             . "   🟣 Parceiros: {$partner}\n"
             . "   🔴 Suspensos: {$suspended}\n\n"
             . "👤 *Total de usuários:* {$totalUsers}\n\n"
             . "📆 *Esta semana:*\n"
             . "   Novos cadastros: {$thisWeek}\n"
             . "   Semana passada: {$lastWeek}\n"
             . "   {$growthEmoji} Variação: {$growthSign}{$growthPct}%\n\n"
             . "📆 *Este mês:*\n"
             . "   ✅ Convertidos (trial→pago): {$convertedThisMonth}\n"
             . "   ❌ Churn (suspensos): {$churnedThisMonth}\n"
             . "   💰 Receita: R$ " . number_format($revenueMonth, 2, ',', '.') . "\n\n"
             . "🚀 Bora crescer!";

        self::send($msg);
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private static function send(string $text): void
    {
        try {
            $waha = new WahaService(self::SESSION);
            $waha->sendText(self::GROUP_ID, $text);
        } catch (\Throwable $e) {
            Log::warning('MasterWhatsappNotifier: falha ao enviar', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
