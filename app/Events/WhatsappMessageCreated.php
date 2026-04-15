<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\WhatsappMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsappMessageCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly WhatsappMessage $message,
        public readonly int $tenantId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenantId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'whatsapp.message';
    }

    public function broadcastWith(): array
    {
        $m = $this->message;
        // Carrega lazy o agent se ainda nao foi carregado, pra incluir no payload
        if ($m->sent_by_agent_id && ! $m->relationLoaded('sentByAgent')) {
            $m->load('sentByAgent:id,name,display_avatar');
        }
        return [
            'id'               => $m->id,
            'conversation_id'  => $m->conversation_id,
            'waha_message_id'  => $m->waha_message_id,
            'cloud_message_id' => $m->cloud_message_id,   // Cloud API (Meta) — null no WAHA
            'direction'        => $m->direction,
            'type'            => $m->type,
            'body'            => $m->body,
            'media_url'       => $m->media_url,
            'media_mime'      => $m->media_mime,
            'media_filename'  => $m->media_filename,
            'reaction_data'   => $m->reaction_data,
            'ack'             => $m->ack,
            'is_deleted'      => $m->is_deleted,
            'sent_at'         => $m->sent_at?->toISOString(),
            'user_name'       => $m->user?->name,
            'sent_by'         => $m->sent_by,
            'sent_by_agent'   => $m->sentByAgent ? [
                'id'     => $m->sentByAgent->id,
                'name'   => $m->sentByAgent->name,
                'avatar' => $m->sentByAgent->display_avatar,
            ] : null,
        ];
    }
}
