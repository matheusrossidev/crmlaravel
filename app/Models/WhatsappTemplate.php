<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappTemplate extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'whatsapp_instance_id',
        'name',
        'language',
        'category',
        'components',
        'sample_variables',
        'status',
        'meta_template_id',
        'rejected_reason',
        'quality_rating',
        'last_synced_at',
    ];

    protected $casts = [
        'components'       => 'array',
        'sample_variables' => 'array',
        'last_synced_at'   => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WhatsappInstance::class, 'whatsapp_instance_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['PENDING', 'IN_APPEAL', 'PENDING_DELETION'], true);
    }

    public function isRejected(): bool
    {
        return $this->status === 'REJECTED';
    }

    /**
     * Extrai os placeholders {{1}}, {{2}}... do body.
     * Retorna array ordenado e único de índices (strings): ['1', '2', ...].
     */
    public function getVariablesAttribute(): array
    {
        $body = '';
        foreach ((array) $this->components as $c) {
            if (($c['type'] ?? '') === 'BODY' || ($c['type'] ?? '') === 'body') {
                $body = (string) ($c['text'] ?? '');
                break;
            }
        }

        preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $body, $m);
        $ids = array_values(array_unique($m[1] ?? []));
        sort($ids, SORT_NUMERIC);
        return $ids;
    }
}
