<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE whatsapp_messages MODIFY COLUMN `type` ENUM('text','image','audio','video','document','sticker','reaction','note','event','location') NOT NULL DEFAULT 'text'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE whatsapp_messages MODIFY COLUMN `type` ENUM('text','image','audio','video','document','sticker','reaction','note') NOT NULL DEFAULT 'text'");
    }
};
