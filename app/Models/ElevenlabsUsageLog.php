<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElevenlabsUsageLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'agent_id',
        'conversation_id',
        'characters_used',
        'created_at',
    ];

    protected $casts = [
        'characters_used' => 'integer',
        'created_at'      => 'datetime',
    ];
}
