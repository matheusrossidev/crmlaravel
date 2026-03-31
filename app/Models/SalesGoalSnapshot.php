<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesGoalSnapshot extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'user_id', 'goal_id', 'type', 'period',
        'target_value', 'achieved_value', 'percentage',
        'start_date', 'end_date', 'created_at',
    ];

    protected $casts = [
        'target_value'   => 'decimal:2',
        'achieved_value' => 'decimal:2',
        'percentage'     => 'decimal:1',
        'start_date'     => 'date',
        'end_date'       => 'date',
        'created_at'     => 'datetime',
    ];

    public function goal(): BelongsTo
    {
        return $this->belongsTo(SalesGoal::class, 'goal_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
