<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LeadList extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'description', 'type', 'filters', 'lead_count', 'created_by',
    ];

    protected $casts = [
        'filters'    => 'array',
        'lead_count' => 'integer',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class, 'lead_list_members')
            ->withPivot('added_at', 'added_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function refreshCount(): void
    {
        $count = $this->type === 'static'
            ? $this->members()->count()
            : app(\App\Services\LeadListQueryBuilder::class)->resolve($this)->count();

        $this->update(['lead_count' => $count]);
    }
}
