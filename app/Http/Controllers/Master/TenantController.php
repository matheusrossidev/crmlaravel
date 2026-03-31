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
        $tenants = Tenant::withCount([
                'users',
                'leads' => fn ($q) => $q->withoutGlobalScope('tenant'),
            ])
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

        $plans = PlanDefinition::orderBy('price_monthly')
            ->get(['id', 'name', 'display_name', 'price_monthly', 'trial_days']);

        return view('master.tenants.show', compact('tenant', 'users', 'leadsStats', 'plans'));
    }

    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $validPlans = \App\Models\PlanDefinition::pluck('name')->merge(['partner', 'unlimited'])->unique()->implode(',');

        $request->validate([
            'status'        => 'required|in:active,inactive,suspended,trial,partner',
            'plan'          => 'required|in:' . $validPlans,
            'trial_ends_at' => 'nullable|date',
            'max_users'                  => 'nullable|integer|min:0',
            'max_leads'                  => 'nullable|integer|min:0',
            'max_pipelines'              => 'nullable|integer|min:0',
            'max_custom_fields'          => 'nullable|integer|min:0',
            'max_chatbot_flows'          => 'nullable|integer|min:0',
            'max_ai_agents'              => 'nullable|integer|min:0',
            'max_whatsapp_instances'     => 'nullable|integer|min:0',
            'ai_analyst_enabled'            => 'nullable|boolean',
            'integration_whatsapp'          => 'nullable|boolean',
            'integration_google_calendar'   => 'nullable|boolean',
            'integration_instagram'         => 'nullable|boolean',
            'integration_facebook_ads'      => 'nullable|boolean',
            'integration_google_ads'        => 'nullable|boolean',
            'partner_billing_starts_at'     => 'nullable|date',
        ]);

        $settings = $tenant->settings_json ?? [];
        $settings['ai_analyst_enabled']         = $request->boolean('ai_analyst_enabled');
        $settings['integration_whatsapp']        = $request->boolean('integration_whatsapp');
        $settings['integration_google_calendar'] = $request->boolean('integration_google_calendar');
        $settings['integration_instagram']       = $request->boolean('integration_instagram');
        $settings['integration_facebook_ads']    = $request->boolean('integration_facebook_ads');
        $settings['integration_google_ads']      = $request->boolean('integration_google_ads');

        $tenant->update(array_merge(
            $request->only('status', 'plan', 'trial_ends_at', 'max_users', 'max_leads', 'max_pipelines', 'max_custom_fields', 'max_chatbot_flows', 'max_ai_agents', 'max_departments', 'max_whatsapp_instances', 'partner_billing_starts_at'),
            ['settings_json' => $settings]
        ));

        return response()->json(['success' => true, 'message' => 'Empresa atualizada.']);
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
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
