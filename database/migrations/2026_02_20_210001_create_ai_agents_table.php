<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Identidade
            $table->string('name', 100);
            $table->enum('objective', ['sales', 'support', 'general'])->default('general');
            $table->enum('communication_style', ['formal', 'normal', 'casual'])->default('normal');
            $table->string('company_name', 150)->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('language', 10)->default('pt-BR');

            // Persona e comportamento
            $table->text('persona_description')->nullable();
            $table->text('behavior')->nullable();

            // Fluxo
            $table->text('on_finish_action')->nullable();
            $table->text('on_transfer_message')->nullable();
            $table->text('on_invalid_response')->nullable();

            // Etapas da conversa (lista dinâmica)
            $table->json('conversation_stages')->nullable(); // [{name, description}]

            // Base de conhecimento
            $table->longText('knowledge_base')->nullable();

            // Configurações avançadas
            $table->unsignedSmallInteger('max_message_length')->default(500);
            $table->unsignedTinyInteger('response_delay_seconds')->default(2);

            // Canal e status
            $table->enum('channel', ['whatsapp', 'web_chat'])->default('whatsapp');
            $table->boolean('is_active')->default(false);

            $table->timestamps();
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agents');
    }
};
