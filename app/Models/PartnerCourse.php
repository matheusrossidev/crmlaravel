<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerCourse extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'cover_image',
        'is_published', 'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order'   => 'integer',
    ];

    public function lessons(): HasMany
    {
        return $this->hasMany(PartnerLesson::class, 'course_id')->orderBy('sort_order');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(PartnerCertificate::class, 'course_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
