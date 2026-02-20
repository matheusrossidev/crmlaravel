<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'name', 'key_hash', 'key_prefix',
        'permissions_json', 'last_used_at', 'expires_at', 'is_active',
    ];

    protected $hidden = ['key_hash'];

    protected $casts = [
        'permissions_json' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions_json ?? [];
        return in_array('*', $permissions) || in_array($permission, $permissions);
    }
}
