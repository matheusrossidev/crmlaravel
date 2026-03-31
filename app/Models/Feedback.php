<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $table = 'feedbacks';

    protected $fillable = [
        'tenant_id', 'user_id', 'type', 'area', 'title', 'description',
        'impact', 'priority', 'evidence_path', 'can_contact', 'contact_email',
        'url_origin', 'plan_name', 'user_role', 'status', 'admin_notes',
    ];

    protected $casts = [
        'priority'    => 'integer',
        'can_contact' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }
}
