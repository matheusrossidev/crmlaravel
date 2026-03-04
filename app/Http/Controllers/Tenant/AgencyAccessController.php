<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PartnerAgencyCode;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                'message' => 'Sua conta já está vinculada a uma agência parceira.',
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

        $tenant->update(['referred_by_agency_id' => $agencyCode->tenant_id]);

        return response()->json([
            'success' => true,
            'message' => 'Agência parceira vinculada com sucesso!',
        ]);
    }
}
