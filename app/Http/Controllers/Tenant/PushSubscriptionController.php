<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => ['required', 'url'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        try {
            /** @var \App\Models\User $user */
            $user = auth()->user();

            $user->updatePushSubscription(
                $request->input('endpoint'),
                $request->input('keys.p256dh'),
                $request->input('keys.auth'),
                $request->input('content_encoding', 'aesgcm')
            );

            return response()->json(['message' => 'Push subscription salva.']);
        } catch (\Throwable $e) {
            Log::error('Push subscription store failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Erro ao salvar push subscription.'], 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => ['required', 'url'],
        ]);

        try {
            /** @var \App\Models\User $user */
            $user = auth()->user();

            $user->deletePushSubscription($request->input('endpoint'));

            return response()->json(['message' => 'Push subscription removida.']);
        } catch (\Throwable $e) {
            Log::error('Push subscription destroy failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Erro ao remover push subscription.'], 500);
        }
    }
}
