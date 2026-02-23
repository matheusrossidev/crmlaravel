<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agent_knowledge_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_agent_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('original_name', 255);
            $table->string('storage_path', 500);
            $table->string('mime_type', 100);
            $table->longText('extracted_text')->nullable();
            $table->enum('status', ['pending', 'done', 'failed'])->default('pending');
            $table->string('error_message', 500)->nullable();
            $table->timestamps();

            $table->foreign('ai_agent_id')->references('id')->on('ai_agents')->onDelete('cascade');
            $table->index(['ai_agent_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_knowledge_files');
    }
};
