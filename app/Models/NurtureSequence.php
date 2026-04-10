<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NurtureSequence extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'is_active',
        'channel',
        'exit_on_reply',
        'exit_on_stage_change',
        'stats_enrolled',
        'stats_completed',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'exit_on_reply'        => 'boolean',
        'exit_on_stage_change' => 'boolean',
        'stats_enrolled'       => 'integer',
        'stats_completed'      => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(NurtureSequenceStep::class, 'sequence_id')->orderBy('position');
    }

    public function leadSequences(): HasMany
    {
        return $this->hasMany(LeadSequence::class, 'sequence_id');
    }

    public function activeLeadSequences(): HasMany
    {
        return $this->leadSequences()->where('status', 'active');
    }
}
