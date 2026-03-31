<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerLessonProgress extends Model
{
    public $timestamps = false;

    protected $table = 'partner_lesson_progress';

    protected $fillable = [
        'tenant_id', 'lesson_id', 'completed_at', 'created_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(PartnerLesson::class, 'lesson_id');
    }
}
