<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table): void {
            // Tipo do remetente: human, human_phone, ai_agent, chatbot,
            // automation, scheduled, followup, event. NULL = mensagens
            // antigas pre-sent_by (sem badge no chat).
            $table->string('sent_by', 20)->nullable()->after('user_id');
            // FK pro AiAgent quando aplicavel (ai_agent, followup, event-da-IA).
            // NULL pra outros tipos.
            $table->unsignedBigInteger('sent_by_agent_id')->nullable()->after('sent_by');

            $table->index('sent_by', 'idx_whatsapp_messages_sent_by');
            $table->index('sent_by_agent_id', 'idx_whatsapp_messages_sent_by_agent');
        });

        Schema::table('instagram_messages', function (Blueprint $table): void {
            $table->string('sent_by', 20)->nullable()->after('user_id');
            $table->unsignedBigInteger('sent_by_agent_id')->nullable()->after('sent_by');
            $table->index('sent_by', 'idx_instagram_messages_sent_by');
            $table->index('sent_by_agent_id', 'idx_instagram_messages_sent_by_agent');
        });

        Schema::table('website_messages', function (Blueprint $table): void {
            // Website nao tinha user_id ainda — adiciona pra paridade
            if (! Schema::hasColumn('website_messages', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('content');
            }
            $table->string('sent_by', 20)->nullable()->after('user_id');
            $table->unsignedBigInteger('sent_by_agent_id')->nullable()->after('sent_by');
            $table->index('sent_by', 'idx_website_messages_sent_by');
            $table->index('sent_by_agent_id', 'idx_website_messages_sent_by_agent');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table): void {
            $table->dropIndex('idx_whatsapp_messages_sent_by');
            $table->dropIndex('idx_whatsapp_messages_sent_by_agent');
            $table->dropColumn(['sent_by', 'sent_by_agent_id']);
        });

        Schema::table('instagram_messages', function (Blueprint $table): void {
            $table->dropIndex('idx_instagram_messages_sent_by');
            $table->dropIndex('idx_instagram_messages_sent_by_agent');
            $table->dropColumn(['sent_by', 'sent_by_agent_id']);
        });

        Schema::table('website_messages', function (Blueprint $table): void {
            $table->dropIndex('idx_website_messages_sent_by');
            $table->dropIndex('idx_website_messages_sent_by_agent');
            $table->dropColumn(['sent_by', 'sent_by_agent_id', 'user_id']);
        });
    }
};
