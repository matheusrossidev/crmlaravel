<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chatbot_flows', function (Blueprint $table) {
            // NULL = flow funciona em TODAS instâncias whatsapp do tenant (backward compatible).
            // Valor explícito = flow roda SÓ nessa instância (permite flow "comercial" no
            // número A e "suporte" no número B).
            $table->foreignId('whatsapp_instance_id')
                ->nullable()
                ->after('channel')
                ->constrained('whatsapp_instances')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_flows', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_instance_id']);
            $table->dropColumn('whatsapp_instance_id');
        });
    }
};
