<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\InstagramConversation;
use App\Models\InstagramInstance;
use App\Services\InstagramService;
use Illuminate\Console\Command;

/**
 * Re-busca name/username/profile_pic via Graph API pra conversas Instagram
 * que estao com esses campos null. Use uma vez depois do fix do field
 * profile_pic (commits anteriores estavam pedindo profile_picture_url que
 * nao existe na API, deixando todos os campos null).
 *
 *   php artisan instagram:repair-contacts --dry-run
 *   php artisan instagram:repair-contacts
 *   php artisan instagram:repair-contacts --tenant=12
 */
class RepairInstagramContacts extends Command
{
    protected $signature = 'instagram:repair-contacts {--tenant= : Limita a um tenant_id especifico} {--dry-run : Nao escreve nada, so reporta o que faria}';

    protected $description = 'Re-busca metadados (name/username/profile_pic) via Graph API pra conversas Instagram que estao com esses campos null.';

    public function handle(): int
    {
        $tenant = $this->option('tenant');
        $dry    = (bool) $this->option('dry-run');

        if ($dry) {
            $this->warn('=== DRY RUN — nenhuma escrita sera feita ===');
        }

        $query = InstagramConversation::withoutGlobalScope('tenant')
            ->where(function ($q) {
                $q->whereNull('contact_name')
                  ->orWhereNull('contact_username')
                  ->orWhereNull('contact_picture_url');
            });

        if ($tenant) {
            $query->where('tenant_id', $tenant);
        }

        $total = $query->count();
        $this->info("Conversas a reparar: {$total}");

        if ($total === 0) {
            return self::SUCCESS;
        }

        $fixed   = 0;
        $skipped = 0;
        $failed  = 0;

        foreach ($query->cursor() as $conv) {
            $instance = InstagramInstance::withoutGlobalScope('tenant')->find($conv->instance_id);
            if (! $instance || ! $instance->access_token) {
                $this->warn("conv #{$conv->id}: instance #{$conv->instance_id} sem token — skip");
                $skipped++;
                continue;
            }

            try {
                $token   = decrypt($instance->access_token);
                $service = new InstagramService($token);
                $profile = $service->getProfile($conv->igsid);

                if (! empty($profile['error'])) {
                    $this->warn("igsid {$conv->igsid}: API err " . ($profile['status'] ?? '?'));
                    $failed++;
                    continue;
                }

                $update = [];
                if (! $conv->contact_name && ! empty($profile['name'])) {
                    $update['contact_name'] = $profile['name'];
                }
                if (! $conv->contact_username && ! empty($profile['username'])) {
                    $update['contact_username'] = $profile['username'];
                }
                if (! $conv->contact_picture_url && ! empty($profile['profile_pic'])) {
                    $update['contact_picture_url'] = $profile['profile_pic'];
                }

                if (! empty($update)) {
                    if ($dry) {
                        $this->line("[DRY] conv #{$conv->id} igsid={$conv->igsid}: " . json_encode($update, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    } else {
                        $conv->update($update);
                        $this->line("conv #{$conv->id}: ok (" . implode(', ', array_keys($update)) . ")");
                    }
                    $fixed++;
                } else {
                    // API retornou mas sem novos dados — nada pra atualizar
                    $skipped++;
                }

                // Rate limit defensivo: Graph API permite ~200 reqs/hora por user.
                // 300ms = ~3 reqs/seg, bem abaixo do limite.
                usleep(300000);
            } catch (\Throwable $e) {
                $this->error("conv #{$conv->id} igsid={$conv->igsid}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("");
        $this->info("=== Resumo ===");
        $this->info("Reparadas: {$fixed}");
        $this->info("Skipped:   {$skipped}");
        $this->info("Falhas:    {$failed}");

        return self::SUCCESS;
    }
}
