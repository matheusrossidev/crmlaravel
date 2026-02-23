<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->unsignedTinyInteger('followup_count')->default(0)->after('unread_count');
            $table->timestamp('last_followup_at')->nullable()->after('followup_count');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->dropColumn(['followup_count', 'last_followup_at']);
        });
    }
};
