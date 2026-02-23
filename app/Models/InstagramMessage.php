<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramMessage extends Model
{
    use BelongsToTenant;

    public $timestamps = true;

    protected $fillable = [
        'tenant_id', 'conversation_id', 'ig_message_id',
        'direction', 'type', 'body', 'media_url',
        'is_deleted', 'ack', 'user_id', 'sent_at',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
        'sent_at'    => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(InstagramConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
