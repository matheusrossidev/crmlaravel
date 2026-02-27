<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\OAuthConnection;
use App\Services\GoogleCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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

    public function index(): View|RedirectResponse
    {
        $conn = $this->getConnection();

        if (! $conn || ! $this->hasCalendarScope($conn)) {
            return redirect()->route('settings.integrations.index')
                ->with('info', 'Conecte sua conta Google com permissão de Agenda para usar este recurso.');
        }

        return view('tenant.calendar.index');
    }

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
            $svc    = new GoogleCalendarService($conn);
            $events = $svc->listEvents($request->input('start'), $request->input('end'));
            return response()->json($events);
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
        ]);

        $conn = $this->getConnection();
        if (! $conn || ! $this->hasCalendarScope($conn)) {
            return response()->json(['success' => false, 'message' => 'Google Calendar não conectado.'], 403);
        }

        try {
            $svc   = new GoogleCalendarService($conn);
            $event = $svc->createEvent($data);
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
        ]);

        $conn = $this->getConnection();
        if (! $conn || ! $this->hasCalendarScope($conn)) {
            return response()->json(['success' => false, 'message' => 'Google Calendar não conectado.'], 403);
        }

        try {
            $svc   = new GoogleCalendarService($conn);
            $event = $svc->updateEvent($id, $data);
            return response()->json(['success' => true, 'event' => $event]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $conn = $this->getConnection();
        if (! $conn || ! $this->hasCalendarScope($conn)) {
            return response()->json(['success' => false, 'message' => 'Google Calendar não conectado.'], 403);
        }

        try {
            $svc = new GoogleCalendarService($conn);
            $svc->deleteEvent($id);
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
