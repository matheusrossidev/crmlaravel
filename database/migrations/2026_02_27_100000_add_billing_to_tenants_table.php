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
            $table->string('asaas_customer_id', 50)->nullable()->after('trial_ends_at');
            $table->string('asaas_subscription_id', 50)->nullable()->after('asaas_customer_id');
            $table->string('subscription_status', 20)->nullable()->after('asaas_subscription_id');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_status');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['asaas_customer_id', 'asaas_subscription_id', 'subscription_status', 'subscription_ends_at']);
        });
    }
};
