<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AiAgent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AgnoService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('AGNO_SERVICE_URL', 'http://agno:8000'), '/');
    }

    /**
     * Check if the Agno service is reachable.
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/health");
            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Send a message to the Agno AI agent and get a response.
     *
     * @return array{reply_blocks: string[], actions: array[], tokens_prompt: int, tokens_completion: int, tokens_total: int, model: string, provider: string}
     */
    public function chat(array $payload): array
    {
        $response = Http::timeout(30)->post("{$this->baseUrl}/chat", $payload);

        if ($response->failed()) {
            // Log estruturado com detalhes pra facilitar debug. Antes, falha
            // no Agno (ex: 422 Pydantic) so jogava status na exception sem
            // body — diagnosticar bug do payload era impossivel sem reproduzir.
            Log::channel('whatsapp')->error('AgnoService chat failed', [
                'agent_id'    => $payload['agent_id']    ?? null,
                'tenant_id'   => $payload['tenant_id']   ?? null,
                'conv_id'     => $payload['conversation_id'] ?? null,
                'status'      => $response->status(),
                'body'        => mb_substr($response->body(), 0, 2000),
                'msg_len'     => strlen($payload['message'] ?? ''),
                'has_phone'   => ! empty($payload['contact_phone']),
                'history_len' => count($payload['history'] ?? []),
            ]);
            throw new \RuntimeException("Agno service error [{$response->status()}]: {$response->body()}");
        }

        return $response->json();
    }

    /**
     * Push agent configuration to the Agno service (called on agent save).
     */
    public function configureAgent(int $agentId, array $config): void
    {
        try {
            Http::timeout(10)->post("{$this->baseUrl}/agents/{$agentId}/configure", $config);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('AgnoService: configureAgent failed', [
                'agent_id' => $agentId,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build the Agno config payload from an AiAgent model and push it.
     *
     * Centraliza o mapping AiAgent -> payload do Agno em UM lugar so. Antes
     * isso vivia duplicado em AiAgentController::syncToAgno e teria que ser
     * duplicado de novo em qualquer command/job que precise reconfigurar.
     *
     * O Agno guarda o config em memoria (dict Python). Quando o container
     * reinicia, perde tudo — por isso temos um command que reconfigura todos
     * os agents no boot do app pra repopular o cache.
     */
    public function configureFromAgent(AiAgent $agent): void
    {
        if (! $agent->use_agno) {
            return;
        }

        $this->configureAgent($agent->id, [
            'tenant_id'            => $agent->tenant_id,
            'name'                 => $agent->name,
            'objective'            => $agent->objective,
            'company_name'         => $agent->company_name ?? '',
            'industry'             => $agent->industry ?? '',
            'communication_style'  => $agent->communication_style,
            'persona_description'  => $agent->persona_description ?? '',
            'behavior'             => $agent->behavior ?? '',
            'max_message_length'   => $agent->max_message_length ?? 800,
            'knowledge_base_text'  => $agent->knowledge_base ?? '',
            'llm_provider'         => config('ai.provider', 'openai'),
            'llm_model'            => config('ai.model', 'gpt-4o-mini'),
            'llm_api_key'          => config('ai.api_key', ''),
            'enable_pipeline_tool' => (bool) $agent->enable_pipeline_tool,
            'enable_tags_tool'     => (bool) $agent->enable_tags_tool,
            'enable_intent_notify' => (bool) $agent->enable_intent_notify,
            'enable_calendar_tool' => (bool) $agent->enable_calendar_tool,
            'language'             => $agent->language ?? 'pt-BR',
        ]);
    }

    /**
     * Store a conversation memory (summary) for an agent.
     */
    public function storeMemory(int $agentId, array $payload): bool
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/agents/{$agentId}/memories/store", $payload);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('AgnoService: storeMemory failed', [
                'agent_id' => $agentId,
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Search for relevant memories for an agent based on a query.
     *
     * @return array<int, array{summary: string, customer_profile: string|null, similarity: float}>
     */
    public function searchMemories(int $agentId, array $payload): array
    {
        try {
            $response = Http::timeout(15)->post("{$this->baseUrl}/agents/{$agentId}/memories/search", $payload);
            if ($response->successful()) {
                return $response->json('memories') ?? [];
            }
            return [];
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('AgnoService: searchMemories failed', [
                'agent_id' => $agentId,
                'error'    => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Index a knowledge file in the Agno vector store (called after file upload).
     */
    /**
     * Indexa um arquivo no Agno (chunkifica + embeda + salva no pgvector).
     * Retorna {ok, chunks_count, tokens_used} ou null em caso de falha.
     */
    public function indexFile(int $agentId, int $tenantId, int $fileId, string $text, string $filename): ?array
    {
        try {
            $r = Http::timeout(120)->post("{$this->baseUrl}/agents/{$agentId}/index-file", [
                'tenant_id' => $tenantId,
                'file_id'   => $fileId,
                'text'      => $text,
                'filename'  => $filename,
            ]);
            if ($r->failed()) {
                Log::channel('whatsapp')->warning('AgnoService: indexFile non-2xx', [
                    'agent_id' => $agentId,
                    'file_id'  => $fileId,
                    'status'   => $r->status(),
                    'body'     => mb_substr($r->body(), 0, 500),
                ]);
                return null;
            }
            return $r->json();
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('AgnoService: indexFile failed', [
                'agent_id' => $agentId,
                'file_id'  => $fileId,
                'filename' => $filename,
                'error'    => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Busca top-K chunks relevantes pra uma query (RAG retrieval).
     * Chamado antes de cada /chat pra injetar contexto da base de conhecimento.
     */
    public function searchKnowledge(int $agentId, int $tenantId, string $query, int $topK = 5): array
    {
        try {
            $r = Http::timeout(10)->post("{$this->baseUrl}/agents/{$agentId}/knowledge/search", [
                'tenant_id' => $tenantId,
                'query'     => $query,
                'top_k'     => $topK,
            ]);
            return $r->successful() ? ($r->json()['chunks'] ?? []) : [];
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('AgnoService: searchKnowledge failed', [
                'agent_id' => $agentId,
                'error'    => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Apaga todos os chunks de um arquivo (chamado quando o user deleta o arquivo).
     */
    public function deleteKnowledgeFile(int $agentId, int $fileId): void
    {
        try {
            Http::timeout(10)->delete("{$this->baseUrl}/agents/{$agentId}/knowledge/{$fileId}");
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('AgnoService: deleteKnowledgeFile failed', [
                'agent_id' => $agentId,
                'file_id'  => $fileId,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
