<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ai_agents MODIFY channel ENUM('whatsapp','web_chat','instagram') NOT NULL DEFAULT 'whatsapp'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ai_agents MODIFY channel ENUM('whatsapp','web_chat') NOT NULL DEFAULT 'whatsapp'");
        }
    }
};
