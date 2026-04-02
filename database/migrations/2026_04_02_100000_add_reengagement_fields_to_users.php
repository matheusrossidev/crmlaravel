<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->timestamp('last_reengagement_sent_at')->nullable()->after('last_login_at');
            $table->string('reengagement_stage', 10)->nullable()->after('last_reengagement_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'last_reengagement_sent_at', 'reengagement_stage']);
        });
    }
};
