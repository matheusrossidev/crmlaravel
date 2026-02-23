<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->boolean('followup_enabled')->default(false)->after('auto_assign');
            $table->unsignedSmallInteger('followup_delay_minutes')->default(40)->after('followup_enabled');
            $table->unsignedTinyInteger('followup_max_count')->default(3)->after('followup_delay_minutes');
            $table->unsignedTinyInteger('followup_hour_start')->default(8)->after('followup_max_count');
            $table->unsignedTinyInteger('followup_hour_end')->default(18)->after('followup_hour_start');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropColumn([
                'followup_enabled',
                'followup_delay_minutes',
                'followup_max_count',
                'followup_hour_start',
                'followup_hour_end',
            ]);
        });
    }
};
