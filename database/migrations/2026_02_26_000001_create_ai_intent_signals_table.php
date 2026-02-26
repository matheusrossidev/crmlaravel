<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_intent_signals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('ai_agent_id')->nullable();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->string('contact_name', 191);
            $table->string('phone', 30);
            $table->enum('intent_type', ['buy', 'schedule', 'close', 'interest']);
            $table->text('context');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('ai_agent_id')->references('id')->on('ai_agents')->nullOnDelete();
            $table->foreign('conversation_id')->references('id')->on('whatsapp_conversations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_intent_signals');
    }
};
