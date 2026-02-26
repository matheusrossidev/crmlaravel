<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\WahaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WhatsappMessageController extends Controller
{
    public function store(WhatsappConversation $conversation, Request $request): JsonResponse
    {
        $type = $request->input('type', 'text');
        $body = $request->input('body', '');

        $instance = WhatsappInstance::first();
        if (! $instance || $instance->status !== 'connected') {
            return response()->json(['error' => 'WhatsApp não conectado'], 422);
        }

        $waha = new WahaService($instance->session_name);

        // ── Build chatId ──────────────────────────────────────────────────────
        // Groups always use @g.us (phone stores only the numeric group ID).
        // Individual contacts: derive from waha_message_id history to preserve
        // @lid for GOWS engine; fallback to phone@c.us if no history exists.
        $rawPhone = ltrim((string) preg_replace('/[:@\s].+$/', '', $conversation->phone), '+');

        if ($conversation->is_group) {
            $chatId = $rawPhone . '@g.us';
        } else {
            $chatId   = null;
            $sampleId = WhatsappMessage::where('conversation_id', $conversation->id)
                ->whereNotNull('waha_message_id')
                ->where('direction', 'inbound')
                ->latest('sent_at')
                ->value('waha_message_id');

            // Format: "{true|false}_{jid}_{messageId}" e.g. "false_36576092528787@lid_3EB0xxx"
            if ($sampleId && preg_match('/^(?:true|false)_(.+@[\w.]+)_/', $sampleId, $m)) {
                $jid = $m[1]; // "36576092528787@lid" or "556192008997@c.us"
                if (str_ends_with($jid, '@lid')) {
                    $chatId = preg_replace('/[:@].+$/', '', $jid) . '@lid';
                } else {
                    $chatId = preg_replace('/[:@].+$/', '', $jid) . '@c.us';
                }
            }

            $chatId ??= $rawPhone . '@c.us';
        }

        $wahaMessageId = null;
        $mediaUrl      = null;
        $mediaMime     = null;
        $mediaFilename = null;

        if ($type === 'note') {
            // Nota privada: não envia ao WAHA
        } elseif ($type === 'text') {
            $result = $waha->sendText($chatId, $body);
            if (isset($result['error'])) {
                return response()->json(['error' => 'Falha ao enviar mensagem no WhatsApp: ' . ($result['body'] ?? 'erro desconhecido')], 422);
            }
            $wahaMessageId = $result['id'] ?? null;
        } elseif ($type === 'image' && $request->hasFile('file')) {
            // handleUpload retorna [storagePath, mime, filename, publicUrl]
            [$storagePath, $mediaMime, $mediaFilename, $mediaUrl] = $this->handleUpload($request, 'image');
            // Envia via base64 direto ao WAHA (evita que WAHA precise alcançar URL interna do Docker)
            $absolutePath = storage_path('app/public/' . $storagePath);
            $result       = $waha->sendImageBase64($chatId, $absolutePath, $mediaMime, $body);
            if (isset($result['error'])) {
                return response()->json(['error' => 'Falha ao enviar imagem no WhatsApp: ' . ($result['body'] ?? 'erro desconhecido')], 422);
            }
            $wahaMessageId = $result['id'] ?? null;
        } elseif ($type === 'audio' && $request->hasFile('file')) {
            [$storagePath, $mediaMime, $mediaFilename, $mediaUrl] = $this->handleUpload($request, 'audio');
            // Envia via base64 direto ao WAHA
            $absolutePath = storage_path('app/public/' . $storagePath);
            $result       = $waha->sendVoiceBase64($chatId, $absolutePath, $mediaMime);
            if (isset($result['error'])) {
                return response()->json(['error' => 'Falha ao enviar áudio no WhatsApp: ' . ($result['body'] ?? 'erro desconhecido')], 422);
            }
            $wahaMessageId = $result['id'] ?? null;
        } elseif ($type === 'document' && $request->hasFile('file')) {
            [$storagePath, $mediaMime, $mediaFilename, $mediaUrl] = $this->handleUpload($request, 'docs');
            $absolutePath = storage_path('app/public/' . $storagePath);
            $result       = $waha->sendFileBase64($chatId, $absolutePath, $mediaMime, $mediaFilename, $body);
            if (isset($result['error'])) {
                return response()->json(['error' => 'Falha ao enviar arquivo no WhatsApp: ' . ($result['body'] ?? 'erro desconhecido')], 422);
            }
            $wahaMessageId = $result['id'] ?? null;
        } else {
            return response()->json(['error' => 'Tipo inválido ou arquivo ausente'], 422);
        }

        $message = WhatsappMessage::create([
            'tenant_id'       => auth()->user()->tenant_id,
            'conversation_id' => $conversation->id,
            'waha_message_id' => $wahaMessageId,
            'direction'       => 'outbound',
            'type'            => $type,
            'body'            => $body ?: null,
            'media_url'       => $mediaUrl,
            'media_mime'      => $mediaMime,
            'media_filename'  => $mediaFilename,
            'user_id'         => auth()->id(),
            'ack'             => $type === 'note' ? 'delivered' : 'sent',
            'sent_at'         => now(),
        ]);

        // Atualizar última mensagem da conversa
        if ($type !== 'note') {
            $conversation->update(['last_message_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => [
                'id'            => $message->id,
                'direction'     => 'outbound',
                'type'          => $type,
                'body'          => $message->body,
                'media_url'     => $message->media_url,
                'media_mime'    => $message->media_mime,
                'media_filename'=> $message->media_filename,
                'ack'           => $message->ack,
                'is_deleted'    => false,
                'sent_at'       => $message->sent_at->toISOString(),
                'user_name'     => auth()->user()->name,
            ],
        ]);
    }

    public function react(WhatsappConversation $conversation, Request $request): JsonResponse
    {
        $wahaMessageId = $request->input('waha_message_id');
        $emoji         = $request->input('emoji', '');

        $instance = WhatsappInstance::first();
        if (! $instance || $instance->status !== 'connected') {
            return response()->json(['error' => 'WhatsApp não conectado'], 422);
        }

        $waha = new WahaService($instance->session_name);
        $waha->sendReaction($wahaMessageId, $emoji);

        WhatsappMessage::create([
            'tenant_id'       => auth()->user()->tenant_id,
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'type'            => 'reaction',
            'reaction_data'   => ['emoji' => $emoji, 'reactedToMessageId' => $wahaMessageId],
            'user_id'         => auth()->id(),
            'ack'             => 'sent',
            'sent_at'         => now(),
        ]);

        return response()->json(['success' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function handleUpload(Request $request, string $subdir): array
    {
        $file     = $request->file('file');
        $path     = $file->store("whatsapp/{$subdir}", 'public');
        $url      = Storage::disk('public')->url($path);
        $mime     = $file->getMimeType();
        $filename = $file->getClientOriginalName();

        return [$path, $mime, $filename, $url];
    }
}
