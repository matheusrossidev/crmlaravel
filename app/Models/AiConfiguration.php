<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiConfiguration extends Model
{
    // Configuração global da plataforma — sem BelongsToTenant

    protected $fillable = [
        'llm_provider', 'llm_api_key', 'llm_model',
    ];

    protected $hidden = [
        'llm_api_key', // nunca expor a chave em JSON por padrão
    ];
}
