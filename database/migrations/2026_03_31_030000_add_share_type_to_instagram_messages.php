<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE instagram_messages MODIFY COLUMN `type` ENUM('text','image','audio','video','document','sticker','reaction','share','story_mention') NOT NULL DEFAULT 'text'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE instagram_messages MODIFY COLUMN `type` ENUM('text','image','audio','video','document','sticker','reaction') NOT NULL DEFAULT 'text'");
        }
    }
};
