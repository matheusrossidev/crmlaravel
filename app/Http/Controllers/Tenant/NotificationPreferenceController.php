<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationPreferenceController extends Controller
{
    public function index(): View
    {
        return view('tenant.settings.notifications', [
            'preferences' => auth()->user()->notification_preferences ?? [],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'browser' => ['nullable', 'array'],
            'browser.*' => ['boolean'],
            'push' => ['nullable', 'array'],
            'push.*' => ['boolean'],
            'sound' => ['nullable', 'array'],
            'sound.enabled' => ['boolean'],
            'quiet_hours' => ['nullable', 'array'],
            'quiet_hours.enabled' => ['boolean'],
            'quiet_hours.start' => ['nullable', 'string', 'date_format:H:i'],
            'quiet_hours.end' => ['nullable', 'string', 'date_format:H:i'],
        ]);

        $user = auth()->user();
        $user->notification_preferences = $validated;
        $user->save();

        return response()->json([
            'message' => 'Preferências de notificação atualizadas.',
            'preferences' => $user->notification_preferences,
        ]);
    }
}
