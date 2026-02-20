<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappMessage extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'conversation_id',
        'waha_message_id',
        'direction',
        'type',
        'body',
        'media_url',
        'media_mime',
        'media_filename',
        'reaction_data',
        'user_id',
        'ack',
        'is_deleted',
        'sent_at',
    ];

    protected $casts = [
        'reaction_data' => 'array',
        'sent_at'       => 'datetime',
        'is_deleted'    => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsappConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
