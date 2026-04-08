<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'name',
        'color',
        'sort_order',
        'applies_to',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function leads(): MorphToMany
    {
        return $this->morphedByMany(Lead::class, 'taggable');
    }

    public function whatsappConversations(): MorphToMany
    {
        return $this->morphedByMany(WhatsappConversation::class, 'taggable');
    }

    public function instagramConversations(): MorphToMany
    {
        return $this->morphedByMany(InstagramConversation::class, 'taggable');
    }

    public function websiteConversations(): MorphToMany
    {
        return $this->morphedByMany(WebsiteConversation::class, 'taggable');
    }
}
