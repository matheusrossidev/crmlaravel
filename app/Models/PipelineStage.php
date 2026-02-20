<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelineStage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'pipeline_id', 'name', 'color', 'position', 'is_won', 'is_lost',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_won' => 'boolean',
        'is_lost' => 'boolean',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'stage_id');
    }
}
