<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\PartnerAgencyCode;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PartnerAgencyCodeController extends Controller
{
    public function index(): View
    {
        $codes    = PartnerAgencyCode::with('tenant')->orderByDesc('created_at')->get();
        $partners = Tenant::where('plan', 'partner')
            ->orWhere('status', 'partner')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('master.partner-agency-codes.index', compact('codes', 'partners'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'        => 'required|string|max:20|unique:partner_agency_codes,code|regex:/^[A-Z0-9\-]+$/',
            'description' => 'nullable|string|max:100',
            'is_active'   => 'nullable|boolean',
        ], [
            'code.unique' => 'Este código já está em uso.',
            'code.regex'  => 'O código deve conter apenas letras maiúsculas, números e hífens.',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $code = PartnerAgencyCode::create($data);

        return response()->json(['success' => true, 'code' => $code]);
    }

    public function generate(Request $request): JsonResponse
    {
        do {
            $generated = 'AGC-' . strtoupper(Str::random(6));
        } while (PartnerAgencyCode::where('code', $generated)->exists());

        return response()->json(['code' => $generated]);
    }

    public function update(Request $request, PartnerAgencyCode $partnerAgencyCode): JsonResponse
    {
        $data = $request->validate([
            'code'        => 'nullable|string|max:20|regex:/^[A-Z0-9\-]+$/|unique:partner_agency_codes,code,' . $partnerAgencyCode->id,
            'description' => 'nullable|string|max:100',
            'is_active'   => 'nullable|boolean',
            'tenant_id'   => 'nullable|integer|exists:tenants,id',
        ], [
            'code.unique' => 'Este código já está em uso.',
            'code.regex'  => 'O código deve conter apenas letras maiúsculas, números e hífens.',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        // Validate that assigned tenant is actually a partner
        if (!empty($data['tenant_id'])) {
            $tenant = Tenant::find($data['tenant_id']);
            if (!$tenant || !$tenant->isPartner()) {
                return response()->json([
                    'success' => false,
                    'message' => 'O tenant selecionado não é um parceiro.',
                ], 422);
            }
            // Ensure no other code is already linked to this tenant
            $existing = PartnerAgencyCode::where('tenant_id', $data['tenant_id'])
                ->where('id', '!=', $partnerAgencyCode->id)
                ->first();
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => "Este parceiro já possui o código {$existing->code} vinculado.",
                ], 422);
            }
        }

        $partnerAgencyCode->update($data);

        return response()->json(['success' => true, 'code' => $partnerAgencyCode->fresh()->load('tenant')]);
    }

    public function destroy(PartnerAgencyCode $partnerAgencyCode): JsonResponse
    {
        if ($partnerAgencyCode->tenant_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir: este código já foi usado por uma agência.',
            ], 422);
        }

        $partnerAgencyCode->delete();

        return response()->json(['success' => true]);
    }
}
