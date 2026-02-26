<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\MasterNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MasterNotificationSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly MasterNotification $notification,
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
        return 'master.notification';
    }

    public function broadcastWith(): array
    {
        return [
            'id'    => $this->notification->id,
            'title' => $this->notification->title,
            'body'  => $this->notification->body,
            'type'  => $this->notification->type,
        ];
    }
}
