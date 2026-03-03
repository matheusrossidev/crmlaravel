<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantTokenIncrement extends Model
{
    protected $fillable = [
        'tenant_id',
        'token_increment_plan_id',
        'tokens_added',
        'price_paid',
        'asaas_payment_id',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'tokens_added' => 'integer',
        'price_paid'   => 'decimal:2',
        'paid_at'      => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TokenIncrementPlan::class, 'token_increment_plan_id');
    }
}
