<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cs;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CsDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $thirtyDaysAgo  = now()->subDays(30);
        $fourteenDaysAgo = now()->subDays(14);

        $query = Tenant::whereNotIn('status', ['partner'])
            ->orderBy('name');

        // Filtros
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($plan = $request->input('plan')) {
            $query->where('plan', $plan);
        }

        $tenants = $query->get();

        // Batch queries for performance
        $tenantIds = $tenants->pluck('id');

        // Last login per tenant (admin user)
        $lastLogins = User::whereIn('tenant_id', $tenantIds)
            ->select('tenant_id', DB::raw('MAX(last_login_at) as last_login'))
            ->groupBy('tenant_id')
            ->pluck('last_login', 'tenant_id');

        // WA messages count last 30d
        $waMessages = DB::table('whatsapp_messages')
            ->whereIn('tenant_id', $tenantIds)
            ->where('sent_at', '>=', $thirtyDaysAgo)
            ->select('tenant_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('tenant_id')
            ->pluck('cnt', 'tenant_id');

        // Leads created last 30d
        $leadsCreated = DB::table('leads')
            ->whereIn('tenant_id', $tenantIds)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->select('tenant_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('tenant_id')
            ->pluck('cnt', 'tenant_id');

        // Feature counts for health score
        $waInstances = DB::table('whatsapp_instances')
            ->whereIn('tenant_id', $tenantIds)
            ->where('status', 'connected')
            ->select('tenant_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('tenant_id')
            ->pluck('cnt', 'tenant_id');

        $chatbotFlows = DB::table('chatbot_flows')
            ->whereIn('tenant_id', $tenantIds)
            ->where('is_active', true)
            ->select('tenant_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('tenant_id')
            ->pluck('cnt', 'tenant_id');

        $aiAgents = DB::table('ai_agents')
            ->whereIn('tenant_id', $tenantIds)
            ->where('is_active', true)
            ->select('tenant_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('tenant_id')
            ->pluck('cnt', 'tenant_id');

        $automations = DB::table('automations')
            ->whereIn('tenant_id', $tenantIds)
            ->where('is_active', true)
            ->select('tenant_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('tenant_id')
            ->pluck('cnt', 'tenant_id');

        // WA messages last 14d for health
        $waMessages14d = DB::table('whatsapp_messages')
            ->whereIn('tenant_id', $tenantIds)
            ->where('sent_at', '>=', $fourteenDaysAgo)
            ->select('tenant_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('tenant_id')
            ->pluck('cnt', 'tenant_id');

        // Leads last 14d for health
        $leads14d = DB::table('leads')
            ->whereIn('tenant_id', $tenantIds)
            ->where('created_at', '>=', $fourteenDaysAgo)
            ->select('tenant_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('tenant_id')
            ->pluck('cnt', 'tenant_id');

        // Build tenant data with metrics
        $tenantsData = $tenants->map(function (Tenant $t) use (
            $lastLogins, $waMessages, $leadsCreated, $waInstances,
            $chatbotFlows, $aiAgents, $automations, $waMessages14d, $leads14d
        ) {
            $lastLogin = $lastLogins[$t->id] ?? null;
            $daysInactive = $lastLogin ? (int) now()->diffInDays($lastLogin) : 999;

            $features = 0;
            if (($waInstances[$t->id] ?? 0) > 0) $features++;
            if ($t->onboarding_completed_at) $features++;
            if (($chatbotFlows[$t->id] ?? 0) > 0) $features++;
            if (($aiAgents[$t->id] ?? 0) > 0) $features++;
            if (($automations[$t->id] ?? 0) > 0) $features++;

            $health = $this->calculateHealth(
                $daysInactive,
                (int) ($waMessages14d[$t->id] ?? 0),
                (int) ($leads14d[$t->id] ?? 0),
                $features,
                $t->status,
                $t->subscription_status,
            );

            return [
                'tenant'         => $t,
                'last_login'     => $lastLogin,
                'days_inactive'  => $daysInactive,
                'wa_messages'    => (int) ($waMessages[$t->id] ?? 0),
                'leads_created'  => (int) ($leadsCreated[$t->id] ?? 0),
                'health'         => $health,
            ];
        });

        // Sort by health ASC (worst first)
        if ($request->input('sort') === 'name') {
            $tenantsData = $tenantsData->sortBy('tenant.name');
        } elseif ($request->input('sort') === 'inactive') {
            $tenantsData = $tenantsData->sortByDesc('days_inactive');
        } else {
            $tenantsData = $tenantsData->sortBy('health');
        }

        // Filter by health
        if ($healthFilter = $request->input('health')) {
            $tenantsData = $tenantsData->filter(function ($d) use ($healthFilter) {
                if ($healthFilter === 'red') return $d['health'] <= 3;
                if ($healthFilter === 'yellow') return $d['health'] >= 4 && $d['health'] <= 6;
                if ($healthFilter === 'green') return $d['health'] >= 7;
                return true;
            });
        }

        return view('cs.index', [
            'tenantsData' => $tenantsData->values(),
            'filters'     => $request->only(['search', 'status', 'plan', 'health', 'sort']),
        ]);
    }

    public function show(int $tenantId): View
    {
        $tenant = Tenant::findOrFail($tenantId);
        $thirtyDaysAgo = now()->subDays(30);

        // Users
        $users = User::where('tenant_id', $tenant->id)
            ->orderByDesc('last_login_at')
            ->get(['id', 'name', 'email', 'role', 'last_login_at', 'email_verified_at', 'avatar']);

        // KPIs (30d)
        $leads30d = DB::table('leads')
            ->where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $waMessages30d = DB::table('whatsapp_messages')
            ->where('tenant_id', $tenant->id)
            ->where('sent_at', '>=', $thirtyDaysAgo)
            ->count();

        $sales30d = DB::table('sales')
            ->where('tenant_id', $tenant->id)
            ->where('closed_at', '>=', $thirtyDaysAgo)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(value), 0) as total')
            ->first();

        $tokensUsed = DB::table('ai_usage_logs')
            ->where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->sum('tokens_total');

        $automationsRun = DB::table('automations')
            ->where('tenant_id', $tenant->id)
            ->where('last_run_at', '>=', $thirtyDaysAgo)
            ->sum('run_count');

        $activeUsers7d = User::where('tenant_id', $tenant->id)
            ->where('last_login_at', '>=', now()->subDays(7))
            ->count();

        // Feature adoption
        $waConnected = DB::table('whatsapp_instances')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'connected')
            ->exists();

        $igConnected = DB::table('instagram_instances')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'connected')
            ->exists();

        $hasLeads = DB::table('leads')
            ->where('tenant_id', $tenant->id)
            ->exists();

        $hasChatbot = DB::table('chatbot_flows')
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->exists();

        $hasAiAgent = DB::table('ai_agents')
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->exists();

        $hasAutomation = DB::table('automations')
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->exists();

        $hasCalendar = DB::table('oauth_connections')
            ->where('tenant_id', $tenant->id)
            ->where('platform', 'google')
            ->exists();

        // Payments (last 10)
        $payments = DB::table('payment_logs')
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('paid_at')
            ->limit(10)
            ->get();

        // Daily activity chart (30d)
        $dailyActivity = DB::table('whatsapp_messages')
            ->where('tenant_id', $tenant->id)
            ->where('sent_at', '>=', $thirtyDaysAgo)
            ->selectRaw("DATE(sent_at) as day, COUNT(*) as messages")
            ->groupBy('day')
            ->pluck('messages', 'day');

        $dailyLeads = DB::table('leads')
            ->where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw("DATE(created_at) as day, COUNT(*) as leads")
            ->groupBy('day')
            ->pluck('leads', 'day');

        // Build 30-day chart data
        $chartLabels = [];
        $chartMessages = [];
        $chartLeads = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = now()->subDays($i)->format('d/m');
            $chartMessages[] = (int) ($dailyActivity[$day] ?? 0);
            $chartLeads[] = (int) ($dailyLeads[$day] ?? 0);
        }

        // Last login
        $lastLogin = $users->max('last_login_at');

        return view('cs.show', compact(
            'tenant', 'users', 'leads30d', 'waMessages30d', 'sales30d',
            'tokensUsed', 'automationsRun', 'activeUsers7d',
            'waConnected', 'igConnected', 'hasLeads', 'hasChatbot',
            'hasAiAgent', 'hasAutomation', 'hasCalendar',
            'payments', 'chartLabels', 'chartMessages', 'chartLeads',
            'lastLogin',
        ));
    }

    private function calculateHealth(
        int $daysInactive,
        int $waMessages14d,
        int $leads14d,
        int $featuresAdopted,
        string $status,
        ?string $subscriptionStatus,
    ): int {
        // 1. Login recency (0-2)
        $loginScore = match (true) {
            $daysInactive <= 7  => 2,
            $daysInactive <= 14 => 1,
            default             => 0,
        };

        // 2. WA volume (0-2)
        $waScore = match (true) {
            $waMessages14d >= 10 => 2,
            $waMessages14d >= 1  => 1,
            default              => 0,
        };

        // 3. Leads created (0-2)
        $leadsScore = match (true) {
            $leads14d >= 5 => 2,
            $leads14d >= 1 => 1,
            default        => 0,
        };

        // 4. Features adopted (0-2)
        $featuresScore = match (true) {
            $featuresAdopted >= 4 => 2,
            $featuresAdopted >= 2 => 1,
            default               => 0,
        };

        // 5. Payment status (0-2)
        $paymentScore = match (true) {
            in_array($subscriptionStatus, ['overdue', 'inactive'], true)
                || in_array($status, ['suspended', 'inactive'], true) => 0,
            $status === 'trial' => 1,
            default             => 2,
        };

        return $loginScore + $waScore + $leadsScore + $featuresScore + $paymentScore;
    }
}
