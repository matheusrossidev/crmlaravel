<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'name', 'slug', 'type',
        'fields', 'mappings',
        'pipeline_id', 'stage_id', 'assigned_user_id', 'source_utm',
        'confirmation_type', 'confirmation_value',
        'notify_emails', 'sequence_id', 'list_id',
        'send_whatsapp_welcome', 'create_task', 'task_days_offset',
        'max_submissions', 'expires_at', 'is_active', 'views_count',
        'logo_url', 'logo_alignment',
        'brand_color', 'background_color', 'card_color', 'input_bg_color',
        'input_text_color', 'label_color', 'input_border_color',
        'button_color', 'button_text_color', 'font_family', 'border_radius',
    ];

    protected $casts = [
        'fields'                 => 'array',
        'mappings'               => 'array',
        'notify_emails'          => 'array',
        'is_active'              => 'boolean',
        'send_whatsapp_welcome'  => 'boolean',
        'create_task'            => 'boolean',
        'expires_at'             => 'datetime',
        'max_submissions'        => 'integer',
        'task_days_offset'       => 'integer',
        'views_count'            => 'integer',
        'border_radius'          => 'integer',
    ];

    // ── Relations ────────────────────────────────────────────────────

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'stage_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function isAcceptingSubmissions(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_submissions && $this->submissions()->count() >= $this->max_submissions) {
            return false;
        }

        return true;
    }

    public function getPublicUrl(): string
    {
        return rtrim(config('app.url'), '/') . '/f/' . $this->slug;
    }

    public function getSubmissionsCount(): int
    {
        return $this->submissions()->count();
    }

    public function getConversionRate(): float
    {
        if ($this->views_count === 0) {
            return 0;
        }

        return round(($this->getSubmissionsCount() / $this->views_count) * 100, 1);
    }
}
