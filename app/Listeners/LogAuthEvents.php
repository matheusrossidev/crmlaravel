<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;

class LogAuthEvents
{
    public function handleLogin(Login $event): void
    {
        $this->log($event->user, 'login');
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            $this->log($event->user, 'logout');
        }
    }

    public function handleFailed(Failed $event): void
    {
        AuditLog::create([
            'tenant_id'     => null,
            'user_id'       => null,
            'action'        => 'login_failed',
            'entity_type'   => 'User',
            'entity_id'     => null,
            'new_data_json' => ['email' => $event->credentials['email'] ?? '—'],
            'ip_address'    => request()->ip(),
            'user_agent'    => mb_substr((string) request()->userAgent(), 0, 500),
        ]);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->log($event->user, 'password_reset');
    }

    private function log($user, string $action): void
    {
        AuditLog::create([
            'tenant_id'     => $user->tenant_id ?? null,
            'user_id'       => $user->id,
            'action'        => $action,
            'entity_type'   => 'User',
            'entity_id'     => $user->id,
            'ip_address'    => request()->ip(),
            'user_agent'    => mb_substr((string) request()->userAgent(), 0, 500),
        ]);
    }
}
