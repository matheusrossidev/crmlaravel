<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            // smart    = prefere texto livre; fora da janela 24h usa template se configurado, senão pula
            // template = SEMPRE envia via template (mesmo dentro da janela)
            // off      = sem follow-up
            $table->string('followup_strategy', 16)->default('smart');

            // Template HSM aprovado usado como fallback (smart) ou obrigatório (template).
            // NULL = sem template configurado → smart pula quando janela fecha.
            $table->foreignId('followup_template_id')
                ->nullable()
                ->constrained('whatsapp_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropForeign(['followup_template_id']);
            $table->dropColumn(['followup_strategy', 'followup_template_id']);
        });
    }
};
