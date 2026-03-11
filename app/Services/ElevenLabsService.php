<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ElevenLabsService
{
    private string $apiKey;
    private string $defaultVoiceId;
    private string $modelId;
    private string $baseUrl = 'https://api.elevenlabs.io/v1';

    public function __construct()
    {
        $this->apiKey         = (string) config('services.elevenlabs.api_key');
        $this->defaultVoiceId = (string) config('services.elevenlabs.voice_id');
        $this->modelId        = (string) config('services.elevenlabs.model_id', 'eleven_multilingual_v2');
    }

    public function isAvailable(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Generate audio from text via ElevenLabs TTS API.
     *
     * @return string|null Path to temporary mp3 file, or null on failure
     */
    public function textToSpeech(string $text, ?string $voiceId = null): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $voiceId = $voiceId ?: $this->defaultVoiceId;
        if (! $voiceId) {
            return null;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'xi-api-key'   => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'audio/mpeg',
                ])
                ->post("{$this->baseUrl}/text-to-speech/{$voiceId}", [
                    'text'           => $text,
                    'model_id'       => $this->modelId,
                    'voice_settings' => [
                        'stability'        => 0.5,
                        'similarity_boost'  => 0.75,
                        'style'            => 0.0,
                        'use_speaker_boost' => true,
                    ],
                ]);

            if (! $response->successful()) {
                Log::channel('whatsapp')->warning('ElevenLabs TTS: API error', [
                    'status' => $response->status(),
                    'body'   => mb_substr($response->body(), 0, 200),
                ]);
                return null;
            }

            $tempDir = storage_path('app/temp');
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $filePath = $tempDir . '/tts_' . uniqid() . '.mp3';
            file_put_contents($filePath, $response->body());

            return $filePath;
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('ElevenLabs TTS: exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Fetch available voices from ElevenLabs API.
     *
     * @return array<int, array{voice_id: string, name: string, labels: array, preview_url: string|null}>
     */
    public function getVoices(): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['xi-api-key' => $this->apiKey])
                ->get("{$this->baseUrl}/voices");

            if (! $response->successful()) {
                return [];
            }

            $voices = $response->json('voices') ?? [];

            return array_map(fn (array $v) => [
                'voice_id'    => $v['voice_id'] ?? '',
                'name'        => $v['name'] ?? '',
                'labels'      => $v['labels'] ?? [],
                'preview_url' => $v['preview_url'] ?? null,
                'category'    => $v['category'] ?? 'premade',
            ], $voices);
        } catch (\Throwable $e) {
            Log::warning('ElevenLabs: failed to fetch voices', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
