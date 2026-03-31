<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\PartnerCommission;
use App\Models\PartnerWithdrawal;
use App\Models\User;
use App\Notifications\PartnerNotification;
use App\Services\AsaasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartnerCommissionController extends Controller
{
    public function index(Request $request): View
    {
        $commissions = PartnerCommission::with(['partner:id,name', 'clientTenant:id,name'])
            ->orderByDesc('created_at')
            ->paginate(50);

        $withdrawals = PartnerWithdrawal::with('partner:id,name')
            ->orderByDesc('requested_at')
            ->paginate(30, ['*'], 'wd_page');

        $pendingWithdrawals = PartnerWithdrawal::where('status', 'pending')->count();

        return view('master.partner-commissions.index', compact('commissions', 'withdrawals', 'pendingWithdrawals'));
    }

    public function approveWithdrawal(PartnerWithdrawal $withdrawal): JsonResponse
    {
        if ($withdrawal->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Saque não está pendente.'], 422);
        }

        try {
            $asaas = app(AsaasService::class);

            // Check balance
            $balance = $asaas->getBalance();
            if (($balance['balance'] ?? 0) < $withdrawal->amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saldo insuficiente no Asaas. Disponível: R$ ' . number_format($balance['balance'] ?? 0, 2, ',', '.'),
                ], 422);
            }

            // Create PIX transfer
            $transfer = $asaas->createPixTransfer([
                'value'             => (float) $withdrawal->amount,
                'pixAddressKey'     => $withdrawal->pix_key,
                'pixAddressKeyType' => $withdrawal->pix_key_type,
                'description'       => 'Comissão parceiro - Syncro #' . $withdrawal->id,
                'externalReference' => 'withdrawal:' . $withdrawal->id,
            ]);

            $withdrawal->update([
                'status'            => 'processing',
                'approved_at'       => now(),
                'asaas_transfer_id' => $transfer['id'] ?? null,
            ]);

            // Mark commissions as withdrawn
            PartnerCommission::where('tenant_id', $withdrawal->tenant_id)
                ->where('status', 'available')
                ->orderBy('created_at')
                ->limit(100) // safety
                ->update(['status' => 'withdrawn']);

            $this->notifyPartner($withdrawal->tenant_id, 'Saque aprovado!', 'Sua transferência PIX de R$ ' . number_format((float) $withdrawal->amount, 2, ',', '.') . ' está sendo processada.');

            return response()->json(['success' => true, 'message' => 'Saque aprovado e transferência PIX iniciada.']);
        } catch (\Throwable $e) {
            \Log::error('Withdrawal approval failed', ['id' => $withdrawal->id, 'error' => $e->getMessage()]);

            $withdrawal->update(['status' => 'approved', 'approved_at' => now()]);
            $this->notifyPartner($withdrawal->tenant_id, 'Saque aprovado!', 'Seu saque de R$ ' . number_format((float) $withdrawal->amount, 2, ',', '.') . ' foi aprovado.');

            return response()->json(['success' => true, 'message' => 'Saque aprovado. Transferência PIX será processada manualmente.']);
        }
    }

    public function rejectWithdrawal(Request $request, PartnerWithdrawal $withdrawal): JsonResponse
    {
        if ($withdrawal->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Saque não está pendente.'], 422);
        }

        $withdrawal->update([
            'status'          => 'rejected',
            'rejected_reason' => $request->input('reason', 'Rejeitado pelo administrador'),
        ]);

        $this->notifyPartner($withdrawal->tenant_id, 'Saque rejeitado', 'Seu saque de R$ ' . number_format((float) $withdrawal->amount, 2, ',', '.') . ' foi rejeitado. Motivo: ' . ($request->input('reason') ?: 'Não informado'));

        return response()->json(['success' => true]);
    }

    public function markPaid(PartnerWithdrawal $withdrawal): JsonResponse
    {
        if (!in_array($withdrawal->status, ['approved', 'processing'])) {
            return response()->json(['success' => false, 'message' => 'Saque não está aprovado.'], 422);
        }

        $withdrawal->update(['status' => 'paid', 'paid_at' => now()]);

        $this->notifyPartner($withdrawal->tenant_id, 'Saque pago!', 'Sua transferência PIX de R$ ' . number_format((float) $withdrawal->amount, 2, ',', '.') . ' foi realizada com sucesso.');

        return response()->json(['success' => true]);
    }

    private function notifyPartner(int $tenantId, string $title, string $body): void
    {
        $admin = User::where('tenant_id', $tenantId)->where('role', 'admin')->first();
        if ($admin) {
            $admin->notify(new PartnerNotification($title, $body));
        }
    }
}
