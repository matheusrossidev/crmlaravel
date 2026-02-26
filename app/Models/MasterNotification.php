<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterNotification extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
        'body',
        'type',
    ];
}
