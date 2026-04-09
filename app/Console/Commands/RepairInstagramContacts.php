<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\InstagramConversation;
use App\Models\InstagramInstance;
use App\Services\InstagramService;
use App\Support\ProfilePictureDownloader;
use Illuminate\Console\Command;

/**
 * Re-busca contact info (name + username + foto) pra conversas Instagram que
 * estao com algum desses campos null.
 *
 * Usa estrategia hybrid:
 *   1) GET /{IGSID}?fields=name,username,profile_pic — full data
 *      (funciona pra instances criadas antes de ~28/03/2026)
 *   2) Fallback: /me/conversations + participants — so username
 *      (instances novas em que a Meta retorna 100/33 no endpoint direto)
 *
 * Confirmado empiricamente em 08/04/2026 comparando instances #34 (27/03,
 * funciona) vs #37 (01/04, falha 100/33). A Meta mudou silenciosamente o
 * comportamento entre essas duas datas.
 *
 * Quando consegue foto, baixa pro storage local — URLs do CDN do Meta
 * (cdninstagram.com) expiram em horas.
 *
 *   php artisan instagram:repair-contacts --dry-run
 *   php artisan instagram:repair-contacts
 *   php artisan instagram:repair-contacts --tenant=12
 *   php artisan instagram:repair-contacts --instance=37
 */
class RepairInstagramContacts extends Command
{
    protected $signature = 'instagram:repair-contacts
                            {--tenant= : Limita a um tenant_id especifico}
                            {--instance= : Limita a uma instance_id especifica}
                            {--dry-run : Nao escreve nada, so reporta o que faria}';

    protected $description = 'Re-busca name + username + foto de perfil pras conversas Instagram com dados faltando.';

