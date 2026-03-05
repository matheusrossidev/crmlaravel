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
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedInteger('max_chatbot_flows')->default(0)->after('max_custom_fields');
            $table->unsignedInteger('max_ai_agents')->default(0)->after('max_chatbot_flows');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['max_chatbot_flows', 'max_ai_agents']);
        });
    }
};
