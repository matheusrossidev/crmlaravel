<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyAgencyEmail;
use App\Models\PartnerAgencyCode;
use App\Models\PlanDefinition;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AgencyRegisterController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $code = $request->query('code', '');

        if ($code) {
            $agencyCode = PartnerAgencyCode::where('code', strtoupper($code))
                ->where('is_active', true)
                ->whereNull('tenant_id')
                ->first();

            if (!$agencyCode) {
                return redirect()->route('agency.register')
                    ->withErrors(['code' => 'Código inválido, inativo ou já utilizado.']);
            }
        }

        return view('auth.register-agency', ['prefilledCode' => strtoupper($code)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'agency_code'  => 'required|string|max:20',
            'tenant_name'  => 'required|string|max:255',
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|string|min:8|confirmed',
        ], [
            'agency_code.required' => 'O código de agência parceira é obrigatório.',
            'tenant_name.required' => 'Informe o nome da agência.',
            'name.required'        => 'Informe seu nome.',
            'email.required'       => 'Informe seu e-mail.',
            'email.email'          => 'Informe um e-mail válido.',
            'email.unique'         => 'Este e-mail já está cadastrado.',
            'password.required'    => 'Crie uma senha.',
            'password.min'         => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed'   => 'As senhas não conferem.',
        ]);

        $agencyCode = PartnerAgencyCode::where('code', strtoupper($data['agency_code']))
            ->where('is_active', true)
            ->whereNull('tenant_id')
            ->first();

        if (!$agencyCode) {
            return back()->withInput()->withErrors([
                'agency_code' => 'Código inválido, inativo ou já utilizado.',
            ]);
        }

        $token = Str::random(64);

        $tenant = Tenant::create([
            'name'   => $data['tenant_name'],
            'slug'   => Str::slug($data['tenant_name']) . '-' . Str::random(4),
            'plan'   => 'partner',
            'status' => 'partner',
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

        // Vincular código à agência
        $agencyCode->update(['tenant_id' => $tenant->id]);

        try {
            Mail::to($user->email)->send(new VerifyAgencyEmail($user, $tenant));
        } catch (\Throwable $e) {
            \Log::warning('Falha ao enviar email de verificação de agência', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }

        // Notifica grupo master via WhatsApp
        \App\Services\MasterWhatsappNotifier::newAgencyRegistration($tenant, $user, strtoupper($data['agency_code']));

        return redirect()->route('register.pending')
            ->with('email', $user->email);
    }
}
