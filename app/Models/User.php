<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasPushSubscriptions;

    protected $fillable = [
        'tenant_id', 'name', 'email', 'password', 'role',
        'is_super_admin', 'avatar', 'last_login_at', 'dashboard_config',
        'notification_preferences',
        'email_verified_at', 'verification_token',
        'can_see_all_conversations',
        'totp_secret', 'totp_enabled', 'totp_backup_codes',
    ];

    protected $hidden = [
        'password', 'remember_token', 'totp_secret', 'totp_backup_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'can_see_all_conversations' => 'boolean',
            'dashboard_config' => 'array',
            'notification_preferences' => 'array',
            'totp_enabled' => 'boolean',
            'totp_secret' => 'encrypted',
            'totp_backup_codes' => 'encrypted:array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_to');
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
    }

    public function pipelines(): BelongsToMany
    {
        return $this->belongsToMany(Pipeline::class);
    }

    /**
     * Retorna IDs das pipelines permitidas, ou null se sem restrição.
     * Admin sempre vê tudo. Se nenhuma pipeline atribuída = sem restrição.
     *
     * @return int[]|null
     */
    public function allowedPipelineIds(): ?array
    {
        if ($this->isAdmin()) {
            return null;
        }

        $ids = $this->pipelines()->pluck('pipelines.id')->toArray();

        return count($ids) > 0 ? $ids : null;
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin']);
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    // ── Notification Preferences ─────────────────────────────

    public function wantsNotification(string $type, string $channel = 'browser'): bool
    {
        $prefs = $this->notification_preferences ?? [];

        return (bool) ($prefs[$channel][$type] ?? true);
    }

    public function wantsSound(): bool
    {
        $prefs = $this->notification_preferences ?? [];

        return (bool) ($prefs['sound']['enabled'] ?? true);
    }

    public function getSoundForType(string $type): string
    {
        $prefs = $this->notification_preferences ?? [];

        return $prefs['sound'][$type] ?? $prefs['sound']['default'] ?? 'notification-chime';
    }

    public function isInQuietHours(): bool
    {
        $prefs = $this->notification_preferences ?? [];

        if (! ($prefs['quiet_hours']['enabled'] ?? false)) {
            return false;
        }

        $now = now()->format('H:i');
        $start = $prefs['quiet_hours']['start'] ?? '22:00';
        $end = $prefs['quiet_hours']['end'] ?? '07:00';

        if ($start <= $end) {
            return $now >= $start && $now < $end;
        }

        return $now >= $start || $now < $end;
    }
}
