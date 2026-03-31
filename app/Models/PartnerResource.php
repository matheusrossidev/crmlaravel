<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerResource extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'content',
        'cover_image', 'category', 'attachments',
        'is_published', 'sort_order',
    ];

    protected $casts = [
        'attachments'  => 'array',
        'is_published' => 'boolean',
        'sort_order'   => 'integer',
    ];

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
