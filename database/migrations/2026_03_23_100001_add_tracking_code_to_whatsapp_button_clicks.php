<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_button_clicks', function (Blueprint $table) {
            $table->string('tracking_code', 6)->nullable()->after('ip_hash');
            $table->boolean('matched')->default(false)->after('tracking_code');
            $table->unique('tracking_code');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_button_clicks', function (Blueprint $table) {
            $table->dropUnique(['tracking_code']);
            $table->dropColumn(['tracking_code', 'matched']);
        });
    }
};
