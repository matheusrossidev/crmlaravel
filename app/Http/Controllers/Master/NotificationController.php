<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Events\MasterNotificationSent;
use App\Http\Controllers\Controller;
use App\Models\MasterNotification;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = MasterNotification::orderByDesc('created_at')->limit(100)->get();
        $tenants       = Tenant::orderBy('name')->get(['id', 'name']);

        return view('master.notifications.index', compact('notifications', 'tenants'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'     => 'required|string|max:200',
            'body'      => 'required|string|max:2000',
            'type'      => 'required|in:info,warning,alert',
            'tenant_id' => 'nullable|integer|exists:tenants,id',
        ]);

        $data['tenant_id'] = $request->input('tenant_id') ?: null;

        $notification = MasterNotification::create($data);

        if ($data['tenant_id']) {
            // Enviar para um tenant específico
            try {
                MasterNotificationSent::dispatch($notification, (int) $data['tenant_id']);
            } catch (\Throwable) {
                // Broadcast pode falhar se Reverb não estiver ativo — não bloquear
            }
        } else {
            // Broadcast para todos os tenants ativos
            foreach (Tenant::where('status', 'active')->pluck('id') as $tenantId) {
                try {
                    MasterNotificationSent::dispatch($notification, $tenantId);
                } catch (\Throwable) {
                    //
                }
            }
        }

        return response()->json(['success' => true, 'notification' => $notification]);
    }
}
