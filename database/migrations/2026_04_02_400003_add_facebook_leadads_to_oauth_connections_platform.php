<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE oauth_connections MODIFY COLUMN platform VARCHAR(30) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE oauth_connections MODIFY COLUMN platform ENUM('facebook','google','facebook_leadads') NOT NULL");
    }
};
