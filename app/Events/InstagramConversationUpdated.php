<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\InstagramConversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InstagramConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly InstagramConversation $conversation,
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
        return 'instagram.conversation';
    }

    public function broadcastWith(): array
    {
        $c      = $this->conversation->load('latestMessage', 'assignedUser');
        $latest = $c->latestMessage;

        return [
            'id'                => $c->id,
            'channel'           => 'instagram',
            'contact_name'      => $c->contact_name,
            'contact_picture'   => $c->contact_picture_url,
            'status'            => $c->status,
            'unread_count'      => $c->unread_count,
            'last_message_at'   => $c->last_message_at?->toISOString(),
            'last_message_body' => $latest?->body ?? ($latest ? '[' . $latest->type . ']' : null),
            'last_message_type' => $latest?->type,
            'assigned_user'     => $c->assignedUser?->name,
            'assigned_user_id'  => $c->assigned_user_id,
            'tags'              => $c->tags ?? [],
        ];
    }
}
