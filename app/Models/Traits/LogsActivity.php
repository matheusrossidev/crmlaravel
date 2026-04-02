<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\AuditLog;

trait LogsActivity
{
    protected static array $auditIgnoredFields = [
        'updated_at', 'created_at', 'remember_token', 'password',
        'totp_secret', 'totp_backup_codes', 'access_token',
        // Noisy fields that change on every request/interaction
        'last_login_at', 'last_login_ip',
        'unread_count', 'last_message_at', 'last_inbound_at', 'first_response_at',
        'followup_count', 'last_followup_at',
    ];

    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            static::logAudit($model, 'created', [], $model->getAttributes());
        });

        static::updated(function ($model) {
            $dirty = $model->getDirty();
            $clean = collect($dirty)->keys()
                ->reject(fn ($k) => in_array($k, static::$auditIgnoredFields, true))
                ->all();

            if (empty($clean)) {
                return;
            }

            $old = [];
            $new = [];
            foreach ($clean as $key) {
                $old[$key] = $model->getOriginal($key);
                $new[$key] = $dirty[$key];
            }

            static::logAudit($model, 'updated', $old, $new);
        });

        static::deleted(function ($model) {
            static::logAudit($model, 'deleted', $model->getAttributes(), []);
        });
    }

    protected static function logAudit($model, string $action, array $old, array $new): void
    {
        // Skip if running in console (migrations, seeders) unless explicitly enabled
        if (app()->runningInConsole() && !config('audit.log_console', false)) {
            return;
        }

        $tenantId = $model->tenant_id
            ?? (function_exists('activeTenantId') ? activeTenantId() : null);

        // Filter sensitive fields from stored data
        $old = collect($old)->except(static::$auditIgnoredFields)->toArray();
        $new = collect($new)->except(static::$auditIgnoredFields)->toArray();

        try {
            AuditLog::create([
                'tenant_id'     => $tenantId,
                'user_id'       => auth()->id(),
                'action'        => $action,
                'entity_type'   => class_basename($model),
                'entity_id'     => $model->getKey(),
                'old_data_json' => $old ?: null,
                'new_data_json' => $new ?: null,
                'ip_address'    => request()->ip(),
                'user_agent'    => mb_substr((string) request()->userAgent(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            // Never let audit logging break the main operation
            \Illuminate\Support\Facades\Log::warning('AuditLog failed', [
                'error' => $e->getMessage(),
                'model' => class_basename($model),
                'action' => $action,
            ]);
        }
    }
}
