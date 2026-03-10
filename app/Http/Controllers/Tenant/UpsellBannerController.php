<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\UpsellTriggerLog;
use Illuminate\Http\JsonResponse;

class UpsellBannerController extends Controller
{
    public function dismiss(UpsellTriggerLog $log): JsonResponse
    {
        $log->update(['clicked_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function click(UpsellTriggerLog $log): JsonResponse
    {
        $log->update(['clicked_at' => now()]);

        $trigger = $log->trigger;
        $ctaUrl  = $trigger?->action_config['cta_url'] ?? route('billing.checkout', ['plan' => $trigger?->target_plan]);

        return response()->json(['success' => true, 'redirect' => $ctaUrl]);
    }
}
