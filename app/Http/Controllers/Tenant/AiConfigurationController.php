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

        return view('tenant.ai.config', [
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

        AiConfiguration::updateOrCreate(
            ['tenant_id' => auth()->user()->tenant_id],
            $data
        );

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
            $response = $this->callLlm(
                provider: $request->input('llm_provider'),
                apiKey:   $request->input('llm_api_key'),
                model:    $request->input('llm_model'),
                messages: [['role' => 'user', 'content' => 'Responda apenas: "OK"']],
            );

            return response()->json(['success' => true, 'response' => $response]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /** Chama o LLM e retorna a resposta em texto */
    public static function callLlm(string $provider, string $apiKey, string $model, array $messages, int $maxTokens = 1000): string
    {
        return match ($provider) {
            'openai'    => self::callOpenAi($apiKey, $model, $messages, $maxTokens),
            'anthropic' => self::callAnthropic($apiKey, $model, $messages, $maxTokens),
            'google'    => self::callGoogle($apiKey, $model, $messages, $maxTokens),
            default     => throw new \RuntimeException("Provider desconhecido: {$provider}"),
        };
    }

    private static function callOpenAi(string $apiKey, string $model, array $messages, int $maxTokens): string
    {
        $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'      => $model,
                'messages'   => $messages,
                'max_tokens' => $maxTokens,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI erro: ' . ($response->json('error.message') ?? $response->status()));
        }

        return $response->json('choices.0.message.content') ?? '';
    }

    private static function callAnthropic(string $apiKey, string $model, array $messages, int $maxTokens): string
    {
        $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model'      => $model,
                'max_tokens' => $maxTokens,
                'messages'   => $messages,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Anthropic erro: ' . ($response->json('error.message') ?? $response->status()));
        }

        return $response->json('content.0.text') ?? '';
    }

    private static function callGoogle(string $apiKey, string $model, array $messages, int $maxTokens): string
    {
        // Converte formato OpenAI para Google Gemini
        $contents = array_map(fn ($m) => [
            'role'  => $m['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $m['content']]],
        ], $messages);

        $response = \Illuminate\Support\Facades\Http::post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            [
                'contents'          => $contents,
                'generationConfig'  => ['maxOutputTokens' => $maxTokens],
            ]
        );

        if (! $response->successful()) {
            throw new \RuntimeException('Google erro: ' . ($response->json('error.message') ?? $response->status()));
        }

        return $response->json('candidates.0.content.parts.0.text') ?? '';
    }
}
