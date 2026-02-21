<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table): void {
            $table->unsignedBigInteger('ai_agent_id')->nullable()->after('assigned_user_id');
            $table->foreign('ai_agent_id')->references('id')->on('ai_agents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table): void {
            $table->dropForeign(['ai_agent_id']);
            $table->dropColumn('ai_agent_id');
        });
    }
};
