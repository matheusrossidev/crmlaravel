<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantTokenIncrement;
use App\Models\TokenIncrementPlan;
use App\Services\AsaasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenIncrementController extends Controller
{
    public function purchase(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id'  => 'required|exists:token_increment_plans,id',
            'cpf_cnpj' => 'nullable|string|max:18',
            'email'    => 'nullable|email|max:100',
        ]);

        $plan   = TokenIncrementPlan::where('id', $data['plan_id'])->where('is_active', true)->firstOrFail();
        $tenant = auth()->user()->tenant;

        try {
            // Se não tem customer no Asaas, criar on-the-fly
            if (!$tenant->asaas_customer_id) {
                $request->validate([
                    'cpf_cnpj' => 'required|string|max:18',
                    'email'    => 'required|email|max:100',
                ]);

                $asaas    = app(AsaasService::class);
                $customer = $asaas->createCustomer([
                    'name'    => $tenant->name,
                    'cpfCnpj' => preg_replace('/\D/', '', $data['cpf_cnpj']),
                    'email'   => $data['email'],
                ]);
                $tenant->update(['asaas_customer_id' => $customer['id']]);
            }
            // Cria registro pending
            $increment = TenantTokenIncrement::create([
                'tenant_id'               => $tenant->id,
                'token_increment_plan_id' => $plan->id,
                'tokens_added'            => $plan->tokens_amount,
                'price_paid'              => $plan->price,
                'status'                  => 'pending',
            ]);

            // Cobrar via Asaas (PIX)
            $asaas   = app(AsaasService::class);
            $payment = $asaas->createPayment([
                'customer'          => $tenant->asaas_customer_id,
                'billingType'       => 'PIX',
                'value'             => (float) $plan->price,
                'dueDate'           => now()->addDays(1)->toDateString(),
                'description'       => "Incremento de tokens: {$plan->display_name}",
                'externalReference' => "token_increment:{$increment->id}",
            ]);

            $increment->update(['asaas_payment_id' => $payment['id'] ?? null]);

            return response()->json([
                'success'     => true,
                'payment_id'  => $payment['id'] ?? null,
                'invoice_url' => $payment['invoiceUrl'] ?? null,
                'pix_code'    => $payment['pixQrCodeImage'] ?? null,
                'pix_key'     => $payment['pixCopiaECola'] ?? null,
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('TokenIncrementController::purchase erro inesperado', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pagamento. Tente novamente.',
            ], 500);
        }
    }
}
