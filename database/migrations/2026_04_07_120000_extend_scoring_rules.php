<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lead Scoring 2.0 — Fase 1
 *
 * Adiciona campos de filtro estrutural e limites a `scoring_rules`:
 *  - pipeline_id / stage_id     → restringe regra a um funil/etapa específicos
 *  - valid_from / valid_until   → janela de validade temporal (campanhas sazonais)
 *  - max_triggers_per_lead      → limite de disparos por lead na vida toda
 *
 * Score min/max GLOBAL (Fix 7) é armazenado em `tenants.settings_json`
 * (chaves `score_min` / `score_max`) — não precisa de coluna nova.
 *
 * Tudo nullable pra rollback safety. FKs com nullOnDelete pra não quebrar
 * regras quando pipeline/stage for deletado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scoring_rules', function (Blueprint $table) {
            // Filtros estruturais (Fix 1, 2)
            $table->unsignedBigInteger('pipeline_id')->nullable()->after('event_type');
            $table->unsignedBigInteger('stage_id')->nullable()->after('pipeline_id');

            $table->foreign('pipeline_id')
                ->references('id')->on('pipelines')
                ->nullOnDelete();

            $table->foreign('stage_id')
                ->references('id')->on('pipeline_stages')
                ->nullOnDelete();

            // Validade (Fix 5)
            $table->date('valid_from')->nullable()->after('cooldown_hours');
            $table->date('valid_until')->nullable()->after('valid_from');

            // Limite de disparos por lead (Fix 6) — null = sem limite
            $table->unsignedSmallInteger('max_triggers_per_lead')
                ->nullable()
                ->after('valid_until');
        });
    }

    public function down(): void
    {
        Schema::table('scoring_rules', function (Blueprint $table) {
            $table->dropForeign(['pipeline_id']);
            $table->dropForeign(['stage_id']);
            $table->dropColumn([
                'pipeline_id',
                'stage_id',
                'valid_from',
                'valid_until',
                'max_triggers_per_lead',
            ]);
        });
    }
};
