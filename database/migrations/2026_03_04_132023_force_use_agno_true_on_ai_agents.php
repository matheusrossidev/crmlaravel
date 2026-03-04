<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Atualiza todos os agentes existentes para usar Agno
        DB::table('ai_agents')->update(['use_agno' => true]);

        // Muda o default da coluna para true
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->boolean('use_agno')->default(true)->change();
        });
    }

    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->boolean('use_agno')->default(false)->change();
        });
    }
};
