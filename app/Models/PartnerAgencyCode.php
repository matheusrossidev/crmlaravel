<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerAgencyCode extends Model
{
    protected $fillable = [
        'code',
        'description',
        'tenant_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->tenant_id === null;
    }
}
