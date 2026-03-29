<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NurtureSequenceStep extends Model
{
    protected $fillable = [
        'sequence_id',
        'position',
        'delay_minutes',
        'type',
        'config',
        'is_active',
    ];

    protected $casts = [
        'position'      => 'integer',
        'delay_minutes' => 'integer',
        'config'        => 'array',
        'is_active'     => 'boolean',
    ];

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(NurtureSequence::class, 'sequence_id');
    }
}
