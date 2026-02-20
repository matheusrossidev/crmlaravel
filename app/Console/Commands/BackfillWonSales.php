<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Sale;
use Illuminate\Console\Command;

class BackfillWonSales extends Command
{
    protected $signature   = 'sales:backfill-won';
    protected $description = 'Cria registros em sales para leads já em etapas "ganhas" que não possuem venda registrada.';

    public function handle(): int
    {
        // Busca todos os leads em stages is_won (sem filtro de tenant)
        $wonLeads = Lead::withoutGlobalScopes()
            ->whereHas('stage', fn ($q) => $q->withoutGlobalScopes()->where('is_won', true))
            ->get();

        if ($wonLeads->isEmpty()) {
            $this->info('Nenhum lead em etapa "ganho" encontrado.');
            return self::SUCCESS;
        }

        // IDs que já têm Sale registrado
        $alreadyHasSale = Sale::withoutGlobalScopes()
            ->whereIn('lead_id', $wonLeads->pluck('id'))
            ->pluck('lead_id')
            ->flip()
            ->toArray();

        $created = 0;

        foreach ($wonLeads as $lead) {
            if (isset($alreadyHasSale[$lead->id])) {
                continue;
            }

            Sale::withoutGlobalScopes()->create([
                'tenant_id'   => $lead->tenant_id,
                'lead_id'     => $lead->id,
                'pipeline_id' => $lead->pipeline_id,
                'campaign_id' => $lead->campaign_id,
                'value'       => $lead->value ?? 0,
                'closed_by'   => null,
                'closed_at'   => $lead->updated_at ?? now(),
            ]);

            $created++;
        }

        $this->info("Backfill concluído: {$created} registro(s) de venda criado(s).");

        return self::SUCCESS;
    }
}
