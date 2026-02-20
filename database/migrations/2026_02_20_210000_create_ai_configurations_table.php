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
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('llm_provider', 30)->default('openai'); // openai | anthropic | google
            $table->string('llm_api_key')->nullable();             // encrypted at application level
            $table->string('llm_model', 80)->nullable();           // e.g. gpt-4o, claude-3-5-sonnet
            $table->timestamps();

            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_configurations');
    }
};
