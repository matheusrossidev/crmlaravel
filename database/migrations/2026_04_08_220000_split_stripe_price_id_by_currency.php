<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Stripe tem 2 produtos diferentes (BRL e USD) com prices diferentes.
        // Antes existia so 1 coluna stripe_price_id — separamos em 2.
        Schema::table('plan_definitions', function (Blueprint $table): void {
            if (! Schema::hasColumn('plan_definitions', 'stripe_price_id_brl')) {
                $table->string('stripe_price_id_brl', 191)->nullable()->after('stripe_price_id');
            }
            if (! Schema::hasColumn('plan_definitions', 'stripe_price_id_usd')) {
                $table->string('stripe_price_id_usd', 191)->nullable()->after('stripe_price_id_brl');
            }
        });

        // Migra dados existentes: se ja tinha algum stripe_price_id setado,
        // copia pra coluna USD (assumindo que era pra clientes internacionais
        // — pelo padrao atual de billing_provider='stripe' so pra locale=en).
        DB::table('plan_definitions')
            ->whereNotNull('stripe_price_id')
            ->update(['stripe_price_id_usd' => DB::raw('stripe_price_id')]);

        // Mesma logica pra token_increment_plans — eles tambem tem 2 produtos
        // (BRL e USD) e ja tinham stripe_price_id (ver migration de 2026_03_03)
        if (Schema::hasTable('token_increment_plans')) {
            Schema::table('token_increment_plans', function (Blueprint $table): void {
                if (! Schema::hasColumn('token_increment_plans', 'stripe_price_id_brl')) {
                    $table->string('stripe_price_id_brl', 191)->nullable()->after('stripe_price_id');
                }
                if (! Schema::hasColumn('token_increment_plans', 'stripe_price_id_usd')) {
                    $table->string('stripe_price_id_usd', 191)->nullable()->after('stripe_price_id_brl');
                }
            });

            DB::table('token_increment_plans')
                ->whereNotNull('stripe_price_id')
                ->update(['stripe_price_id_usd' => DB::raw('stripe_price_id')]);
        }
    }

    public function down(): void
    {
        Schema::table('plan_definitions', function (Blueprint $table): void {
            if (Schema::hasColumn('plan_definitions', 'stripe_price_id_usd')) {
                $table->dropColumn('stripe_price_id_usd');
            }
            if (Schema::hasColumn('plan_definitions', 'stripe_price_id_brl')) {
                $table->dropColumn('stripe_price_id_brl');
            }
        });

        if (Schema::hasTable('token_increment_plans')) {
            Schema::table('token_increment_plans', function (Blueprint $table): void {
                if (Schema::hasColumn('token_increment_plans', 'stripe_price_id_usd')) {
                    $table->dropColumn('stripe_price_id_usd');
                }
                if (Schema::hasColumn('token_increment_plans', 'stripe_price_id_brl')) {
                    $table->dropColumn('stripe_price_id_brl');
                }
            });
        }
    }
};
