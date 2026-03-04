<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('flow_id');
            $table->string('visitor_id', 64);
            $table->string('contact_name', 100)->nullable();
            $table->string('contact_email', 100)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('chatbot_node_id')->nullable();
            $table->json('chatbot_variables')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('last_message_at')->nullable();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('flow_id')->references('id')->on('chatbot_flows')->cascadeOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();

            $table->unique(['flow_id', 'visitor_id']);
            $table->index(['tenant_id', 'status']);
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_conversations');
    }
};
