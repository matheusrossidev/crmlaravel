<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstagramInstance extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'instagram_account_id',
        'ig_business_account_id',
        'username',
        'profile_picture_url',
        'access_token',
        'token_expires_at',
        'status',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];

    protected $hidden = ['access_token'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(InstagramConversation::class, 'instance_id');
    }
}
