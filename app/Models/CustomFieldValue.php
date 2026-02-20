<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFieldValue extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'lead_id', 'field_id',
        'value_text', 'value_number', 'value_date', 'value_boolean', 'value_json',
    ];

    protected $casts = [
        'value_number' => 'decimal:4',
        'value_boolean' => 'boolean',
        'value_json' => 'array',
        'value_date' => 'date',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function fieldDefinition(): BelongsTo
    {
        return $this->belongsTo(CustomFieldDefinition::class, 'field_id');
    }
}
