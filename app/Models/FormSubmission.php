<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'form_id', 'tenant_id', 'lead_id',
        'data', 'ip_address', 'user_agent', 'submitted_at',
        'embed_mode', 'referrer_url',
    ];

    protected $casts = [
        'data'         => 'array',
        'submitted_at' => 'datetime',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
