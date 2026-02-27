<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Mail\SubscriptionActivated;
use App\Mail\SubscriptionCancelled;
use App\Models\PlanDefinition;
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
        $tenant = auth()->user()->tenant;
        $plan   = PlanDefinition::where('name', $tenant->plan)->first();
        $plans  = PlanDefinition::where('is_active', true)->orderBy('price_monthly')->get();

        return view('tenant.settings.billing', compact('tenant', 'plan', 'plans'));
    }

    public function showCheckout(): View|RedirectResponse
    {
        $tenant = auth()->user()->tenant;

        if ($tenant->hasActiveSubscription()) {
            return redirect()->route('dashboard');
        }

        $plan  = PlanDefinition::where('name', $tenant->plan)->first();
        $plans = PlanDefinition::where('is_active', true)
            ->where('price_monthly', '>', 0)
            ->orderBy('price_monthly')
            ->get();

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

        $tenant = auth()->user()->tenant;
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

            $tenant->update([
                'plan'                  => $plan->name,
                'asaas_subscription_id' => $subscription['id'],
                'subscription_status'   => $subscriptionStatus,
                'status'                => $subscriptionStatus === 'active' ? 'active' : $tenant->status,
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
        $tenant = auth()->user()->tenant;
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
}
