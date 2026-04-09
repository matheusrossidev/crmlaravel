<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\InstagramMessage;
use App\Models\WhatsappMessage;
use Illuminate\Console\Command;

class BackfillMessageAuthorship extends Command
{
    protected $signature   = 'messages:backfill-authorship
                              {--dry-run : So mostra o que seria atualizado, nao escreve}
                              {--tenant= : Limita a um tenant especifico}';
    protected $description = 'Preenche sent_by retroativamente em mensagens outbound antigas via heuristica';

    public function handle(): int
    {
        $dryRun   = (bool) $this->option('dry-run');
        $tenantId = $this->option('tenant') ? (int) $this->option('tenant') : null;

        $this->line(($dryRun ? '[DRY-RUN] ' : '') . 'Backfilling message authorship...');

        $totalHuman = 0;
        $totalEvent = 0;

        // ── WhatsappMessage ────────────────────────────────────────────────
        // Heuristica 1: outbound + user_id != null  →  sent_by='human'
        $q = WhatsappMessage::withoutGlobalScope('tenant')
            ->whereNull('sent_by')
            ->where('direction', 'outbound')
            ->whereNotNull('user_id');
        if ($tenantId) {
            $q->where('tenant_id', $tenantId);
        }
        $cnt = $q->count();
        if ($cnt > 0) {
            if (! $dryRun) {
                $q->update(['sent_by' => 'human']);
            }
            $totalHuman += $cnt;
            $this->line("  WhatsApp: {$cnt} mensagens marcadas como 'human'");
        }

        // Heuristica 2: outbound + type='event' + media_mime LIKE 'ai_%'  →  sent_by='event'
        $q = WhatsappMessage::withoutGlobalScope('tenant')
            ->whereNull('sent_by')
            ->where('direction', 'outbound')
            ->where('type', 'event')
            ->where('media_mime', 'like', 'ai_%');
        if ($tenantId) {
            $q->where('tenant_id', $tenantId);
        }
        $cnt = $q->count();
        if ($cnt > 0) {
            if (! $dryRun) {
                $q->update(['sent_by' => 'event']);
            }
            $totalEvent += $cnt;
            $this->line("  WhatsApp: {$cnt} mensagens marcadas como 'event' (eventos da IA)");
        }

        // ── InstagramMessage ───────────────────────────────────────────────
        $q = InstagramMessage::withoutGlobalScope('tenant')
            ->whereNull('sent_by')
            ->where('direction', 'outbound')
            ->whereNotNull('user_id');
        if ($tenantId) {
            $q->where('tenant_id', $tenantId);
        }
        $cnt = $q->count();
        if ($cnt > 0) {
            if (! $dryRun) {
                $q->update(['sent_by' => 'human']);
            }
            $totalHuman += $cnt;
            $this->line("  Instagram: {$cnt} mensagens marcadas como 'human'");
        }

        $this->line('');
        $this->line(($dryRun ? '[DRY-RUN] ' : '') . "Total: human={$totalHuman} event={$totalEvent}");
        $this->line('Mensagens outbound restantes (sem heuristica clara) ficam com sent_by=NULL — sem badge no chat.');

        return self::SUCCESS;
    }
}