    public function handle(): int
    {
        $tenant   = $this->option('tenant');
        $instance = $this->option('instance');
        $dry      = (bool) $this->option('dry-run');

        if ($dry) {
            $this->warn('=== DRY RUN — nenhuma escrita sera feita ===');
        }

        $instanceQuery = InstagramInstance::withoutGlobalScope('tenant')
            ->where('status', 'connected')
            ->whereNotNull('access_token');

        if ($tenant) {
            $instanceQuery->where('tenant_id', $tenant);
        }
        if ($instance) {
            $instanceQuery->where('id', $instance);
        }

        $instances = $instanceQuery->get();
        $this->info("Instances a processar: {$instances->count()}");

        $totalFixed   = 0;
        $totalSkipped = 0;
        $totalFailed  = 0;

        foreach ($instances as $inst) {
            $convs = InstagramConversation::withoutGlobalScope('tenant')
                ->where('instance_id', $inst->id)
                ->where(function ($q) {
                    $q->whereNull('contact_name')
                      ->orWhereNull('contact_username')
                      ->orWhereNull('contact_picture_url');
                })
                ->get();

            if ($convs->isEmpty()) {
                continue;
            }

            $this->line("Instance #{$inst->id} (tenant {$inst->tenant_id} / @{$inst->username}): {$convs->count()} conversas a reparar");

            try {
                $token   = decrypt($inst->access_token);
                $service = new InstagramService($token);
            } catch (\Throwable $e) {
                $this->error("  instance #{$inst->id}: erro ao decrypt token — {$e->getMessage()}");
                $totalFailed += $convs->count();
                continue;
            }

            // Probe: testa os primeiros 5 IGSIDs pra decidir o modo. A mudanca
            // da Meta e POR IGSID, nao por instance — IGSIDs antigos funcionam
            // mesmo em instances "novas" e vice-versa. Se ao menos 1 dos 5
            // funcionar, vale a pena tentar direct pra todos (com fallback
            // individual quando falhar). Se nenhum funcionar, so usar fallback.
            $probeResults = 0;
            foreach ($convs->take(5) as $probeConv) {
                if ($this->probeDirectEndpoint($service, $probeConv->igsid)) {
                    $probeResults++;
                }
            }
            $tryDirect = $probeResults > 0;
            $this->line("  probe: {$probeResults}/5 IGSIDs respondem ao endpoint direto" . ($tryDirect ? '' : ' — usando so fallback'));

            // Pre-carrega mapa do fallback (lazy: so se precisar)
            $fallbackMap     = null;
            $loadFallbackMap = function () use ($service, &$fallbackMap) {
                if ($fallbackMap === null) {
                    $this->line('  carregando mapa fallback /me/conversations…');
                    $fallbackMap = $this->buildFallbackMap($service);
                    $this->line('  mapa fallback: ' . count($fallbackMap) . ' entradas');
                }
                return $fallbackMap;
            };

            // Se ja sabemos que tem que usar so fallback, carrega o mapa de cara
            if (! $tryDirect) {
                $loadFallbackMap();
            }

            foreach ($convs as $conv) {
                try {
                    $info = ['name' => null, 'username' => null, 'picture' => null];

                    // Tenta endpoint direto primeiro (se algum probe passou)
                    if ($tryDirect) {
                        $direct = $this->fetchDirect($service, $conv->igsid);
                        if ($direct['name'] || $direct['username'] || $direct['picture']) {
                            $info = $direct;
                        }
                    }

                    // Fallback per-conv: se direct nao deu nada, tenta o mapa
                    if (! $info['username']) {
                        $map = $loadFallbackMap();
                        if (isset($map[$conv->igsid])) {
                            $info['username'] = $map[$conv->igsid];
                        }
                    }

                    if (! $info['name'] && ! $info['username'] && ! $info['picture']) {
                        $totalSkipped++;
                        continue;
                    }

                    $update = [];
                    if (! $conv->contact_username && $info['username']) {
                        $update['contact_username'] = $info['username'];
                    }
                    if (! $conv->contact_name) {
                        $name = $info['name'] ?? $info['username'] ?? null;
                        if ($name) {
                            $update['contact_name'] = $name;
                        }
                    }
                    if (! $conv->contact_picture_url && $info['picture']) {
                        // Baixa pro storage local
                        $localPic = $dry
                            ? $info['picture']
                            : (ProfilePictureDownloader::download(
                                $info['picture'],
                                'instagram',
                                $inst->tenant_id,
                                $conv->igsid,
                            ) ?: $info['picture']);
                        $update['contact_picture_url'] = $localPic;
                    }

                    if (empty($update)) {
                        $totalSkipped++;
                        continue;
                    }

                    if ($dry) {
                        $this->line("  [DRY] conv #{$conv->id}: " . implode(', ', array_keys($update)));
                    } else {
                        $conv->update($update);
                        $this->line("  conv #{$conv->id}: " . implode(', ', array_keys($update)) . ' (@' . ($update['contact_username'] ?? $conv->contact_username) . ')');
                    }
                    $totalFixed++;

                    usleep(200000); // rate limit defensivo
                } catch (\Throwable $e) {
                    $this->error("  conv #{$conv->id}: {$e->getMessage()}");
                    $totalFailed++;
                }
            }
        }

        $this->info('');
        $this->info('=== Resumo ===');
        $this->info("Reparadas: {$totalFixed}");
        $this->info("Skipped:   {$totalSkipped}");
        $this->info("Falhas:    {$totalFailed}");

        return self::SUCCESS;
    }

    private function probeDirectEndpoint(InstagramService $service, string $igsid): bool
    {
        $r = $service->getProfile($igsid);
        return empty($r['error']);
    }

    private function fetchDirect(InstagramService $service, string $igsid): array
    {
        $r = $service->getProfile($igsid);
        if (! empty($r['error'])) {
            return ['name' => null, 'username' => null, 'picture' => null];
        }
        return [
            'name'     => $r['name']        ?? null,
            'username' => $r['username']    ?? null,
            'picture'  => $r['profile_pic'] ?? null,
        ];
    }

    /**
     * Constroi mapa IGSID -> username paginando /me/conversations e
     * fetching participants. Limita a 1000 conversations (20 paginas).
     */
    private function buildFallbackMap(InstagramService $service): array
    {
        $map   = [];
        $after = null;
        $pages = 0;

        do {
            $list = $service->listConversations(50, $after);
            $pages++;
            if (! empty($list['error'])) {
                break;
            }

            foreach ($list['data'] ?? [] as $conv) {
                $convId = $conv['id'] ?? null;
                if (! $convId) {
                    continue;
                }
                $details = $service->getConversationParticipants($convId);
                if (! empty($details['error'])) {
                    continue;
                }
                foreach ($details['participants']['data'] ?? [] as $p) {
                    if (($p['id'] ?? null) && ($p['username'] ?? null)) {
                        $map[$p['id']] = $p['username'];
                    }
                }
                usleep(150000);
            }

            $after = $list['paging']['cursors']['after'] ?? null;
        } while ($after && $pages < 20);

        return $map;
    }
}
