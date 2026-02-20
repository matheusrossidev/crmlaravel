<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomFieldDefinition extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'label', 'field_type', 'options_json',
        'default_value', 'is_required', 'show_on_card', 'card_position',
        'show_on_list', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'options_json' => 'array',
        'is_required' => 'boolean',
        'show_on_card' => 'boolean',
        'show_on_list' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'card_position' => 'integer',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class, 'field_id');
    }
}
