<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\WhatsappTag;
use Illuminate\Database\Eloquent\Model;
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

        View::composer('tenant.leads._drawer', function ($view): void {
            $tags = auth()->check()
                ? WhatsappTag::orderBy('sort_order')->get(['name', 'color'])
                : collect();
            $view->with('_configuredTags', $tags);
        });
    }
}
