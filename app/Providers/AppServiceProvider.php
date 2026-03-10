<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\ViewComposers\UpsellBannerComposer;
use App\Models\WhatsappTag;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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

        Model::shouldBeStrict(!app()->isProduction());

        $this->configureRateLimiting();

        View::composer('tenant.layouts.app', UpsellBannerComposer::class);

        View::composer('tenant.leads._drawer', function ($view): void {
            $tags = auth()->check()
                ? WhatsappTag::orderBy('sort_order')->get(['name', 'color'])
                : collect();
            $view->with('_configuredTags', $tags);
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
