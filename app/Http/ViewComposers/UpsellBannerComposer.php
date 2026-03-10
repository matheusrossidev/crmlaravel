<?php

declare(strict_types=1);

namespace App\Http\ViewComposers;

use App\Models\UpsellTriggerLog;
use Illuminate\View\View;

class UpsellBannerComposer
{
    public function compose(View $view): void
    {
        $banner = null;

        if (auth()->check() && auth()->user()->tenant_id) {
            $banner = UpsellTriggerLog::withoutGlobalScope('tenant')
                ->where('tenant_id', auth()->user()->tenant_id)
                ->whereIn('action_type', ['banner', 'all'])
                ->whereNull('clicked_at')
                ->where('fired_at', '>=', now()->subDays(7))
                ->with('trigger')
                ->orderByDesc('fired_at')
                ->first();
        }

        $view->with('upsellBanner', $banner);
    }
}
