<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AiAgent;
use App\Services\AgnoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconfigureAgnoAgents extends Command
{
    protected $signature   = 'agno:reconfigure-all {--wait=0 : Segundos pra esperar o Agno ficar disponivel antes de comecar}';
    protected $description = 'Reconfigura todos os agents ativos no Agno (repopula o cache in-memory). Roda no boot do container.';

    public function handle(AgnoService $agno): int
    {
        // Espera opcional pra deixar o syncro_agno subir antes (usado no entrypoint).
        $wait = (int) $this->option('wait');
        if ($wait > 0) {
            $this->line("Aguardando Agno ficar disponivel (max {$wait}s)...");
            $deadline = time() + $wait;
            while (time() < $deadline) {
                if ($agno->isAvailable()) {
                    $this->line('Agno OK.');
                    break;
                }
                sleep(2);
            }
        }

        if (! $agno->isAvailable()) {
            $this->warn('Agno indisponivel — pulando reconfigure (vai rodar de novo no proximo boot).');
            return self::SUCCESS;
        }

        $agents = AiAgent::withoutGlobalScope('tenant')
            ->where('use_agno', true)
            ->where('is_active', true)
            ->get();

        if ($agents->isEmpty()) {
            $this->line('Nenhum agente Agno ativo encontrado.');
            return self::SUCCESS;
        }

        $this->line("Reconfigurando {$agents->count()} agente(s) no Agno...");

        $ok    = 0;
        $fails = 0;
        foreach ($agents as $agent) {
            try {
                $agno->configureFromAgent($agent);
                $this->line("  ✓ #{$agent->id} {$agent->name} (tenant {$agent->tenant_id})");
                $ok++;
            } catch (\Throwable $e) {
                $this->error("  ✗ #{$agent->id} {$agent->name}: {$e->getMessage()}");
                Log::channel('whatsapp')->error('agno:reconfigure-all falhou pra agent', [
                    'agent_id' => $agent->id,
                    'error'    => $e->getMessage(),
                ]);
                $fails++;
            }
        }

        $this->line("Done. ok={$ok} fail={$fails}");
        return self::SUCCESS;
    }
}
