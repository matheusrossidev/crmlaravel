<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AiIntentSignal;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class AiIntentSignalController extends Controller
{
    /**
     * Retorna as últimas 20 notificações para o dropdown do sino (AJAX).
     */
    public function list(): JsonResponse
    {
        $signals = AiIntentSignal::orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'contact_name', 'phone', 'intent_type', 'context',
                   'conversation_id', 'read_at', 'created_at'])
            ->map(fn ($s) => [
                'id'              => $s->id,
                'contact_name'    => $s->contact_name,
                'phone'           => $s->phone,
                'intent_type'     => $s->intent_type,
                'context'         => mb_substr($s->context, 0, 120),
                'conversation_id' => $s->conversation_id,
                'read_at'         => $s->read_at?->toISOString(),
                'time_ago'        => $this->timeAgo($s->created_at),
            ]);

        $unreadCount = AiIntentSignal::whereNull('read_at')->count();

        return response()->json([
            'signals'      => $signals->values(),
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Marcar um sinal como lido.
     */
    public function markRead(AiIntentSignal $signal): JsonResponse
    {
        $signal->update(['read_at' => now()]);
        return response()->json(['ok' => true]);
    }

    /**
     * Marcar todos como lidos.
     */
    public function markAllRead(): JsonResponse
    {
        AiIntentSignal::whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['ok' => true]);
    }

    /**
     * Contagem de não lidas (para polling inicial).
     */
    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'count' => AiIntentSignal::whereNull('read_at')->count(),
        ]);
    }

    private function timeAgo(?Carbon $date): string
    {
        if (! $date) return '';

        $diff = now()->diffInSeconds($date);

        if ($diff < 60)   return 'agora mesmo';
        if ($diff < 3600) return floor($diff / 60) . ' min atrás';
        if ($diff < 86400) return floor($diff / 3600) . 'h atrás';

        return $date->format('d/m/Y H:i');
    }
}
