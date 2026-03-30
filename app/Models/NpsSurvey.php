<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NpsSurvey extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'type', 'question', 'follow_up_question',
        'trigger', 'delay_hours', 'send_via', 'is_active', 'slug', 'thank_you_message',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'delay_hours' => 'integer',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class, 'survey_id');
    }

    public function npsScore(?string $from = null, ?string $to = null): float
    {
        $query = $this->responses()->where('status', 'answered');
        if ($from) $query->where('answered_at', '>=', $from);
        if ($to) $query->where('answered_at', '<=', $to);

        $total = $query->count();
        if ($total === 0) return 0;

        $promoters = (clone $query)->whereBetween('score', [9, 10])->count();
        $detractors = (clone $query)->whereBetween('score', [0, 6])->count();

        return round((($promoters - $detractors) / $total) * 100, 1);
    }
}
