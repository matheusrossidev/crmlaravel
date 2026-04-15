<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            // Default country (ISO-2): país que abre selecionado no seletor.
            // BR = padrão da plataforma (base brasileira).
            $table->string('default_country', 2)->default('BR')->after('widget_position');

            // Países permitidos (array ISO-2). NULL = todos os países.
            // Ex: ['BR', 'US', 'PT'] limita o seletor a esses 3.
            $table->json('allowed_countries')->nullable()->after('default_country');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn(['default_country', 'allowed_countries']);
        });
    }
};
