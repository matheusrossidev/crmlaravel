<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona flag pra permitir leads duplicados em uma form connection.
     * Default = true porque tráfego pago (Lead Ads) sempre deve capturar
     * cada submissão — dedup orgânico não faz sentido pra anúncios pagos.
     */
    public function up(): void
    {
        Schema::table('facebook_lead_form_connections', function (Blueprint $table) {
            $table->boolean('allow_duplicates')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('facebook_lead_form_connections', function (Blueprint $table) {
            $table->dropColumn('allow_duplicates');
        });
    }
};
