<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('totp_secret')->nullable()->after('notification_preferences');
            $table->boolean('totp_enabled')->default(false)->after('totp_secret');
            $table->text('totp_backup_codes')->nullable()->after('totp_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['totp_secret', 'totp_enabled', 'totp_backup_codes']);
        });
    }
};
