<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agents', function (Blueprint $table): void {
            $table->boolean('enable_calendar_tool')->default(false)->after('enable_intent_notify');
            $table->text('calendar_tool_instructions')->nullable()->after('enable_calendar_tool');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table): void {
            $table->dropColumn(['enable_calendar_tool', 'calendar_tool_instructions']);
        });
    }
};
