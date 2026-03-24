<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Mail\SubscriptionActivated;
use App\Mail\SubscriptionCancelled;
use App\Models\AiUsageLog;
use App\Models\PartnerAgencyCode;
use App\Models\PlanDefinition;
use App\Models\Tenant;
use App\Models\TenantTokenIncrement;
use App\Models\TokenIncrementPlan;
use App\Services\AsaasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(): View
    {
        $tenant = activeTenant();

        // Página exclusiva para agências parceiras
        if ($tenant->isPartner()) {
            $partnerCode  = PartnerAgencyCode::where('tenant_id', $tenant->id)->first();
            $clientCount  = Tenant::withoutGlobalScope('tenant')
                ->where('referred_by_agency_id', $tenant->id)
                ->count();
            $partnerSince = $partnerCode?->created_at ?? $tenant->created_at;
            $registerLink = $partnerCode
                ? url('/register?agency=' . $partnerCode->code)
                : null;
            $planDef      = PlanDefinition::where('name', 'partner')->first();

            return view('tenant.settings.billing-partner', compact(
                'tenant', 'partnerCode', 'clientCount', 'partnerSince', 'registerLink', 'planDef'
            ));
        }

        $plan   = PlanDefinition::where('name', $tenant->plan)->first();
        $plans  = PlanDefinition::where('is_active', true)->where('is_visible', true)->orderBy('price_monthly')->get();

        $tokenUsedMonth = (int) AiUsageLog::where('tenant_id', $tenant->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('tokens_total');

        $tokenLimit = (int) ($plan?->features_json['ai_tokens_monthly'] ?? 0);

        $tokenExtra = (int) TenantTokenIncrement::where('tenant_id', $tenant->id)
            ->where('status', 'paid')
            ->whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month)
            ->sum('tokens_added');

        $tokenIncrementPlans = TokenIncrementPlan::where('is_active', true)
            ->orderBy('tokens_amount')
            ->get();

        $dailyUsage = AiUsageLog::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as day, SUM(tokens_total) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Buscar histórico de cobranças do Asaas
        $charges = collect();
        if ($tenant->asaas_customer_id) {
            try {
                $asaas = app(AsaasService::class);
                $resp  = $asaas->listCustomerPayments($tenant->asaas_customer_id, ['limit' => 50]);
                $charges = collect($resp['data'] ?? []);
            } catch (\Throwable $e) {
                \Log::warning('BillingController: falha ao buscar cobranças Asaas', ['error' => $e->getMessage()]);
            }
        }

        return view('tenant.settings.billing', compact(
            'tenant', 'plan', 'plans',
            'tokenUsedMonth', 'tokenLimit', 'tokenExtra',
            'tokenIncrementPlans', 'dailyUsage', 'charges'
        ));
    }

    public function showCheckout(): View|RedirectResponse
    {
        $tenant = activeTenant();

        if ($tenant->hasActiveSubscription()) {
            return redirect()->route('dashboard');
        }

        $plan = PlanDefinition::where('name', $tenant->plan)->first();

        if ($tenant->isPartner()) {
            $plans = PlanDefinition::where('name', 'partner')
                ->where('is_active', true)
                ->get();
        } else {
            $plans = PlanDefinition::where('is_active', true)
                ->where('is_visible', true)
                ->where('price_monthly', '>', 0)
                ->orderBy('price_monthly')
                ->get();
        }

        return view('tenant.billing.checkout', compact('tenant', 'plan', 'plans'));
    }

    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_name'     => 'nullable|string|max:50',
            'holder_name'   => 'required|string|max:100',
            'cpf_cnpj'      => 'required|string|max:18',
            'card_number'   => 'required|string|size:16',
            'card_expiry'   => 'required|string|regex:/^\d{2}\/\d{4}$/',
            'card_cvv'      => 'required|string|min:3|max:4',
            'email'         => 'required|email',
            'phone'         => 'nullable|string|max:20',
            'postal_code'   => 'nullable|string|max:9',
            'address'       => 'nullable|string|max:150',
            'address_number'=> 'nullable|string|max:20',
        ]);

        // Validar nome (só letras, mínimo 2 palavras)
        $holderName = trim($data['holder_name']);
        if (! preg_match('/^[\pL\s]+$/u', $holderName) || count(explode(' ', $holderName)) < 2) {
            return response()->json(['success' => false, 'message' => 'Informe nome e sobrenome válidos (apenas letras).'], 422);
        }

        // Validar CPF/CNPJ
        $cpfCnpjRaw = preg_replace('/\D/', '', $data['cpf_cnpj']);
        if (strlen($cpfCnpjRaw) === 11) {
            if (! $this->validaCPF($cpfCnpjRaw)) {
                return response()->json(['success' => false, 'message' => 'CPF inválido.'], 422);
            }
        } elseif (strlen($cpfCnpjRaw) === 14) {
            if (! $this->validaCNPJ($cpfCnpjRaw)) {
                return response()->json(['success' => false, 'message' => 'CNPJ inválido.'], 422);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'CPF ou CNPJ inválido.'], 422);
        }

        $tenant = activeTenant();
        $user   = auth()->user();

        $planName = $data['plan_name'] ?? $tenant->plan;
        $plan = PlanDefinition::where('name', $planName)
            ->where('is_active', true)
            ->first();

        if (!$plan || $plan->price_monthly <= 0) {
            return response()->json(['success' => false, 'message' => 'Plano inválido ou gratuito.'], 422);
        }

        try {
            $asaas = app(AsaasService::class);

            // 1. Criar ou reutilizar customer no Asaas
            $customerId = $tenant->asaas_customer_id;
            if (!$customerId) {
                $customerPayload = [
                    'name'     => $tenant->name,
                    'cpfCnpj'  => preg_replace('/\D/', '', $data['cpf_cnpj']),
                    'email'    => $data['email'],
                    'phone'    => $data['phone'] ?? null,
                ];
                $customer   = $asaas->createCustomer($customerPayload);
                $customerId = $customer['id'];
                $tenant->update(['asaas_customer_id' => $customerId]);
            }

            // 2. Montar dados do cartão
            [$expMonth, $expYear] = explode('/', $data['card_expiry']);

            $subscriptionPayload = [
                'customer'    => $customerId,
                'billingType' => 'CREDIT_CARD',
                'value'       => (float) $plan->price_monthly,
                'nextDueDate' => now()->toDateString(),
                'cycle'       => 'MONTHLY',
                'description' => "Assinatura {$plan->display_name} — {$tenant->name}",
                'creditCard'  => [
                    'holderName'  => $data['holder_name'],
                    'number'      => $data['card_number'],
                    'expiryMonth' => $expMonth,
                    'expiryYear'  => $expYear,
                    'ccv'         => $data['card_cvv'],
                ],
                'creditCardHolderInfo' => [
                    'name'       => $data['holder_name'],
                    'email'      => $data['email'],
                    'cpfCnpj'    => preg_replace('/\D/', '', $data['cpf_cnpj']),
                    'postalCode' => preg_replace('/\D/', '', $data['postal_code'] ?? ''),
                    'addressNumber' => $data['address_number'] ?? 'S/N',
                    'phone'      => $data['phone'] ?? null,
                ],
            ];

            $subscription = $asaas->createSubscription($subscriptionPayload);

            // 3. Atualizar tenant
            $status = strtolower($subscription['status'] ?? 'pending');
            $subscriptionStatus = match($status) {
                'active'  => 'active',
                'pending' => 'pending',
                default   => 'pending',
            };

            $features = $plan->features_json ?? [];
            $tenant->update([
                'plan'                  => $plan->name,
                'asaas_subscription_id' => $subscription['id'],
                'subscription_status'   => $subscriptionStatus,
                'status'                => $subscriptionStatus === 'active'
                    ? ($tenant->isPartner() ? 'partner' : 'active')
                    : $tenant->status,
                'max_users'             => $features['max_users'] ?? 0,
                'max_leads'             => $features['max_leads'] ?? 0,
                'max_pipelines'         => $features['max_pipelines'] ?? 0,
                'max_custom_fields'     => $features['max_custom_fields'] ?? 0,
                'max_departments'       => $features['max_departments'] ?? 0,
                'max_chatbot_flows'     => $features['max_chatbot_flows'] ?? 0,
                'max_ai_agents'         => $features['max_ai_agents'] ?? 0,
            ]);

            // 4. Enviar email se ativo
            if ($subscriptionStatus === 'active') {
                try {
                    Mail::to($user->email)->send(new SubscriptionActivated($user, $tenant, $plan));
                } catch (\Throwable $e) {
                    \Log::warning('Falha ao enviar email SubscriptionActivated', ['error' => $e->getMessage()]);
                }

                return response()->json([
                    'success'  => true,
                    'message'  => 'Assinatura confirmada! Seu acesso está liberado.',
                    'redirect' => route('dashboard'),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Assinatura criada. Aguardando confirmação do pagamento.',
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('BillingController::subscribe erro inesperado', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar assinatura. Tente novamente.',
            ], 500);
        }
    }

    public function cancel(Request $request): JsonResponse
    {
        $tenant = activeTenant();
        $user   = auth()->user();

        if (!$tenant->asaas_subscription_id) {
            return response()->json(['success' => false, 'message' => 'Nenhuma assinatura ativa.'], 422);
        }

        try {
            $asaas = app(AsaasService::class);
            $asaas->cancelSubscription($tenant->asaas_subscription_id);

            $tenant->update([
                'subscription_status'   => 'cancelled',
                'asaas_subscription_id' => null,
                'status'                => 'inactive',
            ]);

            try {
                $plan = PlanDefinition::where('name', $tenant->plan)->first();
                Mail::to($user->email)->send(new SubscriptionCancelled($user, $tenant, $plan));
            } catch (\Throwable $e) {
                \Log::warning('Falha ao enviar email SubscriptionCancelled', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Assinatura cancelada com sucesso.',
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('BillingController::cancel erro inesperado', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar assinatura. Tente novamente.',
            ], 500);
        }
    }

    private function validaCPF(string $cpf): bool
    {
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += (int) $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ((int) $cpf[$t] !== $d) {
                return false;
            }
        }
        return true;
    }

    private function validaCNPJ(string $cnpj): bool
    {
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }
        $pesos1 = [5,4,3,2,9,8,7,6,5,4,3,2];
        $pesos2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
        $soma = 0;
        for ($i = 0; $i < 12; $i++) {
            $soma += (int) $cnpj[$i] * $pesos1[$i];
        }
        $resto = $soma % 11;
        if ((int) $cnpj[12] !== ($resto < 2 ? 0 : 11 - $resto)) {
            return false;
        }
        $soma = 0;
        for ($i = 0; $i < 13; $i++) {
            $soma += (int) $cnpj[$i] * $pesos2[$i];
        }
        $resto = $soma % 11;
        if ((int) $cnpj[13] !== ($resto < 2 ? 0 : 11 - $resto)) {
            return false;
        }
        return true;
    }
}
