<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Torna as colunas `max_*` antigas nullable pra bater com o design novo:
 * NULL = "herdar do plano" (shadow copy pode ser apagada pra seguir o PlanDefinition).
 *
 * Colunas afetadas: todas as max_* que existiam antes do refactor de limites/features.
 * As novas (max_automations/max_nurture_sequences/max_forms/max_whatsapp_templates)
 * já nasceram nullable na migration 2026_04_17_000002.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedInteger('max_users')->nullable()->change();
            $table->unsignedInteger('max_leads')->nullable()->change();
            $table->unsignedInteger('max_pipelines')->nullable()->change();
            $table->unsignedInteger('max_custom_fields')->nullable()->change();
            $table->unsignedInteger('max_chatbot_flows')->nullable()->change();
            $table->unsignedInteger('max_ai_agents')->nullable()->change();
            $table->unsignedInteger('max_departments')->nullable()->change();
            $table->unsignedInteger('max_whatsapp_instances')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Não reverte: reverter forçaria popular valores em rows que tem NULL agora.
        // Mantemos a semântica nullable pra frente.
    }
};
