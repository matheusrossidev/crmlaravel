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
        Schema::table('plan_definitions', function (Blueprint $table) {
            $table->boolean('is_visible')->default(true)->after('is_active');
        });

        // Planos internos: não visíveis para seleção de usuários
        DB::table('plan_definitions')
            ->whereIn('name', ['partner', 'trial', 'free'])
            ->update(['is_visible' => false]);
    }

    public function down(): void
    {
        Schema::table('plan_definitions', function (Blueprint $table) {
            $table->dropColumn('is_visible');
        });
    }
};
