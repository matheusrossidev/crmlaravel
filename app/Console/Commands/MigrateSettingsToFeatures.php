<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\FeatureFlag;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateSettingsToFeatures extends Command
{
    protected $signature = 'features:migrate-from-settings
                            {--dry-run : Não grava, só mostra o que faria}';

    protected $description = 'Migra tenants.settings_json.integration_* → pivot feature_tenant (one-shot).';

    /**
     * Mapping: chave antiga em settings_json → slug do FeatureFlag.
     * Se o valor em settings_json diferir do default do feature flag global,
     * criamos override individual em feature_tenant.
     */
    private const MAP = [
        'integration_whatsapp'        => 'whatsapp',
        'integration_instagram'       => 'instagram',
        'integration_google_calendar' => 'google_calendar',
        'integration_facebook_ads'    => 'facebook_leadads',
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $flags = FeatureFlag::all()->keyBy('slug');
        $touched = 0;
        $overrides = 0;

        foreach (Tenant::cursor() as $tenant) {
            $settings = $tenant->settings_json ?? [];
            if (empty($settings)) continue;

            $rowsForTenant = 0;
            foreach (self::MAP as $settingsKey => $slug) {
                if (!array_key_exists($settingsKey, $settings)) continue;

                $flag = $flags[$slug] ?? null;
                if (!$flag) continue;

                $desired = (bool) $settings[$settingsKey];
                // Só cria override se divergir do global — senão respeita herança.
                if ($desired === (bool) $flag->is_enabled_globally) {
                    continue;
                }

                $rowsForTenant++;
                $overrides++;

                $this->line("  tenant #{$tenant->id} → {$slug} = " . ($desired ? 'ON' : 'OFF'));

                if (!$dry) {
                    DB::table('feature_tenant')->updateOrInsert(
                        ['tenant_id' => $tenant->id, 'feature_id' => $flag->id],
                        ['is_enabled' => $desired, 'updated_at' => now(), 'created_at' => now()],
                    );
                }
            }

            if ($rowsForTenant > 0) $touched++;
        }

        $this->info(sprintf(
            '%s — %d tenants com overrides, %d rows criadas/atualizadas.',
            $dry ? 'DRY RUN' : 'Migração concluída',
            $touched,
            $overrides,
        ));

        return self::SUCCESS;
    }
}
