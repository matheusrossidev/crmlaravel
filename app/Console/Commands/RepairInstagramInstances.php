<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\InstagramInstance;
use App\Services\InstagramService;
use Illuminate\Console\Command;

/**
 * Re-valida cada InstagramInstance contra o /me da Graph API usando o token
 * proprio da instance e popula instagram_account_id + ig_business_account_id
 * corretamente.
 *
 * Resolve dois problemas historicos:
 *
 * 1) Instances criadas via OAuth onde o getBusinessAccountId() (que chama
 *    graph.facebook.com) falhou silenciosamente — ig_business_account_id
 *    ficou NULL e o webhook nunca achava a instance.
 *
 * 2) Cross-tenant contamination causada pela "auto-descoberta" velha do
 *    ProcessInstagramWebhook que pegava o entry.id de um webhook de QUALQUER
 *    tenant e gravava na primeira instance com NULL — colando IDs errados.
 *    Esse comando re-busca o ID via /me da propria instance, sobrescrevendo
 *    qualquer ID errado.
 *
 *   php artisan instagram:repair-instances --dry-run
 *   php artisan instagram:repair-instances
 *   php artisan instagram:repair-instances --tenant=12
 *   php artisan instagram:repair-instances --force  (re-valida todas, mesmo as ja preenchidas)
 */
class RepairInstagramInstances extends Command
{
    protected $signature = 'instagram:repair-instances
                            {--tenant= : Limita a um tenant_id especifico}
                            {--force : Re-valida todas as instances, nao so as com IDs nulos}
                            {--dry-run : Nao escreve nada, so reporta o que faria}';

    protected $description = 'Re-valida instances Instagram contra /me e popula instagram_account_id / ig_business_account_id.';

    public function handle(): int
    {
        $tenant = $this->option('tenant');
        $force  = (bool) $this->option('force');
        $dry    = (bool) $this->option('dry-run');

        if ($dry) {
            $this->warn('=== DRY RUN — nenhuma escrita sera feita ===');
        }

        $query = InstagramInstance::withoutGlobalScope('tenant')
            ->whereNotNull('access_token');

        if (! $force) {
            $query->where(function ($q) {
                $q->whereNull('instagram_account_id')
                  ->orWhereNull('ig_business_account_id');
            });
        }

        if ($tenant) {
            $query->where('tenant_id', $tenant);
        }

        $instances = $query->get();
        $this->info("Instances a processar: {$instances->count()}");

        $fixed     = 0;
        $unchanged = 0;
        $failed    = 0;
        $mismatch  = 0;

        foreach ($instances as $inst) {
            $label = "instance #{$inst->id} (tenant {$inst->tenant_id} / @{$inst->username})";

            try {
                $token   = decrypt($inst->access_token);
                $service = new InstagramService($token);
                $me      = $service->getMe();

                if (! empty($me['error'])) {
                    $this->error("  {$label}: /me falhou — status " . ($me['status'] ?? '?'));
                    $failed++;
                    continue;
                }

                // Instagram Login API v18+ retorna user_id; fallback pra id
                $userId   = $me['user_id'] ?? $me['id'] ?? null;
                $username = $me['username'] ?? null;

                if (! $userId) {
                    $this->warn("  {$label}: /me nao retornou user_id — skip");
                    $failed++;
                    continue;
                }

                $update = [];

                // ig_business_account_id e o ID que a Meta usa em entry.id dos
                // webhooks. No fluxo Instagram Login, esse ID == user_id do /me.
                if ($inst->ig_business_account_id !== $userId) {
                    if ($inst->ig_business_account_id) {
                        $mismatch++;
                        $this->warn("  {$label}: ig_business_account_id ERRADO — DB={$inst->ig_business_account_id} REAL={$userId}");
                    }
                    $update['ig_business_account_id'] = $userId;
                }

                if (! $inst->instagram_account_id) {
                    $update['instagram_account_id'] = $userId;
                }

                if ($username && $username !== $inst->username) {
                    $update['username'] = $username;
                }

                if (empty($update)) {
                    $unchanged++;
                    continue;
                }

                if ($dry) {
                    $this->line("  [DRY] {$label}: " . json_encode($update, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                } else {
                    $inst->update($update);
                    $this->info("  {$label}: " . json_encode($update, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
                $fixed++;
            } catch (\Throwable $e) {
                $this->error("  {$label}: excecao — {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info('');
        $this->info('=== Resumo ===');
        $this->info("Atualizadas:    {$fixed}");
        $this->info("ID errado fix:  {$mismatch}  (cross-tenant contamination removida)");
        $this->info("Inalteradas:    {$unchanged}");
        $this->info("Falhas:         {$failed}");

        if ($mismatch > 0 && ! $dry) {
            $this->warn('');
            $this->warn("ATENCAO: {$mismatch} instance(s) tinham ig_business_account_id ERRADO (cross-tenant).");
            $this->warn('Webhooks futuros vao ser roteados pra instance correta agora.');
        }

        return self::SUCCESS;
    }
}
