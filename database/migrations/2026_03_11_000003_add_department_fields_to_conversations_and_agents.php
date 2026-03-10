<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('assigned_user_id')->constrained('departments')->nullOnDelete();
        });

        Schema::table('instagram_conversations', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('assigned_user_id')->constrained('departments')->nullOnDelete();
        });

        Schema::table('ai_agents', function (Blueprint $table) {
            $table->foreignId('transfer_to_department_id')->nullable()->after('transfer_to_user_id')->constrained('departments')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_see_all_conversations')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::table('instagram_conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transfer_to_department_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('can_see_all_conversations');
        });
    }
};
