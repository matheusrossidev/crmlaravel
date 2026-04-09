<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agent_knowledge_files', function (Blueprint $table): void {
            // Numero de chunks vetorizados no Agno apos o /index-file
            $table->unsignedInteger('chunks_count')->nullable()->after('extracted_text');
            // Quando foi indexado pela ultima vez (NULL = nunca foi pro Agno ainda)
            $table->timestamp('indexed_at')->nullable()->after('chunks_count');
            // Mensagem de erro do indexer caso falhe (separada do error_message do extractor)
            $table->text('indexing_error')->nullable()->after('indexed_at');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agent_knowledge_files', function (Blueprint $table): void {
            $table->dropColumn(['chunks_count', 'indexed_at', 'indexing_error']);
        });
    }
};
