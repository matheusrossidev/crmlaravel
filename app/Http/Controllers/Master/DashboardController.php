<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total'         => Tenant::count(),
            'active'        => Tenant::where('status', 'active')->count(),
            'trial'         => Tenant::where('status', 'trial')->count(),
            'partner'       => Tenant::where('status', 'partner')->count(),
            'suspended'     => Tenant::whereIn('status', ['suspended', 'inactive'])->count(),
            'new_month'     => Tenant::whereMonth('created_at', now()->month)
                                     ->whereYear('created_at', now()->year)
                                     ->count(),
        ];

        $recentTenants = Tenant::orderByDesc('created_at')->limit(10)->get();

        return view('master.dashboard', compact('stats', 'recentTenants'));
    }
}
