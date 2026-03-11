<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => ['required', 'url'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $user->updatePushSubscription(
            $request->input('endpoint'),
            $request->input('keys.p256dh'),
            $request->input('keys.auth'),
            $request->input('content_encoding', 'aesgcm')
        );

        return response()->json(['message' => 'Push subscription salva.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => ['required', 'url'],
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $user->deletePushSubscription($request->input('endpoint'));

        return response()->json(['message' => 'Push subscription removida.']);
    }
}
