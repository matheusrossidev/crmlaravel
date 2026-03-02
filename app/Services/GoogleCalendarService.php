<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OAuthConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    private const BASE = 'https://www.googleapis.com/calendar/v3';
    private const CALENDAR_ID = 'primary';

    public function __construct(private OAuthConnection $conn) {}

    // ── Token Management ────────────────────────────────────────────────────

    private function refreshIfNeeded(): void
    {
        if (! $this->conn->isExpired()) {
            return;
        }

        if (! $this->conn->refresh_token) {
            throw new \RuntimeException('Token expirado e sem refresh_token. Reconecte o Google.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id'     => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'grant_type'    => 'refresh_token',
            'refresh_token' => $this->conn->refresh_token,
        ]);

        if (! $response->successful()) {
            Log::error('GoogleCalendarService: falha ao renovar token', [
                'tenant_id' => $this->conn->tenant_id,
                'status'    => $response->status(),
                'body'      => $response->body(),
            ]);
            throw new \RuntimeException('Falha ao renovar token do Google. Reconecte a conta.');
        }

        $data = $response->json();

        $this->conn->update([
            'access_token'    => $data['access_token'],
            'token_expires_at'=> now()->addSeconds((int) ($data['expires_in'] ?? 3600)),
        ]);

        $this->conn->refresh();
    }

    private function token(): string
    {
        $this->refreshIfNeeded();
        return $this->conn->access_token;
    }

    // ── Calendar API ────────────────────────────────────────────────────────

    /**
     * Converte qualquer string de data para RFC3339 UTC ("Z") exigido pelo Google Calendar API.
     * Motivo: quando o offset de fuso (ex: -03:00) é passado via query string pelo HTTP client,
     * o ":" do offset fica URL-encoded como "%3A" e a API do Google retorna 400.
     * Solução: sempre normalizar para UTC antes de enviar.
     */
    private function toRfc3339(string $date): string
    {
        try {
            return \Carbon\Carbon::parse($date)->utc()->format('Y-m-d\TH:i:s\Z');
        } catch (\Throwable) {
            // Fallback se Carbon não conseguir parsear
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return $date . 'T00:00:00Z';
            }
            return $date;
        }
    }

    /**
     * Lista eventos no range [timeMin, timeMax].
     * Retorna array de eventos com id, title, start, end, description, location.
     */
    public function listEvents(string $timeMin, string $timeMax): array
    {
        $response = Http::withToken($this->token())
            ->get(self::BASE . '/calendars/' . self::CALENDAR_ID . '/events', [
                'timeMin'      => $this->toRfc3339($timeMin),
                'timeMax'      => $this->toRfc3339($timeMax),
                'singleEvents' => 'true',
                'orderBy'      => 'startTime',
                'maxResults'   => 250,
            ]);

        if (! $response->successful()) {
            $detail = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException('Erro ao listar eventos: ' . $response->status() . ' — ' . $detail);
        }

        return collect($response->json('items', []))
            ->map(fn (array $item) => $this->normalizeEvent($item))
            ->values()
            ->all();
    }

    /**
     * Busca um único evento pelo ID.
     */
    public function getEvent(string $eventId): array
    {
        $response = Http::withToken($this->token())
            ->get(self::BASE . '/calendars/' . self::CALENDAR_ID . '/events/' . $eventId);

        if (! $response->successful()) {
            throw new \RuntimeException('Evento não encontrado: ' . $eventId);
        }

        return $this->normalizeEvent($response->json());
    }

    /**
     * Cria um evento.
     * @param array $data { title, start (ISO), end (ISO), description?, location? }
     */
    public function createEvent(array $data): array
    {
        $payload = [
            'summary'     => $data['title'],
            'description' => $data['description'] ?? '',
            'location'    => $data['location'] ?? '',
            'start'       => ['dateTime' => $data['start'], 'timeZone' => config('app.timezone', 'America/Sao_Paulo')],
            'end'         => ['dateTime' => $data['end'],   'timeZone' => config('app.timezone', 'America/Sao_Paulo')],
        ];

        // Convidados (attendees) — envia convite por e-mail quando presente
        $hasAttendees = false;
        if (! empty($data['attendees'])) {
            $raw   = is_array($data['attendees']) ? $data['attendees'] : [$data['attendees']];
            $emails = array_values(array_filter(array_map('trim', $raw)));
            if (! empty($emails)) {
                $payload['attendees'] = array_map(fn ($e) => ['email' => $e], $emails);
                $hasAttendees = true;
            }
        }

        $url = self::BASE . '/calendars/' . self::CALENDAR_ID . '/events'
             . ($hasAttendees ? '?sendUpdates=all' : '');

        $response = Http::withToken($this->token())
            ->post($url, $payload);

        if (! $response->successful()) {
            Log::error('GoogleCalendarService::createEvent falhou', ['body' => $response->body()]);
            throw new \RuntimeException('Erro ao criar evento: ' . ($response->json('error.message') ?? $response->status()));
        }

        return $this->normalizeEvent($response->json());
    }

    /**
     * Atualiza um evento existente.
     * @param array $data Campos a atualizar (mesmos de createEvent).
     */
    public function updateEvent(string $eventId, array $data): array
    {
        // Busca o evento atual para fazer PATCH parcial
        $current = Http::withToken($this->token())
            ->get(self::BASE . '/calendars/' . self::CALENDAR_ID . '/events/' . $eventId)
            ->json();

        $tz = config('app.timezone', 'America/Sao_Paulo');

        $updates = array_filter([
            'summary'     => $data['title']       ?? null,
            'description' => $data['description'] ?? null,
            'location'    => $data['location']    ?? null,
            'start'       => isset($data['start']) ? ['dateTime' => $data['start'], 'timeZone' => $tz] : null,
            'end'         => isset($data['end'])   ? ['dateTime' => $data['end'],   'timeZone' => $tz] : null,
        ], fn ($v) => $v !== null);

        $payload = array_merge($current, $updates);

        // Suporte a convidados no update
        $hasAttendees = false;
        if (array_key_exists('attendees', $data)) {
            if (! empty($data['attendees'])) {
                $raw    = is_array($data['attendees']) ? $data['attendees'] : [$data['attendees']];
                $emails = array_values(array_filter(array_map('trim', $raw)));
                if (! empty($emails)) {
                    $payload['attendees'] = array_map(fn ($e) => ['email' => $e], $emails);
                    $hasAttendees = true;
                }
            } else {
                $payload['attendees'] = [];
            }
        }

        $url = self::BASE . '/calendars/' . self::CALENDAR_ID . '/events/' . $eventId
             . ($hasAttendees ? '?sendUpdates=all' : '');

        $response = Http::withToken($this->token())->put($url, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('Erro ao atualizar evento: ' . ($response->json('error.message') ?? $response->status()));
        }

        return $this->normalizeEvent($response->json());
    }

    /**
     * Exclui um evento pelo ID.
     */
    public function deleteEvent(string $eventId): void
    {
        $response = Http::withToken($this->token())
            ->delete(self::BASE . '/calendars/' . self::CALENDAR_ID . '/events/' . $eventId);

        if ($response->status() !== 204 && ! $response->successful()) {
            throw new \RuntimeException('Erro ao excluir evento: ' . $response->status());
        }
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function normalizeEvent(array $item): array
    {
        $start = $item['start']['dateTime'] ?? $item['start']['date'] ?? null;
        $end   = $item['end']['dateTime']   ?? $item['end']['date']   ?? null;

        return [
            'id'          => $item['id'] ?? '',
            'title'       => $item['summary'] ?? '(sem título)',
            'start'       => $start,
            'end'         => $end,
            'description' => $item['description'] ?? '',
            'location'    => $item['location'] ?? '',
            'allDay'      => isset($item['start']['date']) && ! isset($item['start']['dateTime']),
            'url'         => $item['htmlLink'] ?? '',
        ];
    }
}
