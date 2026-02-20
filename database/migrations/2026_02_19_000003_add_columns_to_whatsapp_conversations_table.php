<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->foreignId('instance_id')->nullable()->after('tenant_id')
                  ->constrained('whatsapp_instances')->nullOnDelete();
            $table->string('contact_name')->nullable()->after('phone');
            $table->string('contact_picture_url')->nullable()->after('contact_name');
            $table->foreignId('assigned_user_id')->nullable()->after('status')
                  ->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('unread_count')->default(0)->after('assigned_user_id');
            $table->timestamp('closed_at')->nullable()->after('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->dropForeign(['instance_id']);
            $table->dropForeign(['assigned_user_id']);
            $table->dropColumn(['instance_id', 'contact_name', 'contact_picture_url', 'assigned_user_id', 'unread_count', 'closed_at']);
        });
    }
};
