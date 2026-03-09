<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\MasterNotification;
use Illuminate\Http\JsonResponse;

class MasterNotificationReadController extends Controller
{
    public function index(): JsonResponse
    {
        $tenantId = (int) auth()->user()->tenant_id;

        $notifications = MasterNotification::where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->orderByDesc('created_at')
            ->limit(30)
            ->get(['id', 'title', 'body', 'type', 'created_at']);

        return response()->json(['notifications' => $notifications]);
    }
}
