<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expandir enum status para incluir 'partner'
        DB::statement("ALTER TABLE tenants MODIFY status ENUM('active','inactive','trial','suspended','partner') NOT NULL DEFAULT 'trial'");

        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('trial_ends_at');
        });

        DB::statement("ALTER TABLE tenants MODIFY status ENUM('active','inactive','trial','suspended') NOT NULL DEFAULT 'trial'");
    }
};
