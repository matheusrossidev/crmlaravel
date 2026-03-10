<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add widget settings to ai_agents
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->string('website_token', 64)->nullable()->unique()->after('channel');
            $table->string('bot_name', 100)->nullable()->after('website_token');
            $table->string('bot_avatar', 500)->nullable()->after('bot_name');
            $table->text('welcome_message')->nullable()->after('bot_avatar');
            $table->string('widget_type', 10)->default('bubble')->after('welcome_message');
            $table->string('widget_color', 10)->default('#0085f3')->after('widget_type');
        });

        // Make flow_id nullable + add ai_agent_id to website_conversations
        Schema::table('website_conversations', function (Blueprint $table) {
            $table->dropForeign(['flow_id']);
        });

        Schema::table('website_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('flow_id')->nullable()->change();
            $table->unsignedBigInteger('ai_agent_id')->nullable()->after('flow_id');

            $table->foreign('flow_id')->references('id')->on('chatbot_flows')->nullOnDelete();
            $table->foreign('ai_agent_id')->references('id')->on('ai_agents')->nullOnDelete();
            $table->index('ai_agent_id');
        });
    }

    public function down(): void
    {
        Schema::table('website_conversations', function (Blueprint $table) {
            $table->dropForeign(['ai_agent_id']);
            $table->dropIndex(['ai_agent_id']);
            $table->dropColumn('ai_agent_id');

            $table->dropForeign(['flow_id']);
        });

        Schema::table('website_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('flow_id')->nullable(false)->change();
            $table->foreign('flow_id')->references('id')->on('chatbot_flows')->cascadeOnDelete();
        });

        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropColumn([
                'website_token', 'bot_name', 'bot_avatar',
                'welcome_message', 'widget_type', 'widget_color',
            ]);
        });
    }
};
