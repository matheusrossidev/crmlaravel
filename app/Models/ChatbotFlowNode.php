<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotFlowNode extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'flow_id', 'tenant_id', 'type', 'label',
        'config', 'canvas_x', 'canvas_y', 'is_start',
    ];

    protected $casts = [
        'config'   => 'array',
        'canvas_x' => 'float',
        'canvas_y' => 'float',
        'is_start' => 'boolean',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(ChatbotFlow::class, 'flow_id');
    }
}
