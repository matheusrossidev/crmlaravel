<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pipeline extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'color', 'is_default', 'sort_order',
        'auto_create_lead', 'auto_create_from_whatsapp', 'auto_create_from_instagram',
    ];

    protected $casts = [
        'is_default'                 => 'boolean',
        'sort_order'                 => 'integer',
        'auto_create_lead'           => 'boolean',
        'auto_create_from_whatsapp'  => 'boolean',
        'auto_create_from_instagram' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(PipelineStage::class)->orderBy('position');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
