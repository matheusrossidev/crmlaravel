<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteVisit extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'visitor_id', 'lead_id', 'page_url', 'page_title',
        'referrer', 'utm_source', 'utm_medium', 'utm_campaign',
        'utm_content', 'utm_term', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
