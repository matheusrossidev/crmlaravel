<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 191)->nullable();
            $table->string('icon', 50)->nullable();
            $table->string('color', 7)->nullable();
            $table->foreignId('default_ai_agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
            $table->foreignId('default_chatbot_flow_id')->nullable()->constrained('chatbot_flows')->nullOnDelete();
            $table->string('assignment_strategy', 20)->default('round_robin');
            $table->unsignedBigInteger('last_assigned_user_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
