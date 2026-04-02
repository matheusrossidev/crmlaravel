<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Prevent duplicate commissions from webhook retries
        Schema::table('partner_commissions', function (Blueprint $table) {
            $table->unique('asaas_payment_id', 'partner_commissions_asaas_payment_unique');
        });
    }

    public function down(): void
    {
        Schema::table('partner_commissions', function (Blueprint $table) {
            $table->dropUnique('partner_commissions_asaas_payment_unique');
        });
    }
};
