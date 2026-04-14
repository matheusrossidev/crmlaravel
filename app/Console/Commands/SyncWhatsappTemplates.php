<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WhatsappInstance;
use App\Services\Whatsapp\WhatsappTemplateService;
use Illuminate\Console\Command;

class SyncWhatsappTemplates extends Command
{
    protected $signature = 'whatsapp:sync-templates
                            {--tenant= : Filtra por tenant_id}
                            {--instance= : Filtra por whatsapp_instance_id}
                            {--dry-run : Não persiste, só mostra o que seria feito}';

    protected $description = 'Sincroniza Message Templates HSM do Meta com a tabela local whatsapp_templates.';

    public function handle(WhatsappTemplateService $service): int
    {
        $query = WhatsappInstance::query()
            ->where('provider', 'cloud_api')
            ->whereNotNull('waba_id');

        if ($tenantId = $this->option('tenant')) {
            $query->where('tenant_id', (int) $tenantId);
        }
        if ($instanceId = $this->option('instance')) {
            $query->where('id', (int) $instanceId);
        }

        $instances = $query->get();

        if ($instances->isEmpty()) {
            $this->warn('Nenhuma instância Cloud API elegível encontrada.');
            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        $totalCreated = 0;
        $totalUpdated = 0;
        $totalRemoved = 0;

        foreach ($instances as $instance) {
            $label = $instance->label ?: $instance->phone_number ?: "#{$instance->id}";

            if ($dryRun) {
                $result = $service->listFromMeta($instance);
                $count  = count((array) ($result['data'] ?? []));
                $this->line("Instance #{$instance->id} ({$label}): {$count} templates no Meta (dry-run)");
                continue;
            }

            $r = $service->syncFromMeta($instance);

            $instance->forceFill([
                // last_synced_at aqui é por instance; cada template tem o próprio last_synced_at
            ])->save();

            $totalCreated += $r['created'];
            $totalUpdated += $r['updated'];
            $totalRemoved += $r['removed'];

            if (! empty($r['error'])) {
                $this->error("Instance #{$instance->id} ({$label}): erro — {$r['error']}");
            } else {
                $this->info("Instance #{$instance->id} ({$label}): +{$r['created']} -{$r['removed']} ~{$r['updated']}");
            }
        }

        if (! $dryRun) {
            $this->info("TOTAL — created: {$totalCreated}, updated: {$totalUpdated}, removed: {$totalRemoved}");
        }

        return self::SUCCESS;
    }
}
