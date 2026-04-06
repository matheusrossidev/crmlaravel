<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona suporte a múltiplos providers de WhatsApp:
     *  - waha (atual): web scraping não-oficial
     *  - cloud_api: WhatsApp Cloud API oficial da Meta com modo Coexistence
     *
     * Instâncias existentes mantêm provider='waha' por default.
     */
    public function up(): void
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->string('provider', 20)->default('waha')->after('status');
            $table->string('phone_number_id', 64)->nullable()->after('phone_number');
            $table->string('waba_id', 64)->nullable()->after('phone_number_id');
            $table->string('business_account_id', 64)->nullable()->after('waba_id');
            $table->text('access_token')->nullable()->after('business_account_id');
            $table->timestamp('token_expires_at')->nullable()->after('access_token');

            $table->index(['provider', 'phone_number_id'], 'whatsapp_instances_provider_phone_idx');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->dropIndex('whatsapp_instances_provider_phone_idx');
            $table->dropColumn([
                'provider',
                'phone_number_id',
                'waba_id',
                'business_account_id',
                'access_token',
                'token_expires_at',
            ]);
        });
    }
};
