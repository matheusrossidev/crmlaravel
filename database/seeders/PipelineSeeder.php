<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PipelineSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo')->first();
        if (!$tenant) return;

        $pipeline = Pipeline::create([
            'tenant_id' => $tenant->id,
            'name' => 'Vendas',
            'color' => '#3B82F6',
            'is_default' => true,
            'sort_order' => 0,
        ]);

        $stages = [
            ['name' => 'Novo Lead',    'color' => '#6B7280', 'position' => 0, 'is_won' => false, 'is_lost' => false],
            ['name' => 'Contato',      'color' => '#3B82F6', 'position' => 1, 'is_won' => false, 'is_lost' => false],
            ['name' => 'Qualificação', 'color' => '#8B5CF6', 'position' => 2, 'is_won' => false, 'is_lost' => false],
            ['name' => 'Proposta',     'color' => '#F59E0B', 'position' => 3, 'is_won' => false, 'is_lost' => false],
            ['name' => 'Negociação',   'color' => '#F97316', 'position' => 4, 'is_won' => false, 'is_lost' => false],
            ['name' => 'Fechado',      'color' => '#10B981', 'position' => 5, 'is_won' => true,  'is_lost' => false],
            ['name' => 'Perdido',      'color' => '#EF4444', 'position' => 6, 'is_won' => false, 'is_lost' => true],
        ];

        foreach ($stages as $stage) {
            PipelineStage::create(array_merge($stage, ['pipeline_id' => $pipeline->id]));
        }
    }
}
