<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LostSaleReason;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class LostSaleReasonSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo')->first();
        if (!$tenant) return;

        $reasons = [
            'Preço muito alto',
            'Escolheu o concorrente',
            'Não tinha budget',
            'Não era o momento',
            'Sem interesse no produto',
            'Contato não retornou',
            'Proposta recusada',
            'Outros',
        ];

        foreach ($reasons as $index => $reason) {
            LostSaleReason::create([
                'tenant_id' => $tenant->id,
                'name' => $reason,
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }
    }
}
