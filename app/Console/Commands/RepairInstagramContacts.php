<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\InstagramConversation;
use App\Models\InstagramInstance;
use App\Services\InstagramService;
use Illuminate\Console\Command;

/**
 * Re-busca username pra conversas Instagram que estao com contact_username null.
 *
 * Usa o caminho documentado pro fluxo "Instagram API with Instagram Login":
 *   1. GET /me/conversations?platform=instagram   -> lista conversations
 *   2. GET /{conversation_id}?fields=participants -> retorna IGSID+username
 *
 * O endpoint GET /{IGSID} (que retornaria name+profile_pic) NAO funciona
 * nesse fluxo — Meta retorna 100/33 "does not support this operation".
 * O endpoint GET /{message_id}?fields=from tambem nao funciona (mesmo erro).
 * Confirmado em prod 08/04/2026.
 *
 * Limitacao: name (display name) e profile_pic NAO estao disponiveis nesse
 * fluxo. UI usa @username como label e avatar fica fallback de letra.
 *
 *   php artisan instagram:repair-contacts --dry-run
 *   php artisan instagram:repair-contacts
 *   php artisan instagram:repair-contacts --tenant=12
 *   php artisan instagram:repair-contacts --instance=5
 */
class RepairInstagramContacts extends Command
{
    protected $signature = 'instagram:repair-contacts
                            {--tenant= : Limita a um tenant_id especifico}
                            {--instance= : Limita a uma instance_id especifica}
                            {--dry-run : Nao escreve nada, so reporta o que faria}';

    protected $description = 'Re-busca username via listConversations+participants pra conversas Instagram sem contact_username.';

    public function handle(): int
    {
        $tenant   = $this->option('tenant');
        $instance = $this->option('instance');
        $dry      = (bool) $this->option('dry-run');

        if ($dry) {
            $this->warn('=== DRY RUN — nenhuma escrita sera feita ===');
        }

        // Itera por instance pq listConversations e por-instance (1 chamada
        // /me/conversations cobre todas as conversations daquela conta).
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
                    $q->whereNull('contact_name')->orWhereNull('contact_username');
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

            // Constroi mapa IGSID -> username uma unica vez por instance
            // (paginando ate cobrir todas as conversations).
            $map    = [];
            $after  = null;
            $pages  = 0;
            $maxPgs = 20; // teto defensivo (20 * 50 = 1000 conversations)

            do {
                $list = $service->listConversations(50, $after);
                $pages++;

                if (! empty($list['error'])) {
                    $this->warn("  instance #{$inst->id}: listConversations falhou — status " . ($list['status'] ?? '?'));
                    break;
                }

                $data = $list['data'] ?? [];
                foreach ($data as $conv) {
                    $convId = $conv['id'] ?? null;
                    if (! $convId) {
                        continue;
                    }

                    $details = $service->getConversationParticipants($convId);
                    if (! empty($details['error'])) {
                        continue;
                    }

                    foreach ($details['participants']['data'] ?? [] as $p) {
                        $pid = $p['id'] ?? null;
                        $un  = $p['username'] ?? null;
                        if ($pid && $un) {
                            $map[$pid] = $un;
                        }
                    }

                    // Rate limit defensivo
                    usleep(150000);
                }

                $after = $list['paging']['cursors']['after'] ?? null;
            } while ($after && $pages < $maxPgs);

            $this->line("  instance #{$inst->id}: mapa IGSID->username com " . count($map) . ' entradas');

            foreach ($convs as $conv) {
                $username = $map[$conv->igsid] ?? null;
                if (! $username) {
                    $totalSkipped++;
                    continue;
                }

                $update = [];
                if (! $conv->contact_username) {
                    $update['contact_username'] = $username;
                }
                if (! $conv->contact_name) {
                    $update['contact_name'] = $username; // fallback display name
                }

                if (empty($update)) {
                    $totalSkipped++;
                    continue;
                }

                if ($dry) {
                    $this->line("  [DRY] conv #{$conv->id} igsid={$conv->igsid}: " . json_encode($update, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                } else {
                    $conv->update($update);
                    $this->line("  conv #{$conv->id}: @{$username}");
                }
                $totalFixed++;
            }
        }

        $this->info('');
        $this->info('=== Resumo ===');
        $this->info("Reparadas: {$totalFixed}");
        $this->info("Skipped:   {$totalSkipped}");
        $this->info("Falhas:    {$totalFailed}");

        return self::SUCCESS;
    }
}
