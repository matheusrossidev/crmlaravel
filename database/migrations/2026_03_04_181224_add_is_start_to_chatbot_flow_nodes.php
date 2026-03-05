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
        Schema::table('chatbot_flow_nodes', function (Blueprint $table) {
            $table->boolean('is_start')->default(false)->after('type');
            $table->index(['flow_id', 'is_start']);
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_flow_nodes', function (Blueprint $table) {
            $table->dropIndex(['chatbot_flow_nodes_flow_id_is_start_index']);
            $table->dropColumn('is_start');
        });
    }
};
