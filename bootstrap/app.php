<?php

use App\Http\Middleware\AgnoInternalMiddleware;
use App\Http\Middleware\ApiKeyMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Traefik termina SSL — confia em proxies da rede Docker overlay (10.0.0.0/8, 172.16-31.x)
        // Em produção, TRUSTED_PROXIES deve listar os IPs exatos do Traefik.
        // Fallback: '*' se TRUSTED_PROXIES não estiver configurado (dev local).
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES', '*'),
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PREFIX
        );

        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'tenant'        => TenantMiddleware::class,
            'super_admin'   => SuperAdminMiddleware::class,
            'api_key'       => ApiKeyMiddleware::class,
            'agno_internal' => AgnoInternalMiddleware::class,
            'role'          => RoleMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('billing:check-trials')->dailyAt('06:00');
        $schedule->command('whatsapp:send-scheduled')->everyMinute()->withoutOverlapping();
        $schedule->command('automations:process-date-triggers')->dailyAt('08:00');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
