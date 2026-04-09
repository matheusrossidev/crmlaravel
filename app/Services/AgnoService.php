<?php

declare(strict_types=1);

namespace App\Services;

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
    public function indexFile(int $agentId, int $tenantId, string $text, string $filename): void
    {
        try {
            Http::timeout(60)->post("{$this->baseUrl}/agents/{$agentId}/index-file", [
                'tenant_id' => $tenantId,
                'text'      => $text,
                'filename'  => $filename,
            ]);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('AgnoService: indexFile failed', [
                'agent_id' => $agentId,
                'filename' => $filename,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
