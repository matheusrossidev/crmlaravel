<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StageRequiredTask extends Model
{
    protected $fillable = [
        'pipeline_stage_id',
        'subject',
        'description',
        'task_type',
        'priority',
        'due_date_offset',
        'sort_order',
    ];

    protected $casts = [
        'due_date_offset' => 'integer',
        'sort_order'      => 'integer',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }
}
