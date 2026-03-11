<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\AiIntentDetected;
use App\Events\MasterNotificationSent;
use App\Events\WhatsappMessageCreated;
use App\Http\ViewComposers\UpsellBannerComposer;
use App\Models\WhatsappTag;
use App\Services\NotificationDispatcher;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Pulse::authorize(fn ($request) => $request->user()?->is_super_admin === true);

        Model::shouldBeStrict(!app()->isProduction());

        $this->configureRateLimiting();

        View::composer('tenant.layouts.app', UpsellBannerComposer::class);

        View::composer('tenant.leads._drawer', function ($view): void {
            $tags = auth()->check()
                ? WhatsappTag::orderBy('sort_order')->get(['name', 'color'])
                : collect();
            $view->with('_configuredTags', $tags);
        });

        $this->registerNotificationListeners();
    }

    private function registerNotificationListeners(): void
    {
        // WhatsApp inbound message → push notification to tenant users
        Event::listen(WhatsappMessageCreated::class, function (WhatsappMessageCreated $event): void {
            if ($event->message->direction !== 'inbound') {
                return;
            }

            $conversation = $event->message->conversation;
            $contactName = $conversation?->contact_name ?? $conversation?->phone ?? 'Contato';

            app(NotificationDispatcher::class)->dispatch(
                'whatsapp_message',
                [
                    'contact_name' => $contactName,
                    'message_preview' => $event->message->body ?? '',
                    'url' => '/chats?conv=' . $event->message->conversation_id,
                ],
                $event->tenantId,
            );
        });

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
