<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentMedia extends Model
{
    use BelongsToTenant;

    protected $table = 'ai_agent_media';

    protected $fillable = [
        'ai_agent_id',
        'tenant_id',
        'original_name',
        'storage_path',
        'mime_type',
        'file_size',
        'description',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }
}
