<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PartnerAgencyCode;
use App\Models\PartnerCommission;
use App\Models\Tenant;
use App\Notifications\PartnerNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AgencyAccessController extends Controller
{
    /** Entra na conta de um cliente (cria sessão de impersonação) */
    public function enter(Request $request, Tenant $tenant): RedirectResponse
    {
        $agency = auth()->user()->tenant;

        if (!$agency->isPartner()) {
            abort(403, 'Acesso restrito a agências parceiras.');
        }

        if ((int) $tenant->referred_by_agency_id !== (int) $agency->id) {
            abort(403, 'Este cliente não está vinculado à sua agência.');
        }

        session(['impersonating_tenant_id' => $tenant->id]);

        return redirect()->route('dashboard')
            ->with('success', "Acessando a conta de {$tenant->name}.");
    }

    /** Sai da impersonação e volta para a própria conta */
    public function exit(Request $request): RedirectResponse
    {
        session()->forget('impersonating_tenant_id');

        return redirect()->route('dashboard')
            ->with('success', 'Voltou para sua própria conta.');
    }

    /** Lista os clientes indicados pela agência */
    public function clients(): View
    {
        $agency  = auth()->user()->tenant;

        if (!$agency->isPartner()) {
            abort(403, 'Acesso restrito a agências parceiras.');
        }

        $clients = Tenant::withoutGlobalScope('tenant')
            ->where('referred_by_agency_id', $agency->id)
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'plan', 'status', 'created_at']);

        $agencyCode = PartnerAgencyCode::where('tenant_id', $agency->id)->first();

        return view('tenant.agency.clients', compact('clients', 'agency', 'agencyCode'));
    }

    /** Permite usuário existente vincular um código de agência à sua conta */
    public function linkCode(Request $request): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        if ($tenant->referred_by_agency_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Sua conta já está vinculada a uma agência parceira. Desvincule primeiro.',
            ], 422);
        }

        $data = $request->validate([
            'agency_code' => 'required|string|max:20',
        ]);

        $agencyCode = PartnerAgencyCode::where('code', strtoupper($data['agency_code']))
            ->where('is_active', true)
            ->whereNotNull('tenant_id')
            ->first();

        if (!$agencyCode) {
            return response()->json([
                'success' => false,
                'message' => 'Código inválido, inativo ou não encontrado.',
            ], 422);
        }

        $tenant->update([
            'referred_by_agency_id'  => $agencyCode->tenant_id,
            'partner_commission_pct' => $this->getLockedCommissionPct($agencyCode->tenant_id),
        ]);

        // Notificar parceiro sobre novo cliente
        $this->notifyPartnerLinked($agencyCode->tenant_id, $tenant);

        return response()->json([
            'success' => true,
            'message' => 'Agência parceira vinculada com sucesso!',
        ]);
    }

    /** Desvincula o tenant do parceiro atual */
    public function unlinkPartner(Request $request): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        if ($tenant->referred_by_agency_id === null) {
            return response()->json([
                'success' => false,
                'message' => 'Sua conta não está vinculada a nenhuma agência parceira.',
            ], 422);
        }

        $oldPartnerId   = $tenant->referred_by_agency_id;
        $oldPartner     = Tenant::withoutGlobalScope('tenant')->find($oldPartnerId);
        $oldPartnerName = $oldPartner?->name ?? 'Parceiro';

        // Cancelar comissões pendentes (grace period — parceiro perdeu o cliente)
        $cancelledCount = $this->cancelPendingCommissions($oldPartnerId, $tenant->id);

        // Desvincular
        $tenant->update(['referred_by_agency_id' => null]);

        // Notificar parceiro antigo
        $this->notifyPartnerUnlinked($oldPartnerId, $tenant);

        Log::info('Tenant desvinculou do parceiro', [
            'tenant_id'          => $tenant->id,
            'old_partner_id'     => $oldPartnerId,
            'cancelled_commissions' => $cancelledCount,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Desvinculado da agência {$oldPartnerName}. {$cancelledCount} comissão(ões) pendente(s) cancelada(s).",
        ]);
    }

    /** Troca o parceiro: desvincula do antigo e vincula ao novo */
    public function switchPartner(Request $request): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        $data = $request->validate([
            'agency_code' => 'required|string|max:20',
        ]);

        $agencyCode = PartnerAgencyCode::where('code', strtoupper($data['agency_code']))
            ->where('is_active', true)
            ->whereNotNull('tenant_id')
            ->first();

        if (!$agencyCode) {
            return response()->json([
                'success' => false,
                'message' => 'Código inválido, inativo ou não encontrado.',
            ], 422);
        }

        // Não pode trocar pelo mesmo
        if ($tenant->referred_by_agency_id === $agencyCode->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'Você já está vinculado a esta agência.',
            ], 422);
        }

        $oldPartnerId   = $tenant->referred_by_agency_id;
        $newPartnerId   = $agencyCode->tenant_id;
        $cancelledCount = 0;

        // Cancelar comissões pendentes do parceiro antigo
        if ($oldPartnerId) {
            $cancelledCount = $this->cancelPendingCommissions($oldPartnerId, $tenant->id);
            $this->notifyPartnerUnlinked($oldPartnerId, $tenant);
        }

        // Vincular ao novo com % travada
        $tenant->update([
            'referred_by_agency_id'  => $newPartnerId,
            'partner_commission_pct' => $this->getLockedCommissionPct($newPartnerId),
        ]);
        $this->notifyPartnerLinked($newPartnerId, $tenant);

        $newPartner = Tenant::withoutGlobalScope('tenant')->find($newPartnerId);

        Log::info('Tenant trocou de parceiro', [
            'tenant_id'      => $tenant->id,
            'old_partner_id' => $oldPartnerId,
            'new_partner_id' => $newPartnerId,
            'cancelled_commissions' => $cancelledCount,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Vinculado à agência {$newPartner->name}.",
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Cancela comissões pending do parceiro para este cliente */
    /** Calculate commission % based on partner's current rank */
    private function getLockedCommissionPct(int $partnerTenantId): ?float
    {
        $activeClients = Tenant::withoutGlobalScope('tenant')
            ->where('referred_by_agency_id', $partnerTenantId)
            ->whereIn('status', ['active', 'partner', 'trial'])
            ->count();

        $rank = \App\Models\PartnerRank::forSalesCount($activeClients);

        return $rank?->commission_pct ? (float) $rank->commission_pct : null;
    }

    private function cancelPendingCommissions(int $partnerId, int $clientTenantId): int
    {
        return PartnerCommission::where('tenant_id', $partnerId)
            ->where('client_tenant_id', $clientTenantId)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
    }

    /** Notifica parceiro que perdeu um cliente */
    private function notifyPartnerUnlinked(int $partnerId, Tenant $client): void
    {
        try {
            $partnerAdmin = \App\Models\User::where('tenant_id', $partnerId)
                ->where('role', 'admin')
                ->first();

            if ($partnerAdmin) {
                $partnerAdmin->notify(new PartnerNotification(
                    "O cliente {$client->name} se desvinculou da sua agência.",
                    ['type' => 'client_unlinked', 'client_name' => $client->name, 'client_id' => $client->id]
                ));

                Mail::send('emails.partner-client-unlinked', [
                    'partnerName' => $partnerAdmin->name,
                    'clientName'  => $client->name,
                ], function ($msg) use ($partnerAdmin, $client) {
                    $msg->to($partnerAdmin->email)
                        ->subject("Cliente {$client->name} se desvinculou da sua agência — Syncro");
                });
            }
        } catch (\Throwable $e) {
            Log::warning('Falha ao notificar parceiro sobre desvinculação', ['error' => $e->getMessage()]);
        }
    }

    /** Notifica parceiro sobre novo cliente vinculado */
    private function notifyPartnerLinked(int $partnerId, Tenant $client): void
    {
        try {
            $partnerAdmin = \App\Models\User::where('tenant_id', $partnerId)
                ->where('role', 'admin')
                ->first();

            if ($partnerAdmin) {
                $partnerAdmin->notify(new PartnerNotification(
                    "Novo cliente vinculado: {$client->name}.",
                    ['type' => 'client_linked', 'client_name' => $client->name, 'client_id' => $client->id]
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('Falha ao notificar parceiro sobre vinculação', ['error' => $e->getMessage()]);
        }
    }
}
