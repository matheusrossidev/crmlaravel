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
        Schema::create('chatbot_flow_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('chatbot_flows')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('source_node_id');
            $table->string('source_handle', 50)->default('default');
            $table->unsignedBigInteger('target_node_id');
            $table->timestamps();
            $table->unique(['flow_id', 'source_node_id', 'source_handle'], 'chatbot_edges_unique');
            $table->index('flow_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_flow_edges');
    }
};
