<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TourController extends Controller
{
    public function complete(Request $request): JsonResponse
    {
        $request->validate(['tour' => 'required|string|max:50']);

        $user   = auth()->user();
        $config = $user->dashboard_config ?? [];
        $config['tours_completed'] = $config['tours_completed'] ?? [];
        $config['tours_completed'][$request->input('tour')] = true;

        $user->update(['dashboard_config' => $config]);

        return response()->json(['success' => true]);
    }

    public function reset(): JsonResponse
    {
        $user   = auth()->user();
        $config = $user->dashboard_config ?? [];
        $config['tours_completed'] = [];

        $user->update(['dashboard_config' => $config]);

        return response()->json(['success' => true, 'message' => 'Tours resetados.']);
    }
}
