<?php

declare(strict_types=1);

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerCommission;
use App\Models\PartnerWithdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerWithdrawalController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount'              => 'required|numeric|min:50',
            'pix_key'             => 'required|string|max:100',
            'pix_key_type'        => 'required|in:CPF,CNPJ,EMAIL,PHONE,EVP',
            'pix_holder_name'     => 'required|string|max:100',
            'pix_holder_cpf_cnpj' => 'required|string|max:20',
        ]);

        $tenantId = auth()->user()->tenant_id;

        // Check available balance
        $available = PartnerCommission::where('tenant_id', $tenantId)
            ->where('status', 'available')
            ->sum('amount');

        if ($data['amount'] > $available) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo insuficiente. Disponível: R$ ' . number_format((float) $available, 2, ',', '.'),
            ], 422);
        }

        // Check no pending withdrawal
        $pendingExists = PartnerWithdrawal::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'approved', 'processing'])
            ->exists();

        if ($pendingExists) {
            return response()->json([
                'success' => false,
                'message' => 'Você já tem um saque pendente. Aguarde a conclusão.',
            ], 422);
        }

        $withdrawal = PartnerWithdrawal::create([
            'tenant_id'           => $tenantId,
            'amount'              => $data['amount'],
            'status'              => 'pending',
            'pix_key'             => $data['pix_key'],
            'pix_key_type'        => $data['pix_key_type'],
            'pix_holder_name'     => $data['pix_holder_name'],
            'pix_holder_cpf_cnpj' => $data['pix_holder_cpf_cnpj'],
            'requested_at'        => now(),
        ]);

        return response()->json(['success' => true, 'withdrawal' => $withdrawal]);
    }
}
