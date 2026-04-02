<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacebookLeadFormConnection extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'oauth_connection_id',
        'page_id',
        'page_name',
        'page_access_token',
        'form_id',
        'form_name',
        'form_fields_json',
        'pipeline_id',
        'stage_id',
        'field_mapping',
        'default_tags',
        'auto_assign_to',
        'is_active',
    ];

    protected $casts = [
        'field_mapping'     => 'array',
        'default_tags'      => 'array',
        'form_fields_json'  => 'array',
        'page_access_token' => 'encrypted',
        'is_active'         => 'boolean',
    ];

    public function oauthConnection(): BelongsTo
    {
        return $this->belongsTo(OAuthConnection::class, 'oauth_connection_id');
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'stage_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auto_assign_to');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(FacebookLeadFormEntry::class, 'connection_id');
    }
}
