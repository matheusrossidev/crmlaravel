<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_configurations', function (Blueprint $table) {
            $table->id();
            // Configuração global da plataforma — sem tenant_id (super admin configura uma vez)
            $table->string('llm_provider', 30)->default('openai'); // openai | anthropic | google
            $table->text('llm_api_key')->nullable();               // chave API
            $table->string('llm_model', 80)->nullable();           // e.g. gpt-4o-mini
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_configurations');
    }
};
