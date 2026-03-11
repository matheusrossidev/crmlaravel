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
            $table->boolean('enable_voice_reply')->default(false)->after('enable_calendar_tool');
            $table->string('elevenlabs_voice_id', 100)->nullable()->after('enable_voice_reply');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropColumn(['enable_voice_reply', 'elevenlabs_voice_id']);
        });
    }
};
