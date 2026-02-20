<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'webhook_config_id', 'event_type', 'payload_json',
        'response_code', 'response_body', 'status',
        'attempts', 'next_retry_at', 'last_attempt_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'response_code' => 'integer',
        'attempts' => 'integer',
        'next_retry_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function webhookConfig(): BelongsTo
    {
        return $this->belongsTo(WebhookConfig::class);
    }
}
