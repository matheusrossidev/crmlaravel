<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentKnowledgeFile extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'ai_agent_id',
        'tenant_id',
        'original_name',
        'storage_path',
        'mime_type',
        'extracted_text',
        'status',
        'error_message',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }
}
