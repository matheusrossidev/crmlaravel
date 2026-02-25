<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramAutomation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'instance_id',
        'name',
        'media_id',
        'media_thumbnail_url',
        'media_caption',
        'keywords',
        'match_type',
        'reply_comment',
        'dm_message',
        'is_active',
    ];

    protected $casts = [
        'keywords'  => 'array',
        'is_active' => 'boolean',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(InstagramInstance::class, 'instance_id');
    }
}
