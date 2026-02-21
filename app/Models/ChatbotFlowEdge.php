<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotFlowEdge extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'flow_id', 'tenant_id',
        'source_node_id', 'source_handle', 'target_node_id',
    ];

    protected $casts = [
        'source_node_id' => 'integer',
        'target_node_id' => 'integer',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(ChatbotFlow::class, 'flow_id');
    }
}
