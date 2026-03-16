<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\OAuthConnection;
use App\Models\Tenant;
use App\Services\GoogleCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    private function getConnection(): ?OAuthConnection
    {
        return OAuthConnection::where('tenant_id', auth()->user()->tenant_id)
            ->where('platform', 'google')
            ->where('status', 'active')
            ->first();
    }

    private function hasCalendarScope(OAuthConnection $conn): bool
    {
        $scopes = $conn->scopes_json ?? [];
        return in_array('https://www.googleapis.com/auth/calendar', $scopes, true);
    }

    private function getTenant(): Tenant
    {
        return Tenant::findOrFail(auth()->user()->tenant_id);
    }

    private function getCalendarPrefs(Tenant $tenant): array
    {
        $settings = $tenant->settings_json ?? [];
        return [
            'visible_ids' => $settings['calendar_visible_ids'] ?? ['primary'],
            'default_id'  => $settings['calendar_default_id']  ?? 'primary',
        ];
    }

    // ── Views ─────────────────────────────────────────────────────────────

    public function index(): View
    {
        $conn = $this->getConnection();
        $connected = $conn && $this->hasCalendarScope($conn);
        $tenant = $this->getTenant();
        $prefs  = $this->getCalendarPrefs($tenant);

        return view('tenant.calendar.index', [
            'calendarConnected' => $connected,
            'calendarVisibleIds' => $prefs['visible_ids'],
            'calendarDefaultId'  => $prefs['default_id'],
        ]);
    }

    // ── Calendar list ─────────────────────────────────────────────────────

    public function calendars(): JsonResponse
    {
        $conn = $this->getConnection();
        if (! $conn || ! $this->hasCalendarScope($conn)) {
            return response()->json(['error' => 'Google Calendar não conectado.'], 403);
        }

        try {
            $svc = new GoogleCalendarService($conn);
            return response()->json($svc->listCalendars());
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ── Preferences ───────────────────────────────────────────────────────

    public function savePreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'visible_ids' => 'required|array|min:1',
            'visible_ids.*' => 'required|string|max:300',
            'default_id'  => 'required|string|max:300',
        ]);

        $tenant   = $this->getTenant();
        $settings = $tenant->settings_json ?? [];
        $settings['calendar_visible_ids'] = $data['visible_ids'];
        $settings['calendar_default_id']  = $data['default_id'];
        $tenant->settings_json = $settings;
        $tenant->save();

        return response()->json(['success' => true]);
    }

    // ── Events CRUD ───────────────────────────────────────────────────────

    public function events(Request $request): JsonResponse
    {
        $request->validate([
            'start' => 'required|string',
            'end'   => 'required|string',
        ]);

        $conn = $this->getConnection();
        if (! $conn || ! $this->hasCalendarScope($conn)) {
            return response()->json(['error' => 'Google Calendar não conectado.'], 403);
        }

        try {
            $tenant = $this->getTenant();
            $prefs  = $this->getCalendarPrefs($tenant);
            $start  = $request->input('start');
            $end    = $request->input('end');

            $allEvents = [];
            foreach ($prefs['visible_ids'] as $calId) {
                try {
                    $svc    = new GoogleCalendarService($conn, $calId);
                    $events = $svc->listEvents($start, $end);
                    foreach ($events as &$event) {
                        $event['calendarId'] = $calId;
                    }
                    $allEvents = array_merge($allEvents, $events);
                } catch (\Throwable $e) {
                    Log::warning('Calendar skip: ' . $calId . ' — ' . $e->getMessage());
                    continue;
                }
            }

            return response()->json($allEvents);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'required|string|max:500',
            'start'       => 'required|string',
            'end'         => 'required|string',
            'description' => 'nullable|string|max:5000',
            'location'    => 'nullable|string|max:500',
            'attendees'   => 'nullable|string|max:5000',
            'calendarId'  => 'nullable|string|max:300',
        ]);

        $conn = $this->getConnection();
        if (! $conn || ! $this->hasCalendarScope($conn)) {
            return response()->json(['success' => false, 'message' => 'Google Calendar não conectado.'], 403);
        }

        try {
            $calId = $data['calendarId'] ?? $this->getCalendarPrefs($this->getTenant())['default_id'];
            $svc   = new GoogleCalendarService($conn, $calId);
            $event = $svc->createEvent($data);
            $event['calendarId'] = $calId;
            return response()->json(['success' => true, 'event' => $event], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'nullable|string|max:500',
            'start'       => 'nullable|string',
            'end'         => 'nullable|string',
            'description' => 'nullable|string|max:5000',
            'location'    => 'nullable|string|max:500',
            'attendees'   => 'nullable|string|max:5000',
            'calendarId'  => 'nullable|string|max:300',
        ]);

        $conn = $this->getConnection();
        if (! $conn || ! $this->hasCalendarScope($conn)) {
            return response()->json(['success' => false, 'message' => 'Google Calendar não conectado.'], 403);
        }

        try {
            $calId = $data['calendarId'] ?? $this->getCalendarPrefs($this->getTenant())['default_id'];
            $svc   = new GoogleCalendarService($conn, $calId);
            $event = $svc->updateEvent($id, $data);
            return response()->json(['success' => true, 'event' => $event]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $conn = $this->getConnection();
        if (! $conn || ! $this->hasCalendarScope($conn)) {
            return response()->json(['success' => false, 'message' => 'Google Calendar não conectado.'], 403);
        }

        try {
            $calId = $request->input('calendarId') ?? $this->getCalendarPrefs($this->getTenant())['default_id'];
            $svc   = new GoogleCalendarService($conn, $calId);
            $svc->deleteEvent($id);
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
