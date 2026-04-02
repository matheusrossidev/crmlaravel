<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacebookLeadFormEntry extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'connection_id',
        'meta_lead_id',
        'lead_id',
        'platform',
        'ad_id',
        'campaign_name_meta',
        'raw_data',
        'status',
        'error_message',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(FacebookLeadFormConnection::class, 'connection_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
