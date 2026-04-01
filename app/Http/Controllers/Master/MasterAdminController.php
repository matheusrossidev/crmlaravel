<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MasterAdminController extends Controller
{
    public const AVAILABLE_MODULES = [
        'dashboard'           => ['label' => 'Dashboard',             'icon' => 'speedometer2',       'group' => 'Geral'],
        'tenants'             => ['label' => 'Empresas (ver)',        'icon' => 'buildings',          'group' => 'Gestão'],
        'tenants.create'      => ['label' => 'Criar empresas',       'icon' => 'building-add',       'group' => 'Gestão'],
        'tenants.edit'        => ['label' => 'Editar empresas',      'icon' => 'pencil-square',      'group' => 'Gestão'],
        'tenants.delete'      => ['label' => 'Excluir empresas',     'icon' => 'trash3',             'group' => 'Gestão'],
        'tenants.users'       => ['label' => 'Gerenciar usuários',   'icon' => 'people',             'group' => 'Gestão'],
        'plans'               => ['label' => 'Planos',               'icon' => 'card-list',          'group' => 'Gestão'],
        'payments'            => ['label' => 'Recebimentos',         'icon' => 'cash-stack',         'group' => 'Gestão'],
        'agency_codes'        => ['label' => 'Códigos de Agência',   'icon' => 'key',                'group' => 'Parceiros'],
        'partner_ranks'       => ['label' => 'Ranks de Parceiro',    'icon' => 'trophy',             'group' => 'Parceiros'],
        'partner_resources'   => ['label' => 'Recursos Parceiro',    'icon' => 'journal-text',       'group' => 'Parceiros'],
        'partner_courses'     => ['label' => 'Cursos Parceiro',      'icon' => 'mortarboard',        'group' => 'Parceiros'],
        'partner_commissions' => ['label' => 'Comissões / Saques',   'icon' => 'wallet2',            'group' => 'Parceiros'],
        'token_increments'    => ['label' => 'Pacotes de Tokens',    'icon' => 'lightning-charge',   'group' => 'Gestão'],
        'upsell'              => ['label' => 'Upsell Triggers',      'icon' => 'graph-up-arrow',     'group' => 'Gestão'],
        'usage'               => ['label' => 'Uso / Tokens',         'icon' => 'bar-chart',          'group' => 'Monitoramento'],
        'logs'                => ['label' => 'Logs',                  'icon' => 'journal-code',       'group' => 'Monitoramento'],
        'system'              => ['label' => 'Sistema',               'icon' => 'cpu',                'group' => 'Monitoramento'],
        'toolbox'             => ['label' => 'Ferramentas',           'icon' => 'tools',              'group' => 'Monitoramento'],
        'notifications'       => ['label' => 'Notificações',         'icon' => 'bell',               'group' => 'Geral'],
        'feedbacks'           => ['label' => 'Feedbacks',             'icon' => 'chat-square-text',   'group' => 'Geral'],
    ];

    public function index(): View
    {
        $this->ensureOwner();

        $admins = User::where('is_super_admin', true)
            ->orderByRaw('master_permissions IS NOT NULL')  // owner first
            ->orderBy('name')
            ->get();

        return view('master.admins.index', [
            'admins'           => $admins,
            'availableModules' => self::AVAILABLE_MODULES,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureOwner();

        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'modules'  => 'required|array|min:1',
            'modules.*'=> ['string', Rule::in(array_keys(self::AVAILABLE_MODULES))],
        ]);

        $user = User::create([
            'name'               => $data['name'],
            'email'              => $data['email'],
            'password'           => $data['password'],
            'role'               => 'super_admin',
            'is_super_admin'     => true,
            'master_permissions' => ['modules' => $data['modules']],
            'email_verified_at'  => now(),
        ]);

        // Enviar email com credenciais
        try {
            Mail::raw(
                "Olá {$data['name']},\n\nSua conta de administrador foi criada no Syncro.\n\nEmail: {$data['email']}\nSenha: {$data['password']}\n\nVocê precisará configurar a autenticação em dois fatores (2FA) no primeiro acesso.\n\nAcesse: " . url('/login') . "\n\nEquipe Syncro",
                function ($msg) use ($data) {
                    $msg->to($data['email'])->subject('Conta de administrador criada — Syncro');
                }
            );
        } catch (\Throwable $e) {
            \Log::warning('Falha ao enviar email para novo sub-master', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => "Administrador {$data['name']} criado com sucesso.",
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->ensureOwner();

        // Não pode editar o owner
        if ($user->isOwnerAdmin()) {
            return response()->json(['success' => false, 'message' => 'Não é possível editar o administrador principal.'], 422);
        }

        $data = $request->validate([
            'name'     => 'sometimes|string|max:100',
            'email'    => "sometimes|email|unique:users,email,{$user->id}",
            'password' => 'nullable|string|min:8',
            'modules'  => 'required|array|min:1',
            'modules.*'=> ['string', Rule::in(array_keys(self::AVAILABLE_MODULES))],
            'is_active' => 'sometimes|boolean',
        ]);

        $update = [
            'master_permissions' => ['modules' => $data['modules']],
        ];

        if (isset($data['name']))  $update['name']  = $data['name'];
        if (isset($data['email'])) $update['email'] = $data['email'];
        if (!empty($data['password'])) $update['password'] = $data['password'];

        // Desativar = remover is_super_admin
        if (isset($data['is_active']) && !$data['is_active']) {
            $update['is_super_admin'] = false;
        } elseif (isset($data['is_active']) && $data['is_active']) {
            $update['is_super_admin'] = true;
        }

        $user->update($update);

        return response()->json([
            'success' => true,
            'message' => "Administrador {$user->name} atualizado.",
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->ensureOwner();

        if ($user->isOwnerAdmin()) {
            return response()->json(['success' => false, 'message' => 'Não é possível excluir o administrador principal.'], 422);
        }

        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Você não pode excluir a si mesmo.'], 422);
        }

        $user->update(['is_super_admin' => false, 'master_permissions' => null]);

        return response()->json([
            'success' => true,
            'message' => "Acesso master removido de {$user->name}.",
        ]);
    }

    private function ensureOwner(): void
    {
        if (! auth()->user()->isOwnerAdmin()) {
            abort(403, 'Apenas o administrador principal pode gerenciar outros administradores.');
        }
    }
}
