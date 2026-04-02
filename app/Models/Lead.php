<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id', 'name', 'phone', 'email', 'company', 'value',
        'source', 'tags', 'pipeline_id', 'stage_id',
        'assigned_to', 'campaign_id', 'created_by', 'notes',
        'instagram_username', 'exclude_from_pipeline',
        'utm_id', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid',
        'birthday',
        'score', 'score_updated_at',
        'opted_out', 'opted_out_at', 'opted_out_reason',
        'status', 'merged_into', 'merged_at',
    ];

    protected $casts = [
        'tags'                  => 'array',
        'exclude_from_pipeline' => 'boolean',
        'opted_out'             => 'boolean',
        'opted_out_at'          => 'datetime',
        'value'            => 'decimal:2',
        'birthday'         => 'date',
        'score'            => 'integer',
        'score_updated_at' => 'datetime',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'merged_at'        => 'datetime',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'stage_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function whatsappConversation(): HasOne
    {
        return $this->hasOne(WhatsappConversation::class)->latest('last_message_at');
    }

    public function customFieldValues(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(LeadEvent::class)->orderByDesc('created_at');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function lostSales(): HasMany
    {
        return $this->hasMany(LostSale::class);
    }

    public function leadNotes(): HasMany
    {
        return $this->hasMany(LeadNote::class)->orderByDesc('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LeadAttachment::class)->orderByDesc('created_at');
    }

    public function products(): HasMany
    {
        return $this->hasMany(LeadProduct::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('due_date');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(LeadContact::class);
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(LeadList::class, 'lead_list_members')
            ->withPivot('added_at', 'added_by');
    }

    public function scoreLogs(): HasMany
    {
        return $this->hasMany(LeadScoreLog::class)->orderByDesc('created_at');
    }

    public function activeSequence(): HasOne
    {
        return $this->hasOne(LeadSequence::class)->where('status', 'active');
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into');
    }

    public function duplicatesAsA(): HasMany
    {
        return $this->hasMany(LeadDuplicate::class, 'lead_id_a');
    }

    public function duplicatesAsB(): HasMany
    {
        return $this->hasMany(LeadDuplicate::class, 'lead_id_b');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeNotMerged($query)
    {
        return $query->where('status', '!=', 'merged');
    }

    public function getCustomFieldsAttribute(): array
    {
        return $this->customFieldValues
            ->loadMissing('fieldDefinition')
            ->mapWithKeys(function ($cfv) {
                $def = $cfv->fieldDefinition;
                if (!$def) return [];
                $value = match ($def->field_type) {
                    'number', 'currency' => $cfv->value_number,
                    'date' => $cfv->value_date,
                    'checkbox' => (bool) $cfv->value_boolean,
                    'multiselect' => $cfv->value_json,
                    default => $cfv->value_text,
                };
                return [$def->name => [
                    'label' => $def->label,
                    'type' => $def->field_type,
                    'value' => $value,
                ]];
            })
            ->toArray();
    }
}
