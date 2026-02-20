<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class WhatsappTag extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'color', 'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
