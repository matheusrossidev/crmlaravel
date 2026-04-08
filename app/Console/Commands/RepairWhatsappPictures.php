<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Services\WahaService;
use App\Support\ProfilePictureDownloader;
use Illuminate\Console\Command;

/**
 * Re-busca foto de perfil via WAHA pra conversas WhatsApp que estao com
 * contact_picture_url null OU apontando pra uma URL externa do CDN do
 * WhatsApp (mmg.whatsapp.net, etc) que ja expirou ou pode expirar.
 *
 *   php artisan whatsapp:repair-pictures [--tenant=N] [--instance=N] [--dry-run]
 *
 * Por default tenta TUDO que nao esta no nosso storage local. Use --null-only
 * pra so processar conversas com contact_picture_url=null (mais rapido).
 */
class RepairWhatsappPictures extends Command
{
    protected $signature = 'whatsapp:repair-pictures
                            {--tenant= : Limita a um tenant_id especifico}
                            {--instance= : Limita a uma instance_id especifica}
                            {--null-only : So processa conversas com contact_picture_url null}
                            {--dry-run : Nao escreve nada, so reporta o que faria}';

    protected $description = 'Re-busca foto de perfil via WAHA e baixa pra storage local. Conversas com URL externa expirada/expiravel sao re-baixadas.';

    public function handle(): int
    {
        $tenant   = $this->option('tenant');
        $instance = $this->option('instance');
        $nullOnly = (bool) $this->option('null-only');
        $dry      = (bool) $this->option('dry-run');

        if ($dry) {
            $this->warn('=== DRY RUN — nenhuma escrita sera feita ===');
        }

        $appUrl = rtrim((string) config('app.url'), '/');

        $query = WhatsappConversation::withoutGlobalScope('tenant');

        if ($nullOnly) {
            $query->whereNull('contact_picture_url');
        } else {
            // Pula conversas que ja tem URL local (do nosso storage)
            $query->where(function ($q) use ($appUrl) {
                $q->whereNull('contact_picture_url');
                if ($appUrl) {
                    $q->orWhere('contact_picture_url', 'not like', "{$appUrl}%");
                }
            });
        }

        if ($tenant) {
            $query->where('tenant_id', $tenant);
        }
        if ($instance) {
            $query->where('instance_id', $instance);
        }

        $total = $query->count();
        $this->info("Conversas a processar: {$total}");

        if ($total === 0) {
            return self::SUCCESS;
        }

        $fixed   = 0;
        $skipped = 0;
        $failed  = 0;

        // Cache de instances pra evitar re-instanciar WahaService
        $serviceCache = [];

        foreach ($query->cursor() as $conv) {
            $inst = WhatsappInstance::withoutGlobalScope('tenant')->find($conv->instance_id);
            if (! $inst || $inst->status !== 'connected') {
                $skipped++;
                continue;
            }

            // Skip Cloud API instances — esse comando e so pra WAHA
            if (($inst->provider ?? 'waha') !== 'waha') {
                $skipped++;
                continue;
            }

            try {
                if (! isset($serviceCache[$inst->id])) {
                    $serviceCache[$inst->id] = new WahaService($inst->session_name);
                }
                $waha = $serviceCache[$inst->id];

                // Monta o chatId no formato esperado pelo WAHA
                $chatId = $conv->is_group
                    ? ($conv->phone . '@g.us')
                    : ($conv->phone . '@c.us');

                $remotePic = $waha->getChatPicture($chatId);
                if (! $remotePic) {
                    $skipped++;
                    continue;
                }

                if ($dry) {
                    $this->line("[DRY] conv #{$conv->id} {$conv->phone}: " . substr($remotePic, 0, 60) . '...');
                    $fixed++;
                } else {
                    $localPic = ProfilePictureDownloader::download(
                        $remotePic,
                        'whatsapp',
                        $conv->tenant_id,
                        $conv->phone ?: $chatId,
                    );

                    if ($localPic) {
                        $conv->update(['contact_picture_url' => $localPic]);
                        $this->line("conv #{$conv->id} {$conv->phone}: ok");
                        $fixed++;
                    } else {
                        $failed++;
                    }
                }

                // Rate limit: WAHA roda local mas o CDN do WhatsApp pode rate-limitar.
                // 200ms = 5 req/s.
                usleep(200000);
            } catch (\Throwable $e) {
                $this->error("conv #{$conv->id} {$conv->phone}: {$e->getMessage()}");
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
