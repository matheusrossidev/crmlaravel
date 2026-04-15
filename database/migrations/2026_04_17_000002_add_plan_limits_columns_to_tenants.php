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
            $table->unsignedInteger('max_automations')->nullable()->after('max_whatsapp_instances');
            $table->unsignedInteger('max_nurture_sequences')->nullable()->after('max_automations');
            $table->unsignedInteger('max_forms')->nullable()->after('max_nurture_sequences');
            $table->unsignedInteger('max_whatsapp_templates')->nullable()->after('max_forms');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'max_automations',
                'max_nurture_sequences',
                'max_forms',
                'max_whatsapp_templates',
            ]);
        });
    }
};
