<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\LostSale;
use App\Models\LostSaleReason;
use App\Models\PaymentLog;
use App\Models\Pipeline;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\WahaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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
        'import-asaas-payments',
        'generate-demo-data',
        'reset-ai-tokens',
        'test-wa-notifications',
        'create-cs-user',
        'manage-cs-users',
        'manage-partner',
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

        // Limpar dados relacionados primeiro
        $salesCount = Sale::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->count();
        Sale::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->delete();

        $lostCount = LostSale::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->count();
        LostSale::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->delete();

        $eventsCount = LeadEvent::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->count();
        LeadEvent::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->delete();

        Lead::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->delete();

        return response()->json([
            'success' => true,
            'lines'   => [
                "Tenant: {$tenant->name}",
                "{$count} lead(s) removido(s).",
                "{$salesCount} venda(s) removida(s).",
                "{$lostCount} perda(s) removida(s).",
                "{$eventsCount} evento(s) removido(s).",
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

        // Buscar conversas LID: detecta tanto por comprimento (>13 dígitos) quanto
        // pela coluna lid (LIDs de 13 dígitos que ficaram armazenados como phone).
        $lidConversations = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_group', false)
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereRaw('LENGTH(phone) > 13')
                       ->whereRaw("phone REGEXP '^[0-9]+$'");
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('lid')
                       ->where('lid', '!=', '')
                       ->whereColumn('phone', 'lid');
                });
            })
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

                // Fallback: tentar phone@lid (para LIDs não resolvidos armazenados como phone)
                if (! $pic && ! $conv->is_group && ctype_digit($phone) && strlen($phone) >= 13) {
                    try { $pic = $waha->getChatPicture($phone . '@lid'); } catch (\Throwable) {}
                }

                // Fallback: tentar via coluna lid se disponível
                if (! $pic && ! empty($conv->lid)) {
                    try { $pic = $waha->getChatPicture($conv->lid . '@lid'); } catch (\Throwable) {}
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

        // Buscar conversas LID: comprimento >13 OU phone=lid OU phone no LID map batch.
        $knownLidValues = array_keys($lidMap);

        $lidConversations = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_group', false)
            ->where(function ($q) use ($knownLidValues) {
                $q->where(function ($q2) {
                    $q2->whereRaw('LENGTH(phone) > 13')
                       ->whereRaw("phone REGEXP '^[0-9]+$'");
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('lid')
                       ->where('lid', '!=', '')
                       ->whereColumn('phone', 'lid');
                });
                if (! empty($knownLidValues)) {
                    $q->orWhereIn('phone', $knownLidValues);
                }
            })
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

    // ── Import Asaas Payments ──────────────────────────────────────────────────

    private function importAsaasPayments(Request $request): JsonResponse
    {
        $apiUrl = config('services.asaas.url', 'https://www.asaas.com/api/v3');
        $apiKey = config('services.asaas.key');

        if (! $apiKey) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] ASAAS_API_KEY não configurada.']]);
        }

        // Cache de tenants por subscription_id
        $tenantsBySubscription = Tenant::whereNotNull('asaas_subscription_id')
            ->where('asaas_subscription_id', '!=', '')
            ->pluck('id', 'asaas_subscription_id')
            ->toArray();

        $lines     = [];
        $imported  = 0;
        $skipped   = 0;
        $noTenant  = 0;
        $offset    = 0;
        $limit     = 100;
        $hasMore   = true;

        $lines[] = 'Iniciando importação de pagamentos do Asaas...';
        $lines[] = 'Tenants com subscription: ' . count($tenantsBySubscription);
        $lines[] = '';

        while ($hasMore) {
            $response = Http::withHeaders([
                'access_token' => $apiKey,
                'Content-Type' => 'application/json',
            ])->get("{$apiUrl}/payments", [
                'offset' => $offset,
                'limit'  => $limit,
                'status' => 'RECEIVED',
            ]);

            if (! $response->ok()) {
                $lines[] = "[ERRO] API retornou status {$response->status()}";
                break;
            }

            $data     = $response->json();
            $payments = $data['data'] ?? [];
            $hasMore  = $data['hasMore'] ?? false;
            $offset  += $limit;

            foreach ($payments as $payment) {
                $asaasId       = $payment['id'] ?? null;
                $subscriptionId = $payment['subscription'] ?? null;
                $extRef        = $payment['externalReference'] ?? '';
                $value         = (float) ($payment['value'] ?? 0);
                $paidAt        = $payment['confirmedDate'] ?? $payment['paymentDate'] ?? null;

                if (! $asaasId) {
                    continue;
                }

                // Skip se já importado
                $exists = PaymentLog::where('asaas_payment_id', $asaasId)->exists();
                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Identificar tenant
                $tenantId    = null;
                $type        = 'subscription';
                $description = 'Assinatura';

                // Token increment?
                if (str_starts_with($extRef, 'token_increment:')) {
                    $type        = 'token_increment';
                    $description = 'Pacote de tokens';
                    $incrementId = (int) str_replace('token_increment:', '', $extRef);
                    $increment   = \App\Models\TenantTokenIncrement::find($incrementId);
                    if ($increment) {
                        $tenantId    = $increment->tenant_id;
                        $description = "Pacote de {$increment->tokens_added} tokens";
                    }
                } elseif ($subscriptionId && isset($tenantsBySubscription[$subscriptionId])) {
                    $tenantId = $tenantsBySubscription[$subscriptionId];
                    $tenant   = Tenant::find($tenantId);
                    if ($tenant) {
                        $description = "Assinatura plano {$tenant->plan}";
                    }
                }

                if (! $tenantId) {
                    $noTenant++;
                    continue;
                }

                PaymentLog::create([
                    'tenant_id'        => $tenantId,
                    'type'             => $type,
                    'description'      => $description,
                    'amount'           => $value,
                    'asaas_payment_id' => $asaasId,
                    'status'           => 'confirmed',
                    'paid_at'          => $paidAt ? \Carbon\Carbon::parse($paidAt) : now(),
                ]);

                $imported++;
            }

            // Segurança: máximo 10 páginas (1000 pagamentos)
            if ($offset >= 1000) {
                $lines[] = 'Limite de 1000 pagamentos atingido.';
                break;
            }
        }

        // Buscar também pagamentos CONFIRMED (PIX pendente confirmação)
        $offset  = 0;
        $hasMore = true;

        while ($hasMore) {
            $response = Http::withHeaders([
                'access_token' => $apiKey,
                'Content-Type' => 'application/json',
            ])->get("{$apiUrl}/payments", [
                'offset' => $offset,
                'limit'  => $limit,
                'status' => 'CONFIRMED',
            ]);

            if (! $response->ok()) {
                break;
            }

            $data     = $response->json();
            $payments = $data['data'] ?? [];
            $hasMore  = $data['hasMore'] ?? false;
            $offset  += $limit;

            foreach ($payments as $payment) {
                $asaasId        = $payment['id'] ?? null;
                $subscriptionId = $payment['subscription'] ?? null;
                $extRef         = $payment['externalReference'] ?? '';
                $value          = (float) ($payment['value'] ?? 0);
                $paidAt         = $payment['confirmedDate'] ?? $payment['paymentDate'] ?? null;

                if (! $asaasId || PaymentLog::where('asaas_payment_id', $asaasId)->exists()) {
                    $skipped++;
                    continue;
                }

                $tenantId    = null;
                $type        = 'subscription';
                $description = 'Assinatura';

                if (str_starts_with($extRef, 'token_increment:')) {
                    $type        = 'token_increment';
                    $description = 'Pacote de tokens';
                    $incrementId = (int) str_replace('token_increment:', '', $extRef);
                    $increment   = \App\Models\TenantTokenIncrement::find($incrementId);
                    if ($increment) {
                        $tenantId    = $increment->tenant_id;
                        $description = "Pacote de {$increment->tokens_added} tokens";
                    }
                } elseif ($subscriptionId && isset($tenantsBySubscription[$subscriptionId])) {
                    $tenantId = $tenantsBySubscription[$subscriptionId];
                    $tenant   = Tenant::find($tenantId);
                    if ($tenant) {
                        $description = "Assinatura plano {$tenant->plan}";
                    }
                }

                if (! $tenantId) {
                    $noTenant++;
                    continue;
                }

                PaymentLog::create([
                    'tenant_id'        => $tenantId,
                    'type'             => $type,
                    'description'      => $description,
                    'amount'           => $value,
                    'asaas_payment_id' => $asaasId,
                    'status'           => 'confirmed',
                    'paid_at'          => $paidAt ? \Carbon\Carbon::parse($paidAt) : now(),
                ]);

                $imported++;
            }

            if ($offset >= 1000) {
                break;
            }
        }

        $lines[] = "{$imported} pagamento(s) importado(s)";
        $lines[] = "{$skipped} já existente(s) (ignorados)";
        $lines[] = "{$noTenant} sem tenant associado (ignorados)";
        $lines[] = '';
        $lines[] = 'Importação concluída.';

        return response()->json(['success' => true, 'lines' => $lines]);
    }

    // ─── 16. Gerar Dados Demo ────────────────────────────────────────────────
    private function generateDemoData(Request $request): JsonResponse
    {
        $tenantId   = (int) $request->input('tenant_id');
        $qty        = max(1, min(500, (int) $request->input('quantity', 50)));
        $dateFrom   = $request->input('date_from', now()->subMonths(3)->format('Y-m-d'));
        $dateTo     = $request->input('date_to', now()->format('Y-m-d'));
        $valueMin   = max(0, (float) $request->input('value_min', 500));
        $valueMax   = max($valueMin, (float) $request->input('value_max', 50000));
        $pctWon     = max(0, min(100, (int) $request->input('pct_won', 20)));
        $pctLost    = max(0, min(100 - $pctWon, (int) $request->input('pct_lost', 10)));
        $withUtms   = (bool) $request->input('with_utms', false);
        $withTags   = (bool) $request->input('with_tags', false);

        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            return response()->json(['success' => false, 'lines' => ['Tenant não encontrado.']]);
        }

        $lines = [];
        $lines[] = "\033[1mTenant: {$tenant->name}\033[0m";

        // Pipeline
        $pipeline = Pipeline::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_default', true)
            ->first();

        if (! $pipeline) {
            $pipeline = Pipeline::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->first();
        }

        if (! $pipeline) {
            return response()->json(['success' => false, 'lines' => ['Nenhuma pipeline encontrada para este tenant.']]);
        }

        $stages = $pipeline->stages()->orderBy('position')->get();
        $wonStage  = $stages->firstWhere('is_won', true);
        $lostStage = $stages->firstWhere('is_lost', true);
        $activeStages = $stages->where('is_won', false)->where('is_lost', false)->values();

        $lines[] = "Pipeline: {$pipeline->name} ({$stages->count()} etapas)";

        // Users do tenant
        $users = User::where('tenant_id', $tenantId)->pluck('id')->toArray();

        // Tags
        $tagPool = [];
        if ($withTags) {
            $tagPool = ['Cliente Novo', 'Recorrente', 'VIP', 'Atacado', 'Promoção', 'Indicação'];
            $lines[] = "\033[32mTags criadas: " . count($tagPool) . "\033[0m";
        }

        // UTM combos
        $utmCombos = [
            ['source' => 'google',    'medium' => 'cpc',     'campaign' => 'google_ads_marca'],
            ['source' => 'google',    'medium' => 'cpc',     'campaign' => 'google_ads_concorrentes'],
            ['source' => 'facebook',  'medium' => 'paid',    'campaign' => 'facebook_remarketing'],
            ['source' => 'facebook',  'medium' => 'paid',    'campaign' => 'facebook_lookalike'],
            ['source' => 'instagram', 'medium' => 'paid',    'campaign' => 'instagram_stories'],
            ['source' => 'instagram', 'medium' => 'social',  'campaign' => 'reels_organico'],
            ['source' => 'newsletter','medium' => 'email',   'campaign' => 'news_marco_2026'],
            ['source' => 'whatsapp',  'medium' => 'referral','campaign' => 'indicacao_clientes'],
            ['source' => 'linkedin',  'medium' => 'social',  'campaign' => 'b2b_outreach'],
            ['source' => 'youtube',   'medium' => 'video',   'campaign' => 'youtube_tutorial'],
        ];

        $firstNames = ['Ana', 'Bruno', 'Carla', 'Diego', 'Elisa', 'Fernando', 'Gabriela', 'Hugo', 'Isabela', 'João', 'Karen', 'Lucas', 'Mariana', 'Nicolas', 'Patricia', 'Rafael', 'Sophia', 'Thiago', 'Valentina', 'Wesley', 'Beatriz', 'Caio', 'Daniela', 'Eduardo', 'Fernanda', 'Gustavo', 'Helena', 'Igor', 'Julia', 'Leonardo'];
        $lastNames  = ['Silva', 'Santos', 'Oliveira', 'Souza', 'Lima', 'Costa', 'Ferreira', 'Rodrigues', 'Almeida', 'Pereira', 'Carvalho', 'Gomes', 'Ribeiro', 'Martins', 'Araujo', 'Vieira', 'Moreira', 'Rocha', 'Correia', 'Nascimento'];
        $ddds       = ['11','21','31','41','51','61','71','81','91','27','47','48','62','65','67','83','84','85','92','98'];
        $sources    = ['whatsapp', 'instagram', 'facebook', 'google', 'telefone', 'indicacao', 'site'];
        $companies  = ['Tech Solutions', 'Casa & Decor', 'Studio Design', 'Consultoria ABC', 'Importadora XYZ', 'Loja do João', 'Clínica Vida', 'Academia Fit', 'Escritório Central', 'Padaria Pão Quente'];

        $from = Carbon::parse($dateFrom)->startOfDay();
        $to   = Carbon::parse($dateTo)->endOfDay();
        $diffSeconds = max(1, (int) abs($to->diffInSeconds($from)));

        // Calcular distribuição
        $qtyWon  = (int) round($qty * $pctWon / 100);
        $qtyLost = (int) round($qty * $pctLost / 100);
        $qtyActive = $qty - $qtyWon - $qtyLost;

        // Distribuição dos ativos nas stages
        $stageWeights = [30, 25, 20, 15, 10];
        $stageDist = [];
        foreach ($activeStages as $i => $stage) {
            $weight = $stageWeights[$i] ?? 5;
            $stageDist[] = ['stage' => $stage, 'weight' => $weight];
        }
        $totalWeight = array_sum(array_column($stageDist, 'weight'));

        // Lost sale reasons
        $lostReasons = LostSaleReason::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        if (empty($lostReasons) && $qtyLost > 0) {
            foreach (['Preço alto', 'Concorrência', 'Sem resposta'] as $rName) {
                $r = new LostSaleReason();
                $r->tenant_id = $tenantId;
                $r->name = $rName;
                $r->is_active = true;
                $r->sort_order = 0;
                $r->save();
                $lostReasons[] = $r->id;
            }
            $lines[] = "Motivos de perda criados: " . count($lostReasons);
        }

        $created = 0;
        $salesTotal = 0.0;
        $stageCounters = [];
        $utmCount = 0;

        DB::beginTransaction();
        try {
            // Create won leads
            for ($i = 0; $i < $qtyWon; $i++) {
                $lead = $this->createDemoLead($tenantId, $pipeline, $wonStage, $firstNames, $lastNames, $ddds, $sources, $companies, $tagPool, $utmCombos, $withUtms, $users, $from, $diffSeconds, $valueMin, $valueMax, $utmCount);
                $utmCount = $lead['_utmCount'];
                $stageCounters[$wonStage->name] = ($stageCounters[$wonStage->name] ?? 0) + 1;

                // Create Sale
                $closedAt = Carbon::parse($lead['created_at'])->addDays(rand(1, 30));
                if ($closedAt->gt($to)) $closedAt = $to;

                Sale::withoutGlobalScope('tenant')->create([
                    'tenant_id'   => $tenantId,
                    'lead_id'     => $lead['id'],
                    'pipeline_id' => $pipeline->id,
                    'value'       => $lead['value'],
                    'closed_by'   => !empty($users) ? $users[array_rand($users)] : null,
                    'closed_at'   => $closedAt,
                ]);
                $salesTotal += (float) $lead['value'];
                $created++;
            }

            // Create lost leads
            for ($i = 0; $i < $qtyLost; $i++) {
                $lead = $this->createDemoLead($tenantId, $pipeline, $lostStage, $firstNames, $lastNames, $ddds, $sources, $companies, $tagPool, $utmCombos, $withUtms, $users, $from, $diffSeconds, $valueMin, $valueMax, $utmCount);
                $utmCount = $lead['_utmCount'];
                $stageCounters[$lostStage->name] = ($stageCounters[$lostStage->name] ?? 0) + 1;

                $lostAt = Carbon::parse($lead['created_at'])->addDays(rand(1, 20));
                if ($lostAt->gt($to)) $lostAt = $to;

                LostSale::withoutGlobalScope('tenant')->create([
                    'tenant_id'   => $tenantId,
                    'lead_id'     => $lead['id'],
                    'pipeline_id' => $pipeline->id,
                    'reason_id'   => $lostReasons[array_rand($lostReasons)],
                    'lost_by'     => !empty($users) ? $users[array_rand($users)] : null,
                    'lost_at'     => $lostAt,
                ]);
                $created++;
            }

            // Create active leads (distributed across stages)
            for ($i = 0; $i < $qtyActive; $i++) {
                $rand = rand(1, $totalWeight);
                $cumulative = 0;
                $selectedStage = $activeStages->first();
                foreach ($stageDist as $sd) {
                    $cumulative += $sd['weight'];
                    if ($rand <= $cumulative) {
                        $selectedStage = $sd['stage'];
                        break;
                    }
                }

                $lead = $this->createDemoLead($tenantId, $pipeline, $selectedStage, $firstNames, $lastNames, $ddds, $sources, $companies, $tagPool, $utmCombos, $withUtms, $users, $from, $diffSeconds, $valueMin, $valueMax, $utmCount);
                $utmCount = $lead['_utmCount'];
                $stageCounters[$selectedStage->name] = ($stageCounters[$selectedStage->name] ?? 0) + 1;
                $created++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'lines' => ["Erro: {$e->getMessage()}"]]);
        }

        $lines[] = '';
        $lines[] = "\033[32mLeads criados: {$created}\033[0m";
        foreach ($stageCounters as $stageName => $count) {
            $lines[] = "  → {$stageName}: {$count}";
        }
        if ($qtyWon > 0) {
            $lines[] = "  → \033[32mGanhos: {$qtyWon} (R$ " . number_format($salesTotal, 2, ',', '.') . ")\033[0m";
        }
        if ($qtyLost > 0) {
            $lines[] = "  → \033[31mPerdidos: {$qtyLost}\033[0m";
        }
        if ($withUtms) {
            $lines[] = "Leads com UTM: {$utmCount}";
        }
        $lines[] = '';
        $lines[] = 'Geração concluída.';

        return response()->json(['success' => true, 'lines' => $lines]);
    }

    private function createDemoLead(
        int $tenantId,
        $pipeline,
        $stage,
        array $firstNames,
        array $lastNames,
        array $ddds,
        array $sources,
        array $companies,
        array $tagPool,
        array $utmCombos,
        bool $withUtms,
        array $users,
        Carbon $from,
        int $diffSeconds,
        float $valueMin,
        float $valueMax,
        int &$utmCount
    ): array {
        $first = $firstNames[array_rand($firstNames)];
        $last  = $lastNames[array_rand($lastNames)];
        $name  = "{$first} {$last}";
        $ddd   = $ddds[array_rand($ddds)];
        $phone = "55{$ddd}9" . str_pad((string) rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $email = strtolower(str_replace(' ', '.', $this->removeAccents($first))) . '.'
               . strtolower(str_replace(' ', '', $this->removeAccents($last)))
               . rand(10, 99) . '@gmail.com';

        $value = round($valueMin + (mt_rand() / mt_getrandmax()) * ($valueMax - $valueMin), 2);
        $createdAt = $from->copy()->addSeconds(mt_rand(0, $diffSeconds));

        $tags = [];
        if (!empty($tagPool) && rand(1, 100) <= 70) {
            $numTags = rand(1, 3);
            $shuffled = $tagPool;
            shuffle($shuffled);
            $tags = array_slice($shuffled, 0, $numTags);
        }

        $utmData = [];
        if ($withUtms && rand(1, 100) <= 60) {
            $utmData = $utmCombos[array_rand($utmCombos)];
            $utmCount++;
        }

        $lead = new Lead();
        $lead->tenant_id   = $tenantId;
        $lead->name        = $name;
        $lead->phone       = $phone;
        $lead->email       = $email;
        $lead->company     = rand(1, 100) > 50 ? $companies[array_rand($companies)] : null;
        $lead->value       = $value;
        $lead->source      = $sources[array_rand($sources)];
        $lead->pipeline_id = $pipeline->id;
        $lead->stage_id    = $stage->id;
        $lead->tags        = !empty($tags) ? $tags : null;
        $lead->assigned_to = !empty($users) ? $users[array_rand($users)] : null;
        $lead->birthday    = rand(1, 100) <= 30 ? Carbon::now()->subYears(rand(20, 55))->subDays(rand(0, 365))->format('Y-m-d') : null;
        $lead->created_at  = $createdAt;
        $lead->updated_at  = $createdAt;

        if (!empty($utmData)) {
            $lead->utm_source   = $utmData['source'];
            $lead->utm_medium   = $utmData['medium'];
            $lead->utm_campaign = $utmData['campaign'];
        }

        $lead->save();

        LeadEvent::withoutGlobalScope('tenant')->create([
            'tenant_id'   => $tenantId,
            'lead_id'     => $lead->id,
            'event_type'  => 'created',
            'description' => 'Lead criado (demo)',
            'created_at'  => $createdAt,
        ]);

        return array_merge($lead->toArray(), ['_utmCount' => $utmCount]);
    }

    private function resetAiTokens(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');
        if (! $tenantId) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Selecione uma empresa.']]);
        }

        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Empresa não encontrada.']]);
        }

        $lines = [];

        // Zerar logs do mês atual
        $startOfMonth = Carbon::now()->startOfMonth();
        $deleted = DB::table('ai_usage_logs')
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startOfMonth)
            ->delete();

        $lines[] = "[OK] {$deleted} registros de uso de tokens removidos (mês atual).";

        // Limpar flag de tokens esgotados
        if ($tenant->ai_tokens_exhausted) {
            $tenant->update(['ai_tokens_exhausted' => false]);
            $lines[] = "[OK] Flag ai_tokens_exhausted resetado para false.";
        } else {
            $lines[] = "[INFO] Flag ai_tokens_exhausted já era false.";
        }

        $lines[] = "[DONE] Tokens de IA zerados para: {$tenant->name}";

        return response()->json(['success' => true, 'lines' => $lines]);
    }

    private function testWaNotifications(Request $request): JsonResponse
    {
        $type  = $request->input('type', 'registration');
        $lines = [];

        $notifier = \App\Services\MasterWhatsappNotifier::class;

        switch ($type) {
            case 'registration':
                $fakeTenant = new Tenant([
                    'name'             => 'Empresa Teste LTDA',
                    'plan'             => 'free',
                    'status'           => 'trial',
                    'trial_ends_at'    => now()->addDays(14),
                    'locale'           => 'pt_BR',
                    'billing_provider' => 'asaas',
                    'billing_currency' => 'BRL',
                ]);
                $fakeUser = new User([
                    'name'  => 'João da Silva',
                    'email' => 'joao@empresa-teste.com',
                ]);
                $notifier::newRegistration($fakeTenant, $fakeUser, null);
                $lines[] = '[OK] Notificação de NOVO CADASTRO enviada.';
                break;

            case 'agency':
                $fakeTenant = new Tenant([
                    'name'   => 'Agência Digital Pro',
                    'plan'   => 'partner',
                    'status' => 'partner',
                ]);
                $fakeUser = new User([
                    'name'  => 'Maria Souza',
                    'email' => 'maria@agenciadigitalpro.com',
                ]);
                $notifier::newAgencyRegistration($fakeTenant, $fakeUser, 'AGENCY2026');
                $lines[] = '[OK] Notificação de NOVA AGÊNCIA enviada.';
                break;

            case 'payment':
                $fakeTenant = new Tenant([
                    'name'             => 'Tech Solutions ME',
                    'plan'             => 'professional',
                    'billing_currency' => 'BRL',
                ]);
                $notifier::paymentConfirmed($fakeTenant, 197.00, 'Asaas', 'pay_test_abc123');
                $lines[] = '[OK] Notificação de PAGAMENTO CONFIRMADO enviada.';
                break;

            case 'tokens':
                $fakeTenant = new Tenant([
                    'name'             => 'Clínica Saúde+',
                    'billing_currency' => 'BRL',
                ]);
                $notifier::tokenPurchase($fakeTenant, 50000, 49.90, 'Asaas');
                $lines[] = '[OK] Notificação de COMPRA DE TOKENS enviada.';
                break;

            case 'weekly':
                $notifier::weeklyReport();
                $lines[] = '[OK] RELATÓRIO SEMANAL enviado (dados reais da plataforma).';
                break;

            default:
                $lines[] = '[ERRO] Tipo de notificação inválido.';
        }

        $lines[] = '';
        $lines[] = "Grupo: 120363403276686046@g.us";
        $lines[] = "Instância: tenant_12";

        return response()->json(['success' => true, 'lines' => $lines]);
    }

    private function createCsUser(Request $request): JsonResponse
    {
        $name     = trim($request->input('name', ''));
        $email    = trim($request->input('email', ''));
        $password = $request->input('password', '');

        if (!$name || !$email || !$password) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Preencha todos os campos.']]);
        }

        if (strlen($password) < 8) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] A senha deve ter pelo menos 8 caracteres.']]);
        }

        if (User::where('email', $email)->exists()) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Já existe um usuário com este email.']]);
        }

        $user = User::create([
            'name'        => $name,
            'email'       => $email,
            'password'    => $password,
            'is_cs_agent' => true,
            'tenant_id'   => null,
            'role'        => 'viewer',
            'email_verified_at' => now(),
        ]);

        return response()->json(['success' => true, 'lines' => [
            "[OK] Usuário CS criado com sucesso!",
            "",
            "Nome: {$user->name}",
            "Email: {$user->email}",
            "ID: {$user->id}",
            "",
            "O usuário pode fazer login em /login e será redirecionado automaticamente para o painel CS.",
        ]]);
    }

    private function manageCsUsers(Request $request): JsonResponse
    {
        $action = $request->input('action', 'list');

        if ($action === 'delete') {
            $userId = (int) $request->input('user_id');
            $user = User::where('is_cs_agent', true)->find($userId);

            if (!$user) {
                return response()->json(['success' => false, 'lines' => ['[ERRO] Usuário CS não encontrado.']]);
            }

            $name = $user->name;
            $email = $user->email;
            $user->delete();

            return response()->json(['success' => true, 'lines' => [
                "[OK] Usuário CS deletado!",
                "Nome: {$name}",
                "Email: {$email}",
            ]]);
        }

        // List
        $csUsers = User::where('is_cs_agent', true)->orderBy('name')->get(['id', 'name', 'email', 'last_login_at', 'created_at']);

        if ($csUsers->isEmpty()) {
            return response()->json(['success' => true, 'lines' => ['Nenhum usuário CS cadastrado.', '', 'Use a ferramenta "Criar Usuário CS" para criar um.']]);
        }

        $lines = ["Usuários CS cadastrados ({$csUsers->count()}):", ""];
        foreach ($csUsers as $u) {
            $lastLogin = $u->last_login_at ? $u->last_login_at->format('d/m/Y H:i') : 'Nunca';
            $lines[] = "ID: {$u->id} | {$u->name} | {$u->email} | Último login: {$lastLogin}";
        }

        $lines[] = "";
        $lines[] = "Para deletar, execute novamente com action=delete e user_id=<ID>.";

        return response()->json(['success' => true, 'lines' => $lines]);
    }

    // ── Gerenciar Parceiro ──────────────────────────────────────────────────

    private function managePartner(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');
        $action   = $request->input('action'); // 'unlink', 'switch', 'info'
        $newCode  = $request->input('agency_code');

        if (!$tenantId) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Selecione uma empresa.']], 422);
        }

        $tenant = Tenant::withoutGlobalScope('tenant')->find($tenantId);
        if (!$tenant) {
            return response()->json(['success' => false, 'lines' => ['[ERRO] Empresa não encontrada.']], 404);
        }

        $lines = [];

        // Info — mostrar parceiro atual
        if ($action === 'info' || !$action) {
            $lines[] = "Empresa: {$tenant->name} (ID: {$tenant->id})";
            if ($tenant->referred_by_agency_id) {
                $partner = Tenant::withoutGlobalScope('tenant')->find($tenant->referred_by_agency_id);
                $lines[] = "Parceiro vinculado: {$partner->name} (ID: {$partner->id})";

                $pendingCount   = \App\Models\PartnerCommission::where('tenant_id', $partner->id)->where('client_tenant_id', $tenant->id)->where('status', 'pending')->count();
                $availableCount = \App\Models\PartnerCommission::where('tenant_id', $partner->id)->where('client_tenant_id', $tenant->id)->where('status', 'available')->count();
                $totalAmount    = \App\Models\PartnerCommission::where('tenant_id', $partner->id)->where('client_tenant_id', $tenant->id)->whereIn('status', ['pending', 'available', 'withdrawn'])->sum('amount');

                $lines[] = "Comissões: {$pendingCount} pendentes, {$availableCount} disponíveis, total R$ " . number_format((float) $totalAmount, 2, ',', '.');
            } else {
                $lines[] = 'Sem parceiro vinculado.';
            }
            return response()->json(['success' => true, 'lines' => $lines]);
        }

        // Unlink — desvincular
        if ($action === 'unlink') {
            if (!$tenant->referred_by_agency_id) {
                return response()->json(['success' => false, 'lines' => ['[ERRO] Empresa não tem parceiro vinculado.']], 422);
            }

            $oldPartnerId = $tenant->referred_by_agency_id;
            $oldPartner   = Tenant::withoutGlobalScope('tenant')->find($oldPartnerId);

            $cancelledCount = \App\Models\PartnerCommission::where('tenant_id', $oldPartnerId)
                ->where('client_tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            $tenant->update(['referred_by_agency_id' => null]);

            // Notificar parceiro
            $this->notifyPartnerChange($oldPartnerId, $tenant, 'unlinked');

            $lines[] = "[OK] Desvinculado de: {$oldPartner->name}";
            $lines[] = "[OK] {$cancelledCount} comissão(ões) pendente(s) cancelada(s).";
            $lines[] = '[OK] Comissões já liberadas (available/withdrawn) foram mantidas.';

            return response()->json(['success' => true, 'lines' => $lines]);
        }

        // Switch — trocar parceiro
        if ($action === 'switch') {
            if (!$newCode) {
                return response()->json(['success' => false, 'lines' => ['[ERRO] Informe o código da nova agência.']], 422);
            }

            $agencyCode = \App\Models\PartnerAgencyCode::where('code', strtoupper($newCode))
                ->where('is_active', true)
                ->whereNotNull('tenant_id')
                ->first();

            if (!$agencyCode) {
                return response()->json(['success' => false, 'lines' => ['[ERRO] Código inválido, inativo ou não encontrado.']], 422);
            }

            $oldPartnerId = $tenant->referred_by_agency_id;
            $newPartnerId = $agencyCode->tenant_id;
            $cancelledCount = 0;

            if ($oldPartnerId) {
                $oldPartner     = Tenant::withoutGlobalScope('tenant')->find($oldPartnerId);
                $cancelledCount = \App\Models\PartnerCommission::where('tenant_id', $oldPartnerId)
                    ->where('client_tenant_id', $tenant->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);
                $this->notifyPartnerChange($oldPartnerId, $tenant, 'unlinked');
                $lines[] = "[OK] Desvinculado de: {$oldPartner->name} ({$cancelledCount} comissões pendentes canceladas)";
            }

            $tenant->update(['referred_by_agency_id' => $newPartnerId]);
            $newPartner = Tenant::withoutGlobalScope('tenant')->find($newPartnerId);
            $this->notifyPartnerChange($newPartnerId, $tenant, 'linked');

            $lines[] = "[OK] Vinculado a: {$newPartner->name}";
            $lines[] = '[OK] Novas comissões serão contabilizadas para o novo parceiro.';

            return response()->json(['success' => true, 'lines' => $lines]);
        }

        return response()->json(['success' => false, 'lines' => ['[ERRO] Ação inválida. Use: info, unlink ou switch.']], 422);
    }

    private function notifyPartnerChange(int $partnerId, Tenant $client, string $action): void
    {
        try {
            $partnerAdmin = User::where('tenant_id', $partnerId)->where('role', 'admin')->first();
            if (!$partnerAdmin) return;

            if ($action === 'unlinked') {
                Mail::send('emails.partner-client-unlinked', [
                    'partnerName' => $partnerAdmin->name,
                    'clientName'  => $client->name,
                ], function ($msg) use ($partnerAdmin, $client) {
                    $msg->to($partnerAdmin->email)
                        ->subject("Cliente {$client->name} se desvinculou da sua agência — Syncro");
                });
            } else {
                Mail::raw(
                    "Olá {$partnerAdmin->name},\n\nO cliente \"{$client->name}\" foi vinculado à sua agência parceira no Syncro.\n\nAs próximas cobranças deste cliente gerarão comissões para você.\n\nEquipe Syncro",
                    function ($msg) use ($partnerAdmin, $client) {
                        $msg->to($partnerAdmin->email)
                            ->subject("Novo cliente vinculado: {$client->name} — Syncro");
                    }
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('Falha ao notificar parceiro sobre mudança de vínculo', ['error' => $e->getMessage()]);
        }
    }

    private function removeAccents(string $str): string
    {
        return strtr($str, [
            'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ü'=>'u','ç'=>'c',
            'Á'=>'A','À'=>'A','Ã'=>'A','Â'=>'A','É'=>'E','Ê'=>'E','Í'=>'I','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ú'=>'U','Ü'=>'U','Ç'=>'C',
        ]);
    }
}
