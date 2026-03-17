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
use App\Jobs\ImportWhatsappHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
        'check-user-account',
        'cleanup-lid-conversations',
        'reimport-wa-history',
        'sync-profile-pictures',
        'reimport-empty-conversations',
        'resolve-lid-conversations',
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

    private function checkUserAccount(Request $request): JsonResponse
    {
        $userId = $request->input('user_id');
        $resend = (bool) $request->input('resend_email', false);

        if (! $userId) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Selecione um usuário.']]);
        }

        $user = User::find($userId);

        if (! $user) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Usuário não encontrado.']]);
        }

        $tenant   = Tenant::find($user->tenant_id);
        $verified = $user->email_verified_at
            ? '✓ Verificado em ' . $user->email_verified_at->format('d/m/Y H:i')
            : '✗ NÃO VERIFICADO';

        $lines = [
            "=== Conta: {$user->name} ===",
            "  Email    : {$user->email}",
            "  Tenant   : " . ($tenant?->name ?? 'N/A'),
            "  Status   : {$verified}",
            "  Criado em: " . $user->created_at->format('d/m/Y H:i'),
        ];

        if ($resend) {
            if ($user->email_verified_at) {
                $lines[] = 'Email já verificado — reenvio não necessário.';
            } elseif (! $user->verification_token || ! $tenant) {
                $lines[] = '[ERRO] Sem token de verificação ou tenant associado. Não foi possível reenviar.';
            } else {
                Mail::to($user->email)->send(new \App\Mail\VerifyEmail($user, $tenant));
                $lines[] = "Email de verificação reenviado para {$user->email}.";
            }
        }

        return response()->json(['success' => true, 'lines' => $lines]);
    }

    private function cleanupLidConversations(Request $request): JsonResponse
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

        // Buscar conversas LID: phone > 13 dígitos, somente números, não é grupo
        $lidConversations = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_group', false)
            ->whereRaw('LENGTH(phone) > 13')
            ->whereRaw("phone REGEXP '^[0-9]+$'")
            ->get();

        if ($lidConversations->isEmpty()) {
            return response()->json(['success' => true, 'lines' => [
                "Tenant: {$tenant->name}",
                'Nenhuma conversa com LID encontrada.',
            ]]);
        }

        $convIds    = $lidConversations->pluck('id')->toArray();
        $lidPhones  = $lidConversations->pluck('phone')->toArray();

        // Deletar mensagens dessas conversas
        $deletedMessages = WhatsappMessage::withoutGlobalScope('tenant')
            ->whereIn('conversation_id', $convIds)
            ->delete();

        // Desvincular leads (não deleta, apenas remove o phone LID)
        $updatedLeads = Lead::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereIn('phone', $lidPhones)
            ->update(['phone' => null]);

        // Deletar as conversas
        $deletedConvs = WhatsappConversation::withoutGlobalScope('tenant')
            ->whereIn('id', $convIds)
            ->delete();

        return response()->json([
            'success' => true,
            'lines'   => [
                "Tenant: {$tenant->name}",
                '',
                "{$deletedConvs} conversa(s) LID removida(s)",
                "{$deletedMessages} mensagem(ns) removida(s)",
                "{$updatedLeads} lead(s) com phone LID limpo(s)",
                '',
                'Limpeza concluída com sucesso.',
            ],
        ]);
    }

    private function reimportWaHistory(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');
        $days     = max(1, min(30, (int) ($request->input('days') ?: 30)));

        if (! $tenantId) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Selecione um tenant.']]);
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Tenant não encontrado.']]);
        }

        $instance = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('status', 'connected')
            ->first();

        if (! $instance) {
            return response()->json(['success' => false, 'lines' => [
                "Tenant: {$tenant->name}",
                '[ERRO] Nenhuma instância WhatsApp conectada para este tenant.',
            ]]);
        }

        // Resetar flag para permitir reimportação
        $instance->update(['history_imported' => false]);

        // Disparar job de importação
        ImportWhatsappHistory::dispatch($instance, $days);

        return response()->json([
            'success' => true,
            'lines'   => [
                "Tenant: {$tenant->name}",
                "Instância: {$instance->session_name}",
                '',
                "Importação dos últimos {$days} dias iniciada em segundo plano.",
                'Acompanhe o progresso via logs do WhatsApp.',
            ],
        ]);
    }

    private function syncProfilePictures(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');

        if (! $tenantId) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Selecione um tenant.']]);
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Tenant não encontrado.']]);
        }

        $instance = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('status', 'connected')
            ->first();

        if (! $instance) {
            return response()->json(['success' => false, 'lines' => [
                "Tenant: {$tenant->name}",
                '[ERRO] Nenhuma instância WhatsApp conectada para este tenant.',
            ]]);
        }

        $conversations = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where(function ($q) {
                $q->whereNull('contact_picture_url')->orWhere('contact_picture_url', '');
            })
            ->get();

        if ($conversations->isEmpty()) {
            return response()->json(['success' => true, 'lines' => [
                "Tenant: {$tenant->name}",
                'Todas as conversas já possuem foto de perfil.',
            ]]);
        }

        $waha    = new WahaService($instance->session_name);
        $updated = 0;
        $noPhoto = 0;
        $errors  = 0;
        $lines   = ["Tenant: {$tenant->name}", "Encontradas {$conversations->count()} conversa(s) sem foto.", ''];

        foreach ($conversations as $conv) {
            try {
                $phone = $conv->phone;
                $pic   = null;

                // Endpoint correto: GET /api/{session}/chats/{chatId}/picture
                $chatId = $conv->is_group
                    ? (str_contains($phone, '@') ? $phone : $phone . '@g.us')
                    : $phone . '@c.us';
                $pic = $waha->getChatPicture($chatId);

                // Fallback: tentar via LID se disponível
                if (! $pic && ! empty($conv->lid)) {
                    $pic = $waha->getChatPicture($conv->lid . '@lid');
                }

                if ($pic) {
                    WhatsappConversation::withoutGlobalScope('tenant')
                        ->where('id', $conv->id)
                        ->update(['contact_picture_url' => $pic]);
                    $updated++;
                } else {
                    $noPhoto++;
                }
            } catch (\Throwable $e) {
                $errors++;
            }

            usleep(300_000); // Rate limit: 300ms
        }

        $lines[] = "{$updated} foto(s) atualizada(s)";
        $lines[] = "{$noPhoto} contato(s) sem foto disponível no WhatsApp";
        if ($errors > 0) {
            $lines[] = "[ERRO] {$errors} erro(s) ao buscar fotos";
        }
        $lines[] = '';
        $lines[] = 'Sincronização concluída.';

        return response()->json(['success' => $errors === 0, 'lines' => $lines]);
    }

    private function reimportEmptyConversations(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');

        if (! $tenantId) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Selecione um tenant.']]);
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Tenant não encontrado.']]);
        }

        $instance = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('status', 'connected')
            ->first();

        if (! $instance) {
            return response()->json(['success' => false, 'lines' => [
                "Tenant: {$tenant->name}",
                '[ERRO] Nenhuma instância WhatsApp conectada para este tenant.',
            ]]);
        }

        // Conversas com 0 mensagens
        $emptyConversations = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('whatsapp_messages')
                    ->whereColumn('whatsapp_messages.conversation_id', 'whatsapp_conversations.id');
            })
            ->get();

        if ($emptyConversations->isEmpty()) {
            return response()->json(['success' => true, 'lines' => [
                "Tenant: {$tenant->name}",
                'Todas as conversas já possuem mensagens.',
            ]]);
        }

        $waha            = new WahaService($instance->session_name);
        $totalImported   = 0;
        $totalSkipped    = 0;
        $convsWithMsgs   = 0;
        $convsEmpty      = 0;
        $errors          = 0;
        $lines           = ["Tenant: {$tenant->name}", "Encontradas {$emptyConversations->count()} conversa(s) sem mensagens.", ''];

        foreach ($emptyConversations as $conv) {
            try {
                $chatId = $conv->is_group
                    ? (str_contains($conv->phone, '@') ? $conv->phone : $conv->phone . '@g.us')
                    : $conv->phone . '@c.us';

                // Buscar mensagens SEM filtro de timestamp
                $msgs = $waha->getChatMessages($chatId, 200, 0, false, null);

                if (! is_array($msgs) || isset($msgs['error']) || empty($msgs)) {
                    $convsEmpty++;
                    usleep(300_000);
                    continue;
                }

                $imported = 0;
                foreach ($msgs as $msg) {
                    if (! is_array($msg)) {
                        continue;
                    }

                    $msgId = $msg['id'] ?? $msg['key']['id'] ?? null;
                    if (empty($msgId)) {
                        continue;
                    }

                    $rawType = $msg['type'] ?? 'chat';
                    $type    = match ($rawType) {
                        'image'               => 'image',
                        'audio', 'ptt'        => 'audio',
                        'video'               => 'video',
                        'document', 'sticker' => 'document',
                        default               => 'text',
                    };

                    $ts = isset($msg['timestamp']) ? (int) $msg['timestamp'] : 0;
                    if ($ts > 9999999999) {
                        $ts = intdiv($ts, 1000);
                    }
                    $sentAt = ($ts > 1577836800 && $ts < time() + 86400)
                        ? \Carbon\Carbon::createFromTimestamp($ts, config('app.timezone', 'America/Sao_Paulo'))
                        : now();

                    $msgBody = $msg['body'] ?? $msg['text'] ?? $msg['caption'] ?? null;

                    try {
                        WhatsappMessage::withoutGlobalScope('tenant')->create([
                            'tenant_id'       => $tenantId,
                            'conversation_id' => $conv->id,
                            'waha_message_id' => $msgId,
                            'direction'       => ($msg['fromMe'] ?? false) ? 'outbound' : 'inbound',
                            'type'            => $type,
                            'body'            => $msgBody,
                            'ack'             => 'delivered',
                            'sent_at'         => $sentAt,
                        ]);
                        $imported++;
                    } catch (\Illuminate\Database\QueryException) {
                        $totalSkipped++;
                    }
                }

                if ($imported > 0) {
                    $convsWithMsgs++;
                    $totalImported += $imported;

                    // Atualizar last_message_at
                    $latestSentAt = WhatsappMessage::withoutGlobalScope('tenant')
                        ->where('conversation_id', $conv->id)
                        ->orderByDesc('sent_at')
                        ->value('sent_at');
                    if ($latestSentAt) {
                        WhatsappConversation::withoutGlobalScope('tenant')
                            ->where('id', $conv->id)
                            ->update(['last_message_at' => $latestSentAt]);
                    }
                } else {
                    $convsEmpty++;
                }
            } catch (\Throwable $e) {
                $errors++;
            }

            usleep(300_000);
        }

        $lines[] = "{$convsWithMsgs} conversa(s) receberam mensagens";
        $lines[] = "{$totalImported} mensagem(ns) importada(s)";
        $lines[] = "{$totalSkipped} duplicada(s) ignorada(s)";
        $lines[] = "{$convsEmpty} conversa(s) sem mensagens no WAHA";
        if ($errors > 0) {
            $lines[] = "[ERRO] {$errors} erro(s)";
        }
        $lines[] = '';
        $lines[] = 'Reimportação concluída.';

        return response()->json(['success' => $errors === 0, 'lines' => $lines]);
    }

    private function resolveLidConversations(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');

        if (! $tenantId) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Selecione um tenant.']]);
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Tenant não encontrado.']]);
        }

        $instance = WhatsappInstance::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('status', 'connected')
            ->first();

        if (! $instance) {
            return response()->json(['success' => false, 'lines' => [
                "Tenant: {$tenant->name}",
                '[ERRO] Nenhuma instância WhatsApp conectada para este tenant.',
            ]]);
        }

        // Carregar mapa LID→phone via batch
        $waha   = new WahaService($instance->session_name);
        $lidMap = [];
        try {
            $allLids = $waha->getAllLids();
            if (is_array($allLids) && ! isset($allLids['error'])) {
                foreach ($allLids as $entry) {
                    if (! is_array($entry)) {
                        continue;
                    }
                    $lid   = $entry['lid'] ?? $entry['id'] ?? null;
                    $phone = $entry['phoneNumber'] ?? $entry['phone'] ?? $entry['chatId'] ?? null;
                    if ($lid && $phone) {
                        $numericLid   = (string) preg_replace('/[:@].+$/', '', $lid);
                        $numericPhone = ltrim((string) preg_replace('/[:@].+$/', '', $phone), '+');
                        if ($numericLid && $numericPhone) {
                            $lidMap[$numericLid] = $numericPhone;
                        }
                    }
                }
            }
        } catch (\Throwable) {
        }

        // Buscar conversas com phone que parece LID (>13 dígitos, não grupo)
        $lidConversations = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_group', false)
            ->whereRaw('LENGTH(phone) > 13')
            ->whereRaw("phone REGEXP '^[0-9]+$'")
            ->get();

        if ($lidConversations->isEmpty()) {
            return response()->json(['success' => true, 'lines' => [
                "Tenant: {$tenant->name}",
                "LID map carregado: " . count($lidMap) . " mapeamento(s)",
                '',
                'Nenhuma conversa com LID pendente encontrada.',
            ]]);
        }

        $resolved = 0;
        $merged   = 0;
        $blocked  = 0;
        $lines    = [
            "Tenant: {$tenant->name}",
            "LID map: " . count($lidMap) . " mapeamento(s) carregados",
            "Conversas LID encontradas: {$lidConversations->count()}",
            '',
        ];

        foreach ($lidConversations as $conv) {
            $lid       = $conv->phone;
            $realPhone = $lidMap[$lid] ?? null;

            // Se não achou no batch, tentar endpoint individual
            if (! $realPhone) {
                try {
                    $lidResult  = $waha->getPhoneByLid($lid . '@lid');
                    $candidate  = $lidResult['phoneNumber'] ?? $lidResult['phone'] ?? $lidResult['chatId'] ?? null;
                    if ($candidate) {
                        $realPhone = ltrim((string) preg_replace('/[:@].+$/', '', $candidate), '+');
                        if (strlen($realPhone) > 13 || ! ctype_digit($realPhone)) {
                            $realPhone = null;
                        }
                    }
                } catch (\Throwable) {
                }
                usleep(200_000);
            }

            if (! $realPhone) {
                $blocked++;
                continue;
            }

            // Verificar se já existe conversa com o phone real (merge)
            $existingConv = WhatsappConversation::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('phone', $realPhone)
                ->where('id', '!=', $conv->id)
                ->first();

            if ($existingConv) {
                // Merge: mover mensagens da conversa LID para a existente
                WhatsappMessage::withoutGlobalScope('tenant')
                    ->where('conversation_id', $conv->id)
                    ->update(['conversation_id' => $existingConv->id]);

                // Salvar LID na conversa existente
                if (empty($existingConv->lid)) {
                    WhatsappConversation::withoutGlobalScope('tenant')
                        ->where('id', $existingConv->id)
                        ->update(['lid' => $lid]);
                }

                // Deletar conversa LID duplicada
                WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('id', $conv->id)
                    ->delete();

                $merged++;
            } else {
                // Atualizar phone + salvar LID
                WhatsappConversation::withoutGlobalScope('tenant')
                    ->where('id', $conv->id)
                    ->update([
                        'phone' => $realPhone,
                        'lid'   => $lid,
                    ]);
                $resolved++;
            }
        }

        $lines[] = "{$resolved} conversa(s) resolvida(s) (LID → telefone real)";
        $lines[] = "{$merged} conversa(s) mesclada(s) com existentes";
        $lines[] = "{$blocked} LID(s) sem mapeamento disponível";
        $lines[] = '';
        $lines[] = 'Resolução concluída.';

        return response()->json(['success' => true, 'lines' => $lines]);
    }
}
