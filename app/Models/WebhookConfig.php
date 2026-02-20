<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookConfig extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'url', 'secret', 'events_json',
        'headers_json', 'is_active', 'retry_count',
    ];

    protected $hidden = ['secret'];

    protected $casts = [
        'events_json' => 'array',
        'headers_json' => 'array',
        'is_active' => 'boolean',
        'retry_count' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class)->orderByDesc('created_at');
    }
}
