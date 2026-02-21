<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotFlowNode extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'flow_id', 'tenant_id', 'type', 'label',
        'config', 'canvas_x', 'canvas_y',
    ];

    protected $casts = [
        'config'   => 'array',
        'canvas_x' => 'float',
        'canvas_y' => 'float',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(ChatbotFlow::class, 'flow_id');
    }
}
