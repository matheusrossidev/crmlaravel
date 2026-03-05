<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_flows', function (Blueprint $table) {
            $table->json('steps')->nullable()->after('variables');
        });

        Schema::table('website_conversations', function (Blueprint $table) {
            $table->json('chatbot_cursor')->nullable()->after('chatbot_node_id');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_flows', function (Blueprint $table) {
            $table->dropColumn('steps');
        });

        Schema::table('website_conversations', function (Blueprint $table) {
            $table->dropColumn('chatbot_cursor');
        });
    }
};
