<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AiAgent;
use App\Models\AiAgentKnowledgeFile;
use App\Models\AiUsageLog;
use App\Services\AgnoService;
use Illuminate\Console\Command;

class ReindexAgnoKnowledge extends Command
{
    protected $signature   = 'agno:reindex-knowledge
                              {--agent= : Limita ao agent_id especifico}
                              {--file= : Limita ao file_id especifico}
                              {--missing : So reindexa arquivos sem indexed_at}';
    protected $description = 'Reindexa arquivos de knowledge no Agno (chunkifica + embeda + salva no pgvector)';

    public function handle(AgnoService $agno): int
    {
        if (! $agno->isAvailable()) {
            $this->error('Agno indisponivel — abortando.');
            return self::FAILURE;
        }

        $query = AiAgentKnowledgeFile::withoutGlobalScope('tenant')
            ->where('status', 'done')
            ->whereNotNull('extracted_text');

        if ($agentId = $this->option('agent')) {
            $query->where('ai_agent_id', (int) $agentId);
        }
        if ($fileId = $this->option('file')) {
            $query->where('id', (int) $fileId);
        }
        if ($this->option('missing')) {
            $query->whereNull('indexed_at');
        }

        $files = $query->get();

        if ($files->isEmpty()) {
            $this->line('Nenhum arquivo encontrado pra reindexar.');
            return self::SUCCESS;
        }

        $this->line("Reindexando {$files->count()} arquivo(s)...");

        $ok    = 0;
        $fails = 0;
        $totalChunks = 0;

        foreach ($files as $file) {
            $agent = AiAgent::withoutGlobalScope('tenant')->find($file->ai_agent_id);
            if (! $agent || ! $agent->use_agno) {
                $this->line("  - file #{$file->id}: pulado (agent inexistente ou nao usa Agno)");
                continue;
            }

            $result = $agno->indexFile(
                $agent->id,
                $agent->tenant_id,
                $file->id,
                $file->extracted_text,
                $file->original_name,
            );

            if ($result && ($result['ok'] ?? false)) {
                $chunks = $result['chunks_count'] ?? 0;
                $file->update([
                    'chunks_count'   => $chunks,
                    'indexed_at'     => now(),
                    'indexing_error' => null,
                ]);

                try {
                    AiUsageLog::create([
                        'tenant_id'         => $agent->tenant_id,
                        'conversation_id'   => null,
                        'model'             => 'text-embedding-3-small',
                        'provider'          => 'openai',
                        'tokens_prompt'     => $result['tokens_used'] ?? 0,
                        'tokens_completion' => 0,
                        'tokens_total'      => $result['tokens_used'] ?? 0,
                        'type'              => 'knowledge_indexing',
                    ]);
                } catch (\Throwable) {}

                $this->line("  ✓ agent #{$agent->id} file #{$file->id} ({$file->original_name}): {$chunks} chunks");
                $ok++;
                $totalChunks += $chunks;
            } else {
                $err = $result['error'] ?? 'falha desconhecida';
                $file->update(['indexing_error' => $err]);
                $this->error("  ✗ agent #{$agent->id} file #{$file->id}: {$err}");
                $fails++;
            }
        }

        $this->line("Done. ok={$ok} fail={$fails} total_chunks={$totalChunks}");
        return self::SUCCESS;
    }
}
