<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Mail\ReengagementEmail;
use App\Models\ReengagementTemplate;
use App\Models\Tenant;
use App\Models\User;
use App\Services\WahaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ReengagementController extends Controller
{
    use Traits\ChecksMasterPermission;

    public function index(): View
    {
        $this->authorizeModule('system');

        $templates = ReengagementTemplate::all()->groupBy('stage');
        $variables = ReengagementTemplate::availableVariables();

        return view('master.reengagement.index', compact('templates', 'variables'));
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizeModule('system');

        $data = $request->validate([
            'templates'              => 'required|array',
            'templates.*.id'         => 'required|exists:reengagement_templates,id',
            'templates.*.subject'    => 'nullable|string|max:200',
            'templates.*.body'       => 'required|string|max:5000',
            'templates.*.is_active'  => 'required|boolean',
        ]);

        foreach ($data['templates'] as $t) {
            ReengagementTemplate::where('id', $t['id'])->update([
                'subject'   => $t['subject'] ?? null,
                'body'      => $t['body'],
                'is_active' => $t['is_active'],
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Templates atualizados!']);
    }

    /**
     * Preview email template in browser.
     */
    public function preview(Request $request)
    {
        $stage = $request->input('stage', '7d');

        $template = ReengagementTemplate::where('stage', $stage)
            ->where('channel', 'email')
            ->first();

        if (!$template) {
            return 'Template não encontrado. Execute o seeder primeiro.';
        }

        $mockVars = [
            '{{nome}}'               => 'Matheus',
            '{{empresa}}'            => 'Syncro Demo',
            '{{dias_sem_login}}'     => $stage === '7d' ? 8 : ($stage === '14d' ? 15 : 32),
            '{{leads_total}}'        => 47,
            '{{leads_sem_contato}}'  => 12,
            '{{leads_novos_semana}}' => 5,
            '{{conversas_abertas}}'  => 8,
            '{{tarefas_pendentes}}'  => 3,
            '{{vendas_mes}}'         => 6,
            '{{link_crm}}'           => 'https://app.syncro.chat',
            '{{link_leads}}'         => 'https://app.syncro.chat/contatos',
            '{{link_chats}}'         => 'https://app.syncro.chat/chats',
        ];

        $mockUser   = new User(['name' => 'Matheus', 'email' => 'teste@syncro.chat']);
        $mockTenant = new Tenant(['name' => 'Syncro Demo']);

        $mailable = new ReengagementEmail($mockUser, $mockTenant, $template, $mockVars);

        return $mailable->render();
    }

    public function sendTest(Request $request): JsonResponse
    {
        $this->authorizeModule('system');

        $data = $request->validate([
            'stage'   => 'required|in:7d,14d,30d',
            'channel' => 'required|in:email,whatsapp',
            'target'  => 'required|string|max:50',
        ]);

        if ($data['channel'] === 'email') {
            Artisan::call('users:send-reengagement', [
                '--test-email' => $data['target'],
                '--test-stage' => $data['stage'],
            ]);
        } else {
            Artisan::call('users:send-reengagement', [
                '--test-phone' => $data['target'],
                '--test-stage' => $data['stage'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $data['channel'] === 'email'
                ? "Teste enviado para {$data['target']}"
                : "WhatsApp enviado para {$data['target']}",
        ]);
    }
}
