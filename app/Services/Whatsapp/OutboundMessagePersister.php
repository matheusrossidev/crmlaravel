<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

use App\Events\WhatsappMessageCreated;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;

/**
 * Persiste WhatsappMessage síncrono após envio bem-sucedido.
 *
 * Centraliza a criação da row + update do last_message_at + broadcast Reverb —
 * evita que cada caller (chatbot, agente IA, automação, nurture, scheduled)
 * duplique essa lógica com pequenas variações.
 *
 * Bug histórico que isso resolve (B1 do plano Cloud API):
 * O chatbot WAHA dependia do "echo" do webhook pra criar a mensagem no banco.
 * Cloud API NÃO manda echo de outbound — então msgs do chatbot sumiam no Cloud.
 * Agora quem envia é responsável por persistir sync, via este service.
 *
 * Padrão SRP + reusabilidade. OutboundMessagePersister NÃO envia, só persiste.
 */
class OutboundMessagePersister
{
    /**
     * @param WhatsappConversation $conv
     * @param string $type             'text'|'image'|'audio'|'document'|'template'|'interactive'|'reaction'|'note'
     * @param string|null $body        texto exibido (pra template é o "preview" com variables substituídas)
     * @param array $sendResult        retorno do $service->sendX() — contém 'id' (waha_message_id ou cloud_message_id)
     * @param string $sentBy           'human'|'chatbot'|'ai_agent'|'automation'|'scheduled'|'followup'|'event'|'human_phone'
     * @param int|null $sentByAgentId  quando $sentBy='ai_agent' ou 'followup'
     * @param int|null $userId         quando $sentBy='human'
     * @param array $extras            keys opcionais: media_url, media_mime, media_filename, reaction_data
     */
    public function persist(
        WhatsappConversation $conv,
        string $type,
        ?string $body,
        array $sendResult,
        string $sentBy,
        ?int $sentByAgentId = null,
        ?int $userId = null,
        array $extras = [],
    ): WhatsappMessage {
        $isCloud = $conv->instance?->provider === 'cloud_api';
        $messageId = $sendResult['id'] ?? null;

        $payload = [
            'tenant_id'        => $conv->tenant_id,
            'conversation_id'  => $conv->id,
            'direction'        => 'outbound',
            'type'             => $type,
            'body'             => $body,
            'user_id'          => $userId,
            'sent_by'          => $sentBy,
            'sent_by_agent_id' => $sentByAgentId,
            'ack'              => 'sent',
            'sent_at'          => now(),
            'media_url'        => $extras['media_url']      ?? null,
            'media_mime'       => $extras['media_mime']     ?? null,
            'media_filename'   => $extras['media_filename'] ?? null,
            'reaction_data'    => $extras['reaction_data']  ?? null,
        ];

        if ($isCloud) {
            $payload['cloud_message_id'] = $messageId;
        } else {
            $payload['waha_message_id'] = $messageId;
        }

        $message = WhatsappMessage::create($payload);

        // Atualiza timestamp da conversa (pra ela subir no topo da sidebar).
        // updateQuietly pra não disparar observers/events redundantes.
        $conv->updateQuietly(['last_message_at' => now()]);

        // Broadcast Reverb pra atualizar UI em tempo real.
        // tenant_id obrigatório — bug histórico 9f70fd3 que quebrou o broadcast no Cloud.
        try {
            broadcast(new WhatsappMessageCreated($message, $conv->tenant_id))->toOthers();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::channel('whatsapp')->warning(
                'OutboundMessagePersister: broadcast falhou (mensagem salva mesmo assim)',
                ['error' => $e->getMessage(), 'message_id' => $message->id],
            );
        }

        return $message;
    }
}
