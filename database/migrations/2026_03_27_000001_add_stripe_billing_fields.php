<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('billing_provider', 10)->default('asaas')->after('locale');
            $table->string('billing_country', 2)->default('BR')->after('billing_provider');
            $table->string('billing_currency', 3)->default('BRL')->after('billing_country');
            $table->string('stripe_customer_id', 191)->nullable()->after('billing_currency');
            $table->string('stripe_subscription_id', 191)->nullable()->after('stripe_customer_id');
        });

        Schema::table('plan_definitions', function (Blueprint $table) {
            $table->decimal('price_usd', 10, 2)->default(0)->after('price_monthly');
            $table->string('stripe_price_id', 191)->nullable()->after('price_usd');
            $table->json('features_en_json')->nullable()->after('features_json');
        });

        Schema::table('token_increment_plans', function (Blueprint $table) {
            $table->decimal('price_usd', 10, 2)->default(0)->after('price');
            $table->string('stripe_price_id', 191)->nullable()->after('price_usd');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['billing_provider', 'billing_country', 'billing_currency', 'stripe_customer_id', 'stripe_subscription_id']);
        });

        Schema::table('plan_definitions', function (Blueprint $table) {
            $table->dropColumn(['price_usd', 'stripe_price_id', 'features_en_json']);
        });

        Schema::table('token_increment_plans', function (Blueprint $table) {
            $table->dropColumn(['price_usd', 'stripe_price_id']);
        });
    }
};
