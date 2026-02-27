<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\TrialEndingSoon;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckTrialExpiry extends Command
{
    protected $signature   = 'billing:check-trials';
    protected $description = 'Verifica trials prestes a expirar e expira os vencidos';

    public function handle(): void
    {
        $this->info('Verificando trials...');

        // Tenants em trial, sem assinatura ativa, não isentos
        $trialing = Tenant::where('status', 'trial')
            ->whereNull('subscription_status')
            ->whereNotNull('trial_ends_at')
            ->where('status', '!=', 'partner')
            ->get();

        $notified3 = 0;
        $notified1 = 0;
        $expired   = 0;

        foreach ($trialing as $tenant) {
            $daysLeft = (int) now()->startOfDay()->diffInDays($tenant->trial_ends_at->startOfDay(), false);

            if ($daysLeft < 0) {
                // Trial expirado → inativar
                $tenant->update(['status' => 'inactive']);
                $expired++;
                $this->info("  → Tenant #{$tenant->id} ({$tenant->name}) expirado e inativado.");
                continue;
            }

            if ($daysLeft === 3 || $daysLeft === 1) {
                $admin = $tenant->users()->where('role', 'admin')->first();
                if ($admin) {
                    try {
                        Mail::to($admin->email)->send(new TrialEndingSoon($admin, $tenant, $daysLeft));
                        $daysLeft === 3 ? $notified3++ : $notified1++;
                        $this->info("  → Email enviado para {$admin->email} ({$daysLeft}d restantes).");
                    } catch (\Throwable $e) {
                        $this->warn("  → Falha ao enviar email para {$admin->email}: {$e->getMessage()}");
                    }
                }
            }
        }

        $this->info("Concluído: {$notified3} avisos de 3 dias, {$notified1} avisos de 1 dia, {$expired} expirados.");
    }
}
