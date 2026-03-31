<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerLesson extends Model
{
    protected $fillable = [
        'course_id', 'title', 'description', 'video_url',
        'duration_minutes', 'sort_order',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'sort_order'       => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(PartnerCourse::class, 'course_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(PartnerLessonProgress::class, 'lesson_id');
    }
}
