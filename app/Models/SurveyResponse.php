<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyResponse extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uuid', 'tenant_id', 'survey_id', 'lead_id', 'user_id',
        'score', 'comment', 'status', 'sent_at', 'answered_at', 'expires_at', 'created_at',
    ];

    protected $casts = [
        'score'       => 'integer',
        'sent_at'     => 'datetime',
        'answered_at' => 'datetime',
        'expires_at'  => 'datetime',
        'created_at'  => 'datetime',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(NpsSurvey::class, 'survey_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function npsCategory(): string
    {
        if ($this->score === null) return 'pending';
        if ($this->score >= 9) return 'promoter';
        if ($this->score >= 7) return 'passive';
        return 'detractor';
    }
}
