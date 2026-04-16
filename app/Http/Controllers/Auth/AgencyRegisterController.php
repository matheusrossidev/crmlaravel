<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyAgencyEmail;
use App\Models\PartnerAgencyCode;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AgencyRegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register-partner');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant_name'  => 'required|string|max:255',
            'cnpj'         => 'nullable|string|max:20',
            'name'         => 'required|string|max:255',
            'phone'        => 'required|string|min:10|max:20',
            'segment'      => 'required|string|max:50',
            'email'        => 'required|email:rfc,dns|unique:users,email',
            'website'      => 'nullable|string|max:191',
            'city'         => 'nullable|string|max:100',
            'state'        => 'nullable|string|max:2',
            'password'     => 'required|string|min:8|confirmed',
            'accept_terms' => 'accepted',
        ], [
            'email.email'  => __('auth.validation.email_dns'),
            'email.unique' => __('auth.validation.email_unique'),
        ]);

        $token = Str::random(64);

        // Auto-generate partner code from company name
        $baseName = Str::upper(Str::slug($data['tenant_name'], ''));
        $baseName = substr((string) preg_replace('/[^A-Z0-9]/', '', $baseName), 0, 15);
        if (!$baseName) $baseName = 'PARTNER';
        $code = $baseName . '-' . strtoupper(Str::random(4));
        while (PartnerAgencyCode::where('code', $code)->exists()) {
            $code = $baseName . '-' . strtoupper(Str::random(4));
        }

        $tenant = Tenant::create([
            'name'    => $data['tenant_name'],
            'slug'    => Str::slug($data['tenant_name']) . '-' . Str::random(4),
            'phone'   => preg_replace('/\D/', '', $data['phone']),
            'cnpj'    => $data['cnpj'] ? preg_replace('/\D/', '', $data['cnpj']) : null,
            'website' => $data['website'] ? (str_starts_with($data['website'], 'http') ? $data['website'] : 'https://' . $data['website']) : null,
            'city'    => $data['city'] ?: null,
            'state'   => $data['state'] ? strtoupper($data['state']) : null,
            'segment' => $data['segment'],
            'plan'    => 'partner',
            'status'  => 'pending_approval',
        ]);

        $user = User::create([
            'tenant_id'          => $tenant->id,
            'name'               => $data['name'],
            'email'              => $data['email'],
            'password'           => $data['password'],
            'role'               => 'admin',
            'email_verified_at'  => null,
            'verification_token' => $token,
        ]);

        // Create partner code (inactive until approved)
        PartnerAgencyCode::create([
            'code'      => $code,
            'tenant_id' => $tenant->id,
            'is_active' => false,
        ]);

        try {
            Mail::to($user->email)->send(new VerifyAgencyEmail($user, $tenant));
        } catch (\Throwable $e) {
            \Log::warning('Falha ao enviar email de verificação de agência', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }

        // Notify master group via WhatsApp
        \App\Services\MasterWhatsappNotifier::newAgencyRegistration($tenant, $user, $code);

        // Envia boas-vindas no WhatsApp pessoal do parceiro
        \App\Services\MasterWhatsappNotifier::welcomeUser($user, $tenant);

        return redirect()->route('register.pending')
            ->with('email', $user->email);
    }
}
