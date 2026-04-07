<?php

declare(strict_types=1);

namespace App\Sentry;

use Sentry\Event;
use Sentry\UserDataBag;

/**
 * Sentry before_send callback.
 *
 * Implementado como classe (com método estático) em vez de closure inline
 * em config/sentry.php porque closures NÃO são serializáveis pelo
 * `php artisan config:cache` (Laravel usa var_export). Callable em formato
 * [ClassName::class, 'method'] é apenas array de strings → serializável.
 *
 * Comportamento: anexa user_id, email, tenant_id e tenant_name ao scope
 * do Sentry sempre que houver usuário autenticado, pra cada evento ter
 * contexto multi-tenant no painel.
 */
class BeforeSend
{
    public static function handle(Event $event): ?Event
    {
        if (function_exists('auth') && auth()->check()) {
            $user = auth()->user();

            $event->setUser(UserDataBag::createFromArray([
                'id'    => $user->id,
                'email' => $user->email,
            ]));

            $event->setTag('tenant_id',   (string) ($user->tenant_id ?? 'none'));
            $event->setTag('tenant_name', optional($user->tenant)->name ?? 'none');
        }

        return $event;
    }
}
