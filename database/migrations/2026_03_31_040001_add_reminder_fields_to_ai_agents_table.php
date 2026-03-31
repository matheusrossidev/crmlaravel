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
            $table->text('reminder_message_template')->nullable()->after('calendar_id');
            $table->json('reminder_offsets')->nullable()->after('reminder_message_template');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropColumn(['reminder_message_template', 'reminder_offsets']);
        });
    }
};
