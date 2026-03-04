<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chatbot_flows', function (Blueprint $table) {
            $table->string('bot_name', 100)->nullable()->after('website_token');
            $table->string('bot_avatar', 500)->nullable()->after('bot_name');
            $table->text('welcome_message')->nullable()->after('bot_avatar');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_flows', function (Blueprint $table) {
            $table->dropColumn(['bot_name', 'bot_avatar', 'welcome_message']);
        });
    }
};
