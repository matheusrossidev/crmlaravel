<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WhatsappButton extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'phone_number',
        'default_message',
        'button_label',
        'website_token',
        'show_floating',
        'is_active',
    ];

    protected $casts = [
        'show_floating' => 'boolean',
        'is_active'     => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $btn) {
            if (empty($btn->website_token)) {
                $btn->website_token = (string) Str::uuid();
            }
        });
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(WhatsappButtonClick::class, 'button_id');
    }
}
