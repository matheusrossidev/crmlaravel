<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\InstagramMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InstagramMessageCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly InstagramMessage $message,
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
        return 'instagram.message';
    }

    public function broadcastWith(): array
    {
        $m = $this->message;
        if ($m->sent_by_agent_id && ! $m->relationLoaded('sentByAgent')) {
            $m->load('sentByAgent:id,name,display_avatar');
        }
        return [
            'id'              => $m->id,
            'conversation_id' => $m->conversation_id,
            'ig_message_id'   => $m->ig_message_id,
            'direction'       => $m->direction,
            'type'            => $m->type,
            'body'            => $m->body,
            'media_url'       => $m->media_url,
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
