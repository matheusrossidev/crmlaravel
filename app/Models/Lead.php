<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'phone', 'email', 'value',
        'source', 'tags', 'pipeline_id', 'stage_id',
        'assigned_to', 'campaign_id', 'created_by', 'notes',
        'instagram_username',
    ];

    protected $casts = [
        'tags' => 'array',
        'value' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
