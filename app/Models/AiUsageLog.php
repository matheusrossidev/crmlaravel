<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    public const UPDATED_AT = null; // append-only, sem updated_at

    protected $fillable = [
        'tenant_id',
        'conversation_id',
        'model',
        'provider',
        'tokens_prompt',
        'tokens_completion',
        'tokens_total',
        'type',
    ];

    protected $casts = [
        'tokens_prompt'      => 'integer',
        'tokens_completion'  => 'integer',
        'tokens_total'       => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
