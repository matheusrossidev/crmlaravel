<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Snapshot imutável de um relatório gerado pelo user.
 *
 * Criado via POST /relatorios/gerar. Acessível por link público /r/{hash}.
 * Dados congelados em snapshot_json no momento da geração — deletar um lead
 * depois não muda o relatório histórico.
 */
class GeneratedReport extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'hash',
        'title',
        'snapshot_json',
        'filters_json',
        'password_hash',
        'views_count',
        'last_viewed_at',
        'expires_at',
    ];

    protected $casts = [
        'snapshot_json'  => 'array',
        'filters_json'   => 'array',
        'password_hash'  => 'hashed',
        'views_count'    => 'integer',
        'last_viewed_at' => 'datetime',
        'expires_at'     => 'datetime',
    ];

    protected $hidden = ['password_hash'];

    /**
     * Gera hash único de 20 chars base62 (~119 bits de entropia).
     */
    public static function generateUniqueHash(): string
    {
        do {
            $hash = Str::random(20);
        } while (self::withoutGlobalScope('tenant')->where('hash', $hash)->exists());

        return $hash;
    }

    public function hasPassword(): bool
    {
        return ! empty($this->password_hash);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function publicUrl(): string
    {
        return url('/r/' . $this->hash);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
