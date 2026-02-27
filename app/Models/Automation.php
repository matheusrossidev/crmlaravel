<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Automation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'is_active',
        'trigger_type', 'trigger_config', 'conditions', 'actions',
        'run_count', 'last_run_at',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'trigger_config' => 'array',
        'conditions'     => 'array',
        'actions'        => 'array',
        'run_count'      => 'integer',
        'last_run_at'    => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
