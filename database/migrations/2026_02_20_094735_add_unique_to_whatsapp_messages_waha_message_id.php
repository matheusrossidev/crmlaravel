<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove duplicatas antes de criar o índice único.
        // Mantém apenas o registro com menor ID para cada waha_message_id duplicado.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                DELETE wm1
                FROM whatsapp_messages wm1
                INNER JOIN whatsapp_messages wm2
                    ON  wm1.waha_message_id = wm2.waha_message_id
                    AND wm1.id > wm2.id
                WHERE wm1.waha_message_id IS NOT NULL
            ');
        } else {
            // SQLite-compatible: delete duplicates keeping lowest ID
            DB::statement('
                DELETE FROM whatsapp_messages
                WHERE id NOT IN (
                    SELECT MIN(id) FROM whatsapp_messages
                    WHERE waha_message_id IS NOT NULL
                    GROUP BY waha_message_id
                ) AND waha_message_id IS NOT NULL
            ');
        }

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->unique('waha_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropUnique(['waha_message_id']);
        });
    }
};
