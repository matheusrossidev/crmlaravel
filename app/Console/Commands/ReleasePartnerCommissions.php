<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PartnerCommission;
use App\Models\User;
use App\Notifications\PartnerNotification;
use Illuminate\Console\Command;

class ReleasePartnerCommissions extends Command
{
    protected $signature = 'partners:release-commissions';
    protected $description = 'Release pending partner commissions that passed the grace period';

    public function handle(): int
    {
        $toRelease = PartnerCommission::where('status', 'pending')
            ->where('available_at', '<=', now()->toDateString())
            ->get();

        if ($toRelease->isEmpty()) {
            $this->info('Nothing to release.');
            return self::SUCCESS;
        }

        // Group by partner tenant
        $byPartner = $toRelease->groupBy('tenant_id');

        foreach ($byPartner as $tenantId => $commissions) {
            $total = $commissions->sum('amount');

            // Update status
            PartnerCommission::whereIn('id', $commissions->pluck('id'))
                ->update(['status' => 'available']);

            // Notify partner admin
            $admin = User::where('tenant_id', $tenantId)->where('role', 'admin')->first();
            if ($admin) {
                $admin->notify(new PartnerNotification(
                    'Comissão liberada!',
                    'R$ ' . number_format((float) $total, 2, ',', '.') . ' em comissões foram liberados para saque.',
                ));
            }
        }

        $this->info("Released: {$toRelease->count()} commissions for " . $byPartner->count() . ' partners');

        return self::SUCCESS;
    }
}
