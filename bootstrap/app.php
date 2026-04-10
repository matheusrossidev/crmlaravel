<?php

use App\Http\Middleware\AgnoInternalMiddleware;
use App\Http\Middleware\ApiKeyMiddleware;
use App\Http\Middleware\CheckPlanLimit;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TenantMiddleware;
use App\Http\Middleware\TwoFactorMiddleware;
use App\Http\Middleware\CsAgentMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

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
            'locale'        => SetLocale::class,
            'super_admin'   => SuperAdminMiddleware::class,
            'api_key'       => ApiKeyMiddleware::class,
            'agno_internal' => AgnoInternalMiddleware::class,
            'role'          => RoleMiddleware::class,
            'plan.limit'    => CheckPlanLimit::class,
            '2fa'           => TwoFactorMiddleware::class,
            'cs_agent'      => CsAgentMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('billing:check-trials')->dailyAt('06:00');
        // withoutOverlapping(5) = mutex expira em 5 min se o processo crashar.
        // Sem timeout, default é 24h — mutex travado = cron morto por 24h.
        // Bug histórico: 2026-04-10, mensagens agendadas não enviaram por horas.
        $schedule->command('whatsapp:send-scheduled')->everyMinute()->withoutOverlapping(5);
        $schedule->command('whatsapp:send-event-reminders')->everyMinute()->withoutOverlapping(5);
        $schedule->command('automations:process-date-triggers')->dailyAt('08:00');
        $schedule->command('automations:process-recurring')->hourly()->withoutOverlapping(30);
        $schedule->command('scoring:decay')->dailyAt('09:00');
        $schedule->command('sequences:process')->everyMinute()->withoutOverlapping(5);
        $schedule->command('master:weekly-report')->weeklyOn(5, '12:00'); // sexta ao meio-dia
        $schedule->command('goals:process-recurrence')->dailyAt('00:05');
        $schedule->command('goals:check-alerts')->dailyAt('09:00');
        $schedule->command('partners:release-commissions')->dailyAt('06:30');
        $schedule->command('instagram:refresh-tokens')->dailyAt('03:00');
        $schedule->command('leads:detect-duplicates')->dailyAt('03:30');
        $schedule->command('users:send-reengagement')->dailyAt('10:00');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })->create();
