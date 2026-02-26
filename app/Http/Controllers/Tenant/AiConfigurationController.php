<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AiConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiConfigurationController extends Controller
{
    /** Modelos disponíveis por provider */
    private const MODELS = [
        'openai'    => ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-3.5-turbo'],
        'anthropic' => ['claude-opus-4-6', 'claude-sonnet-4-6', 'claude-haiku-4-5-20251001'],
        'google'    => ['gemini-2.0-flash', 'gemini-1.5-pro', 'gemini-1.5-flash'],
    ];

    public function show(): View
    {
        $config = AiConfiguration::first() ?? new AiConfiguration();

        return view('master.ai.config', [
            'config'       => $config,
            'modelOptions' => self::MODELS,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'llm_provider' => 'required|in:openai,anthropic,google',
            'llm_api_key'  => 'nullable|string|max:200',
            'llm_model'    => 'required|string|max:80',
        ]);

        // Não sobrescrever a chave se o usuário não informou (campo oculto)
        if (empty($data['llm_api_key'])) {
            unset($data['llm_api_key']);
        }

        // Tabela global (singleton) — atualiza o único registro ou cria
        $config = AiConfiguration::first() ?? new AiConfiguration();
        $config->fill($data)->save();

        return response()->json(['success' => true, 'message' => 'Configuração salva.']);
    }

    public function testConnection(Request $request): JsonResponse
    {
        $request->validate([
            'llm_provider' => 'required|in:openai,anthropic,google',
            'llm_api_key'  => 'required|string',
            'llm_model'    => 'required|string',
        ]);

        try {
            $result = $this->callLlm(
                provider: $request->input('llm_provider'),
                apiKey:   $request->input('llm_api_key'),
                model:    $request->input('llm_model'),
                messages: [['role' => 'user', 'content' => 'Responda apenas: "OK"']],
            );

            return response()->json(['success' => true, 'response' => $result['reply']]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Chama o LLM e retorna array com 'reply' (texto) e 'usage' (tokens).
     *
     * @param array $messages  Mensagens no formato OpenAI [{role, content}].
     *                         Content pode ser string (texto) ou array de blocos
     *                         multimodal [{type:'image_url',...}, {type:'text',...}].
     * @param string $system   System prompt (passado como parâmetro separado para cada provider).
     * @return array{reply: string, usage: array{prompt: int, completion: int, total: int}}
     */
    public static function callLlm(
        string $provider,
        string $apiKey,
        string $model,
        array  $messages,
        int    $maxTokens = 1000,
        string $system = '',
    ): array {
        return match ($provider) {
            'openai'    => self::callOpenAi($apiKey, $model, $messages, $maxTokens, $system),
            'anthropic' => self::callAnthropic($apiKey, $model, $messages, $maxTokens, $system),
            'google'    => self::callGoogle($apiKey, $model, $messages, $maxTokens, $system),
            default     => throw new \RuntimeException("Provider desconhecido: {$provider}"),
        };
    }

    private static function callOpenAi(string $apiKey, string $model, array $messages, int $maxTokens, string $system): array
    {
        // Prepend system message se fornecido (e não já presente no array)
        if ($system !== '' && (empty($messages) || $messages[0]['role'] !== 'system')) {
            array_unshift($messages, ['role' => 'system', 'content' => $system]);
        }

        $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'      => $model,
                'messages'   => $messages,
                'max_tokens' => $maxTokens,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI erro: ' . ($response->json('error.message') ?? $response->status()));
        }

        $prompt     = (int) ($response->json('usage.prompt_tokens') ?? 0);
        $completion = (int) ($response->json('usage.completion_tokens') ?? 0);

        return [
            'reply' => $response->json('choices.0.message.content') ?? '',
            'usage' => ['prompt' => $prompt, 'completion' => $completion, 'total' => $prompt + $completion],
        ];
    }

    private static function callAnthropic(string $apiKey, string $model, array $messages, int $maxTokens, string $system): array
    {
        // Remove mensagens com role=system do array (Anthropic usa campo separado)
        $filtered = array_values(array_filter($messages, fn ($m) => $m['role'] !== 'system'));

        // Se $system está vazio mas havia {role:system} no array, extrai-o
        if ($system === '') {
            foreach ($messages as $m) {
                if ($m['role'] === 'system') {
                    $system = is_string($m['content']) ? $m['content'] : '';
                    break;
                }
            }
        }

        // Converter blocos de imagem do formato OpenAI para Anthropic
        $converted = array_map(function (array $m) {
            if (! is_array($m['content'])) {
                return $m;
            }
            $blocks = [];
            foreach ($m['content'] as $block) {
                if (($block['type'] ?? '') === 'image_url') {
                    $dataUri = $block['image_url']['url'] ?? '';
                    // "data:image/jpeg;base64,..." → extrair mime e data
                    if (preg_match('/^data:([^;]+);base64,(.+)$/', $dataUri, $bm)) {
                        $blocks[] = [
                            'type'   => 'image',
                            'source' => ['type' => 'base64', 'media_type' => $bm[1], 'data' => $bm[2]],
                        ];
                    }
                } elseif (($block['type'] ?? '') === 'text') {
                    $blocks[] = ['type' => 'text', 'text' => $block['text'] ?? ''];
                }
            }
            return ['role' => $m['role'], 'content' => $blocks];
        }, $filtered);

        $body = [
            'model'      => $model,
            'max_tokens' => $maxTokens,
            'messages'   => $converted,
        ];
        if ($system !== '') {
            $body['system'] = $system;
        }

        $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->post('https://api.anthropic.com/v1/messages', $body);

        if (! $response->successful()) {
            throw new \RuntimeException('Anthropic erro: ' . ($response->json('error.message') ?? $response->status()));
        }

        $prompt     = (int) ($response->json('usage.input_tokens') ?? 0);
        $completion = (int) ($response->json('usage.output_tokens') ?? 0);

        return [
            'reply' => $response->json('content.0.text') ?? '',
            'usage' => ['prompt' => $prompt, 'completion' => $completion, 'total' => $prompt + $completion],
        ];
    }

    private static function callGoogle(string $apiKey, string $model, array $messages, int $maxTokens, string $system): array
    {
        // Filtrar system messages
        $filtered = array_values(array_filter($messages, fn ($m) => $m['role'] !== 'system'));

        if ($system === '') {
            foreach ($messages as $m) {
                if ($m['role'] === 'system') {
                    $system = is_string($m['content']) ? $m['content'] : '';
                    break;
                }
            }
        }

        // Converte formato OpenAI para Google Gemini (com suporte multimodal)
        $contents = array_map(function (array $m) {
            $role = $m['role'] === 'assistant' ? 'model' : 'user';

            if (is_string($m['content'])) {
                return ['role' => $role, 'parts' => [['text' => $m['content']]]];
            }

            // Multimodal
            $parts = [];
            foreach ($m['content'] as $block) {
                if (($block['type'] ?? '') === 'image_url') {
                    $dataUri = $block['image_url']['url'] ?? '';
                    if (preg_match('/^data:([^;]+);base64,(.+)$/', $dataUri, $bm)) {
                        $parts[] = ['inline_data' => ['mime_type' => $bm[1], 'data' => $bm[2]]];
                    }
                } elseif (($block['type'] ?? '') === 'text') {
                    $parts[] = ['text' => $block['text'] ?? ''];
                }
            }

            return ['role' => $role, 'parts' => $parts];
        }, $filtered);

        $body = [
            'contents'         => $contents,
            'generationConfig' => ['maxOutputTokens' => $maxTokens],
        ];
        if ($system !== '') {
            $body['systemInstruction'] = ['parts' => [['text' => $system]]];
        }

        $response = \Illuminate\Support\Facades\Http::post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            $body
        );

        if (! $response->successful()) {
            throw new \RuntimeException('Google erro: ' . ($response->json('error.message') ?? $response->status()));
        }

        $prompt     = (int) ($response->json('usageMetadata.promptTokenCount') ?? 0);
        $completion = (int) ($response->json('usageMetadata.candidatesTokenCount') ?? 0);

        return [
            'reply' => $response->json('candidates.0.content.parts.0.text') ?? '',
            'usage' => ['prompt' => $prompt, 'completion' => $completion, 'total' => $prompt + $completion],
        ];
    }
}
