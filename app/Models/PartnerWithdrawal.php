<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerWithdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'amount', 'status',
        'pix_key', 'pix_key_type', 'pix_holder_name', 'pix_holder_cpf_cnpj',
        'asaas_transfer_id', 'requested_at', 'approved_at', 'paid_at',
        'rejected_reason',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'requested_at' => 'datetime',
        'approved_at'  => 'datetime',
        'paid_at'      => 'datetime',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
