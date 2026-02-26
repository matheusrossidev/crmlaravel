<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_definitions', function (Blueprint $table) {
            // Dias de trial gratuito; null = sem trial (plano pago imediato)
            $table->unsignedSmallInteger('trial_days')->nullable()->default(null)->after('price_monthly');
        });

        // Atualizar os planos existentes com valores padrão de trial
        DB::table('plan_definitions')->where('name', 'trial')->update(['trial_days' => 14]);
        DB::table('plan_definitions')->where('name', 'free')->update(['trial_days' => 0]);
    }

    public function down(): void
    {
        Schema::table('plan_definitions', function (Blueprint $table) {
            $table->dropColumn('trial_days');
        });
    }
};
