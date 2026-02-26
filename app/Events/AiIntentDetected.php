<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\AiIntentSignal;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiIntentDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly AiIntentSignal $signal,
        private readonly int            $tenantId,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('tenant.' . $this->tenantId);
    }

    public function broadcastAs(): string
    {
        return 'ai.intent';
    }

    public function broadcastWith(): array
    {
        return [
            'id'              => $this->signal->id,
            'contact_name'    => $this->signal->contact_name,
            'phone'           => $this->signal->phone,
            'intent_type'     => $this->signal->intent_type,
            'context'         => $this->signal->context,
            'conversation_id' => $this->signal->conversation_id,
            'created_at'      => $this->signal->created_at?->toISOString(),
        ];
    }
}
