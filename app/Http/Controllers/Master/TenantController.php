<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\PlanDefinition;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::withCount(['users', 'leads'])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $plans = PlanDefinition::where('is_active', true)
            ->orderBy('price_monthly')
            ->get(['id', 'name', 'display_name', 'price_monthly', 'trial_days']);

        return view('master.tenants.index', compact('tenants', 'plans'));
    }

    public function store(Request $request): JsonResponse
    {
        $planNames = PlanDefinition::where('is_active', true)->pluck('name')->toArray();

        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:8',
            'plan'     => ['required', 'string', 'in:' . implode(',', $planNames)],
        ]);

        $slug = Str::slug($request->input('name')) . '-' . Str::random(6);

        // Determinar status e trial_ends_at a partir da definição do plano
        $planDef      = PlanDefinition::where('name', $request->input('plan'))->first();
        $trialDays    = $planDef?->trial_days;
        $status       = ($trialDays !== null && $trialDays > 0) ? 'trial' : 'active';
        $trialEndsAt  = ($trialDays !== null && $trialDays > 0)
            ? now()->addDays($trialDays)
            : null;

        $tenant = DB::transaction(function () use ($request, $slug, $status, $trialEndsAt) {
            $tenant = Tenant::create([
                'name'          => $request->input('name'),
                'slug'          => $slug,
                'plan'          => $request->input('plan'),
                'status'        => $status,
                'trial_ends_at' => $trialEndsAt,
            ]);

            User::create([
                'tenant_id' => $tenant->id,
                'name'      => $request->input('admin_name', $request->input('name')),
                'email'     => $request->input('email'),
                'password'  => $request->input('password'),
                'role'      => 'admin',
            ]);

            return $tenant;
        });

        return response()->json([
            'success' => true,
            'tenant'  => [
                'id'          => $tenant->id,
                'name'        => $tenant->name,
                'plan'        => $tenant->plan,
                'status'      => $tenant->status,
                'created_at'  => $tenant->created_at->format('d/m/Y'),
                'users_count' => 1,
                'leads_count' => 0,
            ],
        ], 201);
    }

    public function show(Tenant $tenant): View
    {
        $users = User::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        $leadsStats = [
            'total'  => Lead::forTenant($tenant->id)->count(),
            'active' => Lead::forTenant($tenant->id)->whereNull('won_at')->whereNull('lost_at')->count(),
            'won'    => Lead::forTenant($tenant->id)->whereNotNull('won_at')->count(),
            'lost'   => Lead::forTenant($tenant->id)->whereNotNull('lost_at')->count(),
        ];

        $plans = PlanDefinition::orderBy('price_monthly')
            ->get(['id', 'name', 'display_name', 'price_monthly', 'trial_days']);

        return view('master.tenants.show', compact('tenant', 'users', 'leadsStats', 'plans'));
    }

    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'status'        => 'required|in:active,inactive,suspended,trial,partner',
            'plan'          => 'required|in:free,starter,pro,enterprise,partner',
            'trial_ends_at' => 'nullable|date',
            'max_users'     => 'nullable|integer|min:0',
            'max_leads'     => 'nullable|integer|min:0',
            'max_pipelines' => 'nullable|integer|min:0',
        ]);

        $tenant->update($request->only('status', 'plan', 'trial_ends_at', 'max_users', 'max_leads', 'max_pipelines'));

        return response()->json(['success' => true, 'message' => 'Empresa atualizada.']);
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        $tenant->delete();

        return response()->json(['success' => true]);
    }
}
