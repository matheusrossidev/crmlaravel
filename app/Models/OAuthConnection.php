<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OAuthConnection extends Model
{
    use BelongsToTenant;

    protected $table = 'oauth_connections';

    protected $fillable = [
        'tenant_id', 'platform', 'platform_user_id', 'platform_user_name',
        'access_token', 'refresh_token', 'token_expires_at', 'scopes_json',
        'status', 'last_sync_at',
    ];

    protected $hidden = [
        'access_token', 'refresh_token',
    ];

    protected $casts = [
        'scopes_json' => 'array',
        'token_expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }
}
