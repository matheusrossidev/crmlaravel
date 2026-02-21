<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->foreignId('chatbot_flow_id')
                ->nullable()
                ->after('ai_agent_id')
                ->constrained('chatbot_flows')
                ->nullOnDelete();
            $table->unsignedBigInteger('chatbot_node_id')
                ->nullable()
                ->after('chatbot_flow_id');
            $table->json('chatbot_variables')
                ->nullable()
                ->after('chatbot_node_id');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->dropForeign(['chatbot_flow_id']);
            $table->dropColumn(['chatbot_flow_id', 'chatbot_node_id', 'chatbot_variables']);
        });
    }
};
