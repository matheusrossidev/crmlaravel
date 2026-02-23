<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\WahaService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class WaImportHistoryCommand extends Command
{
    protected $signature = 'wa:import-history
                            {--limit=100 : Máximo de mensagens a importar por chat}
                            {--skip-groups : Pular grupos}';

    protected $description = 'Importa histórico de conversas e mensagens do WhatsApp via WAHA';

    private int $importedChats    = 0;
    private int $importedMessages = 0;
    private int $skippedMessages  = 0;

    public function handle(): void
    {
        $msgLimit   = (int) $this->option('limit');
        $skipGroups = (bool) $this->option('skip-groups');

        $instances = WhatsappInstance::where('status', 'connected')->get();

        if ($instances->isEmpty()) {
            $this->warn('Nenhuma instância WhatsApp conectada encontrada.');
            return;
        }

        foreach ($instances as $instance) {
            $this->info("Processando instância: {$instance->session_name} (tenant #{$instance->tenant_id})");
            $this->importInstance($instance, $msgLimit, $skipGroups);
        }

        $this->newLine();
        $this->info("✓ Importação concluída.");
        $this->table(
            ['Conversas importadas', 'Mensagens importadas', 'Mensagens já existentes'],
            [[$this->importedChats, $this->importedMessages, $this->skippedMessages]]
        );
    }

    private function importInstance(WhatsappInstance $instance, int $msgLimit, bool $skipGroups): void
    {
        $waha   = new WahaService($instance->session_name);
        $limit  = 50;
        $offset = 0;

        do {
            try {
                $chats = $waha->getChats($limit, $offset);
            } catch (\Throwable $e) {
                $this->error("Erro ao buscar chats (offset={$offset}): " . $e->getMessage());
                break;
            }

            if (isset($chats['error']) || ! is_array($chats) || empty($chats)) {
                break;
            }

            foreach ($chats as $chat) {
                if (! is_array($chat) || empty($chat['id'])) {
                    continue;
                }

                $isGroup = (bool) ($chat['isGroup'] ?? false);

                if ($skipGroups && $isGroup) {
                    continue;
                }

                $this->importChat($waha, $instance, $chat, $msgLimit);
            }

            $offset += $limit;
        } while (count($chats) >= $limit);
    }

    private function importChat(WahaService $waha, WhatsappInstance $instance, array $chat, int $msgLimit): void
    {
        $chatId      = $chat['id'];
        $isGroup     = (bool) ($chat['isGroup'] ?? false);
        $contactName = $chat['name'] ?? null;
        $phone       = $this->normalizePhone($chatId);

        if ($phone === '') {
            return;
        }

        // Busca ou cria conversa
        $conversation = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('tenant_id', $instance->tenant_id)
            ->where('phone', $phone)
            ->first();

        if (! $conversation) {
            $conversation = WhatsappConversation::withoutGlobalScope('tenant')->create([
                'tenant_id'    => $instance->tenant_id,
                'instance_id'  => $instance->id,
                'phone'        => $phone,
                'is_group'     => $isGroup,
                'contact_name' => $contactName,
                'status'       => 'open',
                'started_at'   => now(),
                'last_message_at' => now(),
                'unread_count' => 0,
            ]);
            $this->importedChats++;
            $this->line("  + Chat: {$phone} ({$contactName})");
        }

        // Buscar mensagens sem download de mídia
        $msgOffset = 0;
        $msgLimit2 = min($msgLimit, 100);

        do {
            try {
                $messages = $waha->getChatMessages($chatId, $msgLimit2, $msgOffset, false);
            } catch (\Throwable $e) {
                $this->warn("  ! Erro ao buscar mensagens de {$chatId}: " . $e->getMessage());
                break;
            }

            if (isset($messages['error']) || ! is_array($messages) || empty($messages)) {
                break;
            }

            foreach ($messages as $msg) {
                if (! is_array($msg) || empty($msg['id'])) {
                    continue;
                }

                $this->importMessage($msg, $conversation);
            }

            $msgOffset += $msgLimit2;

            // Para quando recebemos menos que o limite (fim do histórico)
            if (count($messages) < $msgLimit2) {
                break;
            }

            // Respeita o limite máximo configurado
            if ($msgOffset >= $msgLimit) {
                break;
            }
        } while (true);

        // Atualiza last_message_at com a mensagem mais recente importada
        $latest = WhatsappMessage::withoutGlobalScope('tenant')
            ->where('conversation_id', $conversation->id)
            ->orderByDesc('sent_at')
            ->value('sent_at');

        if ($latest) {
            WhatsappConversation::withoutGlobalScope('tenant')
                ->where('id', $conversation->id)
                ->update(['last_message_at' => $latest]);
        }
    }

    private function importMessage(array $msg, WhatsappConversation $conversation): void
    {
        $wahaId    = $msg['id'] ?? null;
        $fromMe    = (bool) ($msg['fromMe'] ?? false);
        $body      = $msg['body'] ?? null;
        $rawType   = $msg['type'] ?? 'chat';
        $timestamp = isset($msg['timestamp']) ? (int) $msg['timestamp'] : null;

        $sentAt = $timestamp
            ? Carbon::createFromTimestamp($timestamp, config('app.timezone', 'America/Sao_Paulo'))
            : now();

        $type = match ($rawType) {
            'image'                => 'image',
            'audio', 'ptt'         => 'audio',
            'video'                => 'video',
            'document', 'sticker'  => 'document',
            default                => 'text',
        };

        try {
            WhatsappMessage::withoutGlobalScope('tenant')->create([
                'tenant_id'       => $conversation->tenant_id,
                'conversation_id' => $conversation->id,
                'waha_message_id' => $wahaId,
                'direction'       => $fromMe ? 'outbound' : 'inbound',
                'type'            => $type,
                'body'            => $body,
                'ack'             => 'delivered',
                'sent_at'         => $sentAt,
            ]);
            $this->importedMessages++;
        } catch (QueryException $e) {
            // Violação de UNIQUE (waha_message_id já existe) — pular
            $this->skippedMessages++;
        }
    }

    private function normalizePhone(string $jid): string
    {
        // Remove sufixo de dispositivo (:22) e parte do JID (@c.us, @s.whatsapp.net, @lid, @g.us)
        $phone = preg_replace('/[:@].+$/', '', $jid);
        return ltrim((string) $phone, '+');
    }
}
