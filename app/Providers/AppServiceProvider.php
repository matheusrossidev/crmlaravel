<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\AiIntentDetected;
use App\Events\MasterNotificationSent;
use App\Events\WhatsappMessageCreated;
use App\Listeners\LogAuthEvents;
use Illuminate\Auth\Events\Failed as LoginFailed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use App\Http\ViewComposers\UpsellBannerComposer;
use App\Models\Lead;
use App\Models\LostSale;
use App\Models\Product;
use App\Models\Sale;
use App\Observers\LeadObserver;
use App\Observers\LostSaleObserver;
use App\Observers\SaleObserver;
use App\Models\WhatsappTag;
use App\Services\NotificationDispatcher;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Cache invalidation observers
        Lead::observe(LeadObserver::class);
        Sale::observe(SaleObserver::class);
        LostSale::observe(LostSaleObserver::class);

        // Auth audit logging
        $authListener = new LogAuthEvents();
        Event::listen(Login::class, [$authListener, 'handleLogin']);
        Event::listen(Logout::class, [$authListener, 'handleLogout']);
        Event::listen(LoginFailed::class, [$authListener, 'handleFailed']);
        Event::listen(PasswordReset::class, [$authListener, 'handlePasswordReset']);

        Gate::define('viewPulse', fn ($user) => $user->is_super_admin === true);

        Model::shouldBeStrict(!app()->isProduction());

        $this->configureRateLimiting();

        View::composer('tenant.layouts.app', UpsellBannerComposer::class);

        View::composer('tenant.leads._drawer', function ($view): void {
            if (! auth()->check()) {
                $view->with('_configuredTags', collect())->with('allProducts', collect());
                return;
            }

            $view->with('_configuredTags', WhatsappTag::orderBy('sort_order')->get(['name', 'color']));

            // Catalogo de produtos pro modal "adicionar produto" no drawer.
            // Antes era query inline na view (executava em toda inclusao —
            // index, show, kanban). Centralizado aqui pra manter 1 query so.
            $view->with('allProducts', Product::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'price', 'unit']));
        });

        $this->registerNotificationListeners();
    }

    private function registerNotificationListeners(): void
    {
        // WhatsApp inbound message → notification disabled (too noisy for bell)
        // Kept as comment for future re-enabling with filtering
        // Event::listen(WhatsappMessageCreated::class, function (WhatsappMessageCreated $event): void { ... });

        // AI intent signal → push notification
        Event::listen(AiIntentDetected::class, function (AiIntentDetected $event): void {
            // AiIntentDetected has private $signal — we use broadcastWith() data
            $data = $event->broadcastWith();

            app(NotificationDispatcher::class)->dispatch(
                'ai_intent',
                [
                    'contact_name' => $data['contact_name'] ?? 'Contato',
                    'intent_type' => $data['intent_type'] ?? 'compra',
                    'url' => isset($data['conversation_id']) ? '/chats?conv=' . $data['conversation_id'] : null,
                ],
                // tenantId is also private, so derive from broadcastOn channel name
                (int) str_replace('private-tenant.', '', $event->broadcastOn()->name),
            );
        });

        // Master notification → push to specific tenant or all
        Event::listen(MasterNotificationSent::class, function (MasterNotificationSent $event): void {
            app(NotificationDispatcher::class)->dispatch(
                'master_notification',
                [
                    'title' => $event->notification->title,
                    'body' => $event->notification->body,
                ],
                $event->tenantId,
            );
        });
    }

    private function configureRateLimiting(): void
    {
        // Registration: 5 attempts per hour per IP
        RateLimiter::for('register', fn (Request $request) =>
            Limit::perHour(5)->by($request->ip())
        );

        // Password reset: 3 attempts per hour per IP
        RateLimiter::for('password-reset', fn (Request $request) =>
            Limit::perHour(3)->by($request->ip())
        );

        // API: 60 requests per minute per API key
        RateLimiter::for('api', fn (Request $request) =>
            Limit::perMinute(60)->by($request->header('X-API-Key', $request->ip()))
        );

        // Webhooks: 120 requests per minute per IP
        RateLimiter::for('webhooks', fn (Request $request) =>
            Limit::perMinute(120)->by($request->ip())
        );

        // Widget messages: 20 per minute per IP (public endpoint)
        RateLimiter::for('widget', fn (Request $request) =>
            Limit::perMinute(20)->by($request->ip())
        );
    }
}
