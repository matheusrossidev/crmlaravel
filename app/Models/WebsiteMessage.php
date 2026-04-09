<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'direction',
        'content',
        'user_id',
        'sent_by',
        'sent_by_agent_id',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WebsiteConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sentByAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'sent_by_agent_id');
    }
}
