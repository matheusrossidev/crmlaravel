<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InstagramConversation extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'instance_id', 'lead_id',
        'igsid', 'contact_name', 'contact_username', 'contact_picture_url',
        'tags', 'assigned_user_id', 'ai_agent_id',
        'status', 'unread_count',
        'started_at', 'last_message_at', 'closed_at',
    ];

    protected $casts = [
        'tags'           => 'array',
        'started_at'     => 'datetime',
        'last_message_at'=> 'datetime',
        'closed_at'      => 'datetime',
        'unread_count'   => 'integer',
        'ai_agent_id'    => 'integer',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(InstagramInstance::class, 'instance_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function aiAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(InstagramMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(InstagramMessage::class, 'conversation_id')
                    ->latestOfMany('sent_at');
    }
}
