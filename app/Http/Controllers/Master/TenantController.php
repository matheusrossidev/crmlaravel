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
    use Traits\ChecksMasterPermission;

    public function index(): View
    {
        $this->authorizeModule('tenants');
        $tenants = Tenant::withCount([
                'users',
                'leads' => fn ($q) => $q->withoutGlobalScope('tenant'),
            ])
            ->with(['referringAgency:id,name'])
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
        $this->authorizeModule('tenants.create');
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
        $this->authorizeModule('tenants');
        $users = User::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        $totalLeads = Lead::forTenant($tenant->id)->count();
        $wonLeads   = Lead::forTenant($tenant->id)
            ->whereHas('stage', fn ($q) => $q->where('is_won', true))
            ->count();
        $lostLeads  = Lead::forTenant($tenant->id)
            ->whereHas('stage', fn ($q) => $q->where('is_lost', true))
            ->count();

        $leadsStats = [
            'total'  => $totalLeads,
            'active' => $totalLeads - $wonLeads - $lostLeads,
            'won'    => $wonLeads,
            'lost'   => $lostLeads,
        ];

        $plans = PlanDefinition::orderBy('price_monthly')->get();
        $limits = config('plan_limits', []);
        $featureFlags = \App\Models\FeatureFlag::orderBy('sort_order')->get();

        $featureOverrides = \Illuminate\Support\Facades\DB::table('feature_tenant')
            ->where('tenant_id', $tenant->id)
            ->pluck('is_enabled', 'feature_id')
            ->toArray();

        return view('master.tenants.show', compact(
            'tenant', 'users', 'leadsStats', 'plans', 'limits', 'featureFlags', 'featureOverrides'
        ));
    }

    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorizeModule('tenants.edit');
        $validPlans = \App\Models\PlanDefinition::pluck('name')->merge(['partner', 'unlimited'])->unique()->implode(',');

        $request->validate([
            'status'        => 'required|in:active,inactive,suspended,trial,partner',
            'plan'          => 'required|in:' . $validPlans,
            'trial_ends_at' => 'nullable|date',
            'partner_billing_starts_at' => 'nullable|date',
            'limits'        => 'nullable|array',
            'features'      => 'nullable|array',
        ]);

        $updates = $request->only('status', 'plan', 'trial_ends_at', 'partner_billing_starts_at');

        // Limites dinâmicos via config/plan_limits.php
        $limitsInput = $request->input('limits', []);
        foreach (config('plan_limits', []) as $cfg) {
            $col = $cfg['column'] ?? null;
            if (!$col) continue;
            if (!array_key_exists($col, $limitsInput)) continue;
            $raw = $limitsInput[$col];
            $updates[$col] = ($raw === '' || $raw === null) ? null : (int) $raw;
        }

        $tenant->update($updates);

        // Feature overrides via pivot feature_tenant
        $featuresInput = $request->input('features', []);
        $flags = \App\Models\FeatureFlag::pluck('id', 'slug');
        foreach ($featuresInput as $slug => $state) {
            $flagId = $flags[$slug] ?? null;
            if (!$flagId) continue;
            if ($state === 'inherit' || $state === '' || $state === null) {
                \Illuminate\Support\Facades\DB::table('feature_tenant')
                    ->where('tenant_id', $tenant->id)
                    ->where('feature_id', $flagId)
                    ->delete();
            } else {
                \Illuminate\Support\Facades\DB::table('feature_tenant')->updateOrInsert(
                    ['tenant_id' => $tenant->id, 'feature_id' => $flagId],
                    ['is_enabled' => (bool) (int) $state, 'updated_at' => now(), 'created_at' => now()],
                );
            }
            cache()->forget("feature:{$tenant->id}:{$slug}");
        }

        return response()->json(['success' => true, 'message' => 'Empresa atualizada.']);
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        $this->authorizeModule('tenants.delete');
        $tenant->delete();

        return response()->json(['success' => true]);
    }

    public function approvePartner(Tenant $tenant): JsonResponse
    {
        if ($tenant->status !== 'pending_approval') {
            return response()->json(['success' => false, 'message' => 'Tenant não está pendente de aprovação.'], 422);
        }

        $tenant->update(['status' => 'partner']);

        // Activate the partner code
        $code = \App\Models\PartnerAgencyCode::where('tenant_id', $tenant->id)->first();
        if ($code) {
            $code->update(['is_active' => true]);
        }

        // Notify partner via email + WhatsApp
        $admin = $tenant->users()->where('role', 'admin')->first();
        if ($admin) {
            try {
                \Illuminate\Support\Facades\Mail::to($admin->email)
                    ->send(new \App\Mail\PartnerApproved($admin, $tenant, $code?->code ?? ''));
            } catch (\Throwable) {}
        }

        if ($tenant->phone) {
            try {
                $instance = \App\Models\WhatsappInstance::where('session_name', 'tenant_12')
                    ->where('status', 'connected')->first();
                if ($instance) {
                    $waha = new \App\Services\WahaService($instance->session_name);
                    $waha->sendText(
                        preg_replace('/\D/', '', $tenant->phone) . '@c.us',
                        "🎉 *Parabéns!* Seu cadastro como parceiro Syncro foi aprovado!\n\n"
                        . "Seu código de parceiro: *{$code?->code}*\n\n"
                        . "Acesse a plataforma e comece a indicar clientes."
                    );
                }
            } catch (\Throwable) {}
        }

        return response()->json(['success' => true, 'message' => 'Parceiro aprovado com sucesso.']);
    }

    public function rejectPartner(Tenant $tenant): JsonResponse
    {
        if ($tenant->status !== 'pending_approval') {
            return response()->json(['success' => false, 'message' => 'Tenant não está pendente de aprovação.'], 422);
        }

        $tenant->update(['status' => 'rejected']);

        return response()->json(['success' => true, 'message' => 'Parceiro rejeitado.']);
    }
}
