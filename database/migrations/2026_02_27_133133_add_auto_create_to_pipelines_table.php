<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pipelines', function (Blueprint $table) {
            $table->boolean('auto_create_lead')->default(true)->after('sort_order');
            $table->boolean('auto_create_from_whatsapp')->default(true)->after('auto_create_lead');
            $table->boolean('auto_create_from_instagram')->default(true)->after('auto_create_from_whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('pipelines', function (Blueprint $table) {
            $table->dropColumn(['auto_create_lead', 'auto_create_from_whatsapp', 'auto_create_from_instagram']);
        });
    }
};
