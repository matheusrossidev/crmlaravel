<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AiConfiguration extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'llm_provider', 'llm_api_key', 'llm_model',
    ];

    protected $hidden = [
        'llm_api_key', // nunca expor a chave em JSON por padrão
    ];
}
