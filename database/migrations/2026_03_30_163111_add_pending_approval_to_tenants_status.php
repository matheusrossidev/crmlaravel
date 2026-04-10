<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tenants MODIFY COLUMN status ENUM('active','inactive','trial','suspended','partner','pending_approval','rejected') DEFAULT 'trial'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tenants MODIFY COLUMN status ENUM('active','inactive','trial','suspended','partner') DEFAULT 'trial'");
        }
    }
};
