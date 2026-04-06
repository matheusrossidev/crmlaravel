<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona cloud_message_id pra dedup de mensagens vindas do Cloud API
     * (Meta envia message.id no payload do webhook). Mensagens vindas via
     * WAHA continuam usando waha_message_id como antes.
     */
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->string('cloud_message_id', 128)->nullable()->after('waha_message_id');
            $table->index('cloud_message_id', 'whatsapp_messages_cloud_msg_idx');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropIndex('whatsapp_messages_cloud_msg_idx');
            $table->dropColumn('cloud_message_id');
        });
    }
};
