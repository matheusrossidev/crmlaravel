<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instagram_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('chatbot_flow_id')->nullable()->after('ai_agent_id');
            $table->unsignedBigInteger('chatbot_node_id')->nullable()->after('chatbot_flow_id');
            $table->json('chatbot_variables')->nullable()->after('chatbot_node_id');

            $table->foreign('chatbot_flow_id')
                  ->references('id')->on('chatbot_flows')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('instagram_conversations', function (Blueprint $table) {
            $table->dropForeign(['chatbot_flow_id']);
            $table->dropColumn(['chatbot_flow_id', 'chatbot_node_id', 'chatbot_variables']);
        });
    }
};
