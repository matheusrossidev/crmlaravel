<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\WahaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ToolboxController extends Controller
{
    private const TOOLS = [
        'sync-group-names',
        'clear-leads',
        'clear-cache',
        'fix-unread-counts',
        'reset-password',
        'wa-status',
        'close-conversations',
        'export-tenant-stats',
    ];

    public function index(): View
    {
        $tenants = Tenant::orderBy('name')->get(['id', 'name']);
        $users   = User::whereNotNull('tenant_id')->orderBy('name')->get(['id', 'tenant_id', 'name', 'email']);

        return view('master.toolbox.index', compact('tenants', 'users'));
    }

    public function run(Request $request, string $tool): JsonResponse
    {
        if (! in_array($tool, self::TOOLS, true)) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Ferramenta inválida.']], 404);
        }

        $method = lcfirst(str_replace('-', '', ucwords($tool, '-')));

        return $this->{$method}($request);
    }

    // ── Tools ─────────────────────────────────────────────────────────────────

    private function syncGroupNames(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');
        $forceAll = (bool) $request->input('all', false);
        $lines    = [];

        $instance = WhatsappInstance::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('status', 'connected')
            ->first();

        if (! $instance) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Nenhuma instância WhatsApp conectada encontrada.']]);
        }

        $query = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('is_group', true)
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId));

        if (! $forceAll) {
            $query->where(function ($q) {
                $q->whereNull('contact_name')->orWhere('contact_name', '');
            });
        }

        $conversations = $query->get();

        if ($conversations->isEmpty()) {
            return response()->json(['success' => true, 'lines' => ['Nenhum grupo para atualizar.']]);
        }

        $lines[] = "Encontrados {$conversations->count()} grupo(s). Iniciando sincronização...";

        $waha    = new WahaService($instance->session_name);
        $updated = 0;
        $errors  = 0;

        foreach ($conversations as $conv) {
            try {
                $jid  = str_contains($conv->phone, '@') ? $conv->phone : $conv->phone . '@g.us';
                $info = $waha->getGroupInfo($jid);
                $name = $info['subject'] ?? $info['name'] ?? null;

                if ($name) {
                    $conv->update(['contact_name' => $name]);
                    $lines[] = "  ✓ {$conv->phone} → {$name}";
                    $updated++;
                } else {
                    $lines[] = "  ? {$conv->phone} — sem nome na resposta";
                }
            } catch (\Throwable $e) {
                $lines[] = "[ERRO]   ✗ {$conv->phone} — {$e->getMessage()}";
                $errors++;
            }
        }

        $lines[] = "Concluído: {$updated} atualizado(s), {$errors} erro(s).";

        return response()->json(['success' => $errors === 0, 'lines' => $lines]);
    }

    private function clearLeads(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');
        $confirm  = $request->input('confirm', '');

        if (strtoupper(trim($confirm)) !== 'CONFIRMAR') {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Confirmação inválida. Digite CONFIRMAR no campo.']]);
        }

        if (! $tenantId) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Selecione um tenant.']]);
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Tenant não encontrado.']]);
        }

        $count = Lead::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->count();

        Lead::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->delete();

        return response()->json([
            'success' => true,
            'lines'   => [
                "Tenant: {$tenant->name}",
                "{$count} lead(s) removido(s) com sucesso.",
            ],
        ]);
    }

    private function clearCache(Request $request): JsonResponse
    {
        $lines = [];

        $commands = ['cache:clear', 'config:clear', 'route:clear', 'view:clear'];

        foreach ($commands as $cmd) {
            try {
                Artisan::call($cmd);
                $output = trim(Artisan::output()) ?: 'OK';
                $lines[] = "  ✓ php artisan {$cmd} — {$output}";
            } catch (\Throwable $e) {
                $lines[] = "[ERRO]   ✗ php artisan {$cmd} — {$e->getMessage()}";
            }
        }

        $lines[] = 'Cache limpo com sucesso.';

        return response()->json(['success' => true, 'lines' => $lines]);
    }

    private function fixUnreadCounts(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');
        $lines    = [];

        $query = WhatsappConversation::withoutGlobalScope('tenant')
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId));

        $total   = $query->count();
        $updated = 0;

        // Reset all unread counts to 0 (manual correction tool)
        $affected = (clone $query)->where('unread_count', '>', 0)->count();

        (clone $query)->where('unread_count', '>', 0)->update(['unread_count' => 0]);

        $lines[] = "Total de conversas: {$total}";
        $lines[] = "Conversas com não-lidos resetados: {$affected}";
        $lines[] = 'Contadores de não-lidos zerados com sucesso.';
        $updated = $affected;

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            $lines  = array_merge(["Tenant: {$tenant?->name}"], $lines);
        }

        return response()->json(['success' => true, 'lines' => $lines]);
    }

    private function resetPassword(Request $request): JsonResponse
    {
        $userId      = $request->input('user_id');
        $newPassword = $request->input('new_password', '');

        if (! $userId) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Selecione um usuário.']]);
        }

        if (strlen($newPassword) < 6) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] A nova senha deve ter pelo menos 6 caracteres.']]);
        }

        $user = User::find($userId);

        if (! $user) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Usuário não encontrado.']]);
        }

        $user->update(['password' => Hash::make($newPassword)]);

        return response()->json([
            'success' => true,
            'lines'   => [
                "Usuário: {$user->name} ({$user->email})",
                'Senha redefinida com sucesso.',
            ],
        ]);
    }

    private function waStatus(Request $request): JsonResponse
    {
        $instances = WhatsappInstance::withoutGlobalScope('tenant')
            ->with('tenant:id,name')
            ->orderBy('tenant_id')
            ->get();

        if ($instances->isEmpty()) {
            return response()->json(['success' => true, 'lines' => ['Nenhuma instância WhatsApp cadastrada.']]);
        }

        $lines = ["Total: {$instances->count()} instância(s)", ''];

        foreach ($instances as $inst) {
            $status  = strtoupper((string) $inst->status);
            $tenant  = $inst->tenant?->name ?? "Tenant #{$inst->tenant_id}";
            $emoji   = match ($inst->status) {
                'connected'    => '🟢',
                'disconnected' => '🔴',
                default        => '🟡',
            };
            $lines[] = "  {$emoji} [{$status}] {$inst->session_name}  —  {$tenant}";
        }

        return response()->json(['success' => true, 'lines' => $lines]);
    }

    private function closeConversations(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');

        if (! $tenantId) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Selecione um tenant.']]);
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Tenant não encontrado.']]);
        }

        $count = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->count();

        WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->update(['status' => 'closed', 'closed_at' => now()]);

        return response()->json([
            'success' => true,
            'lines'   => [
                "Tenant: {$tenant->name}",
                "{$count} conversa(s) abertas fechadas com sucesso.",
            ],
        ]);
    }

    private function exportTenantStats(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');

        if (! $tenantId) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Selecione um tenant.']]);
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Tenant não encontrado.']]);
        }

        $leads     = Lead::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->count();
        $convs     = WhatsappConversation::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->count();
        $convOpen  = WhatsappConversation::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->where('status', 'open')->count();
        $messages  = WhatsappMessage::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->count();
        $users     = User::where('tenant_id', $tenantId)->count();
        $groups    = WhatsappConversation::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->where('is_group', true)->count();

        $lines = [
            "=== Stats: {$tenant->name} ===",
            '',
            "  Leads / Contatos   : {$leads}",
            "  Usuários           : {$users}",
            '',
            "  Conversas (total)  : {$convs}",
            "  Conversas abertas  : {$convOpen}",
            "  Grupos             : {$groups}",
            "  Mensagens          : {$messages}",
            '',
            "  Plano              : {$tenant->plan}",
            "  Status             : {$tenant->status}",
            "  Trial ends at      : " . ($tenant->trial_ends_at?->format('d/m/Y') ?? 'N/A'),
        ];

        return response()->json(['success' => true, 'lines' => $lines]);
    }
}
