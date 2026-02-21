<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instance_id')->constrained('instagram_instances')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('igsid', 50);
            $table->string('contact_name', 100)->nullable();
            $table->string('contact_username', 100)->nullable();
            $table->string('contact_picture_url')->nullable();
            $table->json('tags')->nullable();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ai_agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['instance_id', 'igsid']);
            $table->index(['tenant_id', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_conversations');
    }
};
