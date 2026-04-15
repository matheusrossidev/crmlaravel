<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;

/**
 * Verifica se a "janela de 24h" da Meta está aberta pra uma conversa.
 *
 * Regra Meta pro WhatsApp Cloud API: mensagens de texto livre só podem ser enviadas
 * se o cliente respondeu nas últimas 24h. Fora disso, só Message Template HSM
 * (que cobra por disparo). Doc: https://developers.facebook.com/documentation/business-messaging/whatsapp/messages/send-messages
 *
 * WAHA (não-oficial) não tem essa restrição — sempre retorna janela aberta.
 *
 * Padrão SRP: única responsabilidade é responder "posso mandar texto livre agora?".
 * Todos os módulos (follow-up, nurture, automação) consultam AQUI antes de disparar
 * texto pra evitar:
 *   - Falha silenciosa (Meta rejeita e admin não sabe)
 *   - Custo inesperado (template HSM cobra por envio)
 */
class ConversationWindowChecker
{
    private const WINDOW_HOURS = 24;

    /**
     * True se pode mandar texto livre agora.
     *
     * - WAHA: sempre true (sem janela).
     * - Cloud API: true se última inbound < 24h OR se já existe conversa aberta
     *   por template sent (que reabre a janela até o cliente responder de novo).
     */
    public function isOpen(WhatsappConversation $conv): bool
    {
        if (! $this->isCloudApi($conv)) {
            return true;
        }

        $lastInboundAt = $this->lastInboundAt($conv);
        if (! $lastInboundAt) {
            return false;
        }

        return $lastInboundAt->diffInHours(now()) < self::WINDOW_HOURS;
    }

    /**
     * Quantas horas até a janela fechar. null se já fechou ou se é WAHA (sem janela).
     * Usado pro follow-up "smart" agendar antes de 24h.
     */
    public function hoursUntilClose(WhatsappConversation $conv): ?float
    {
        if (! $this->isCloudApi($conv)) {
            return null;
        }

        $lastInboundAt = $this->lastInboundAt($conv);
        if (! $lastInboundAt) {
            return null;
        }

        $hoursSince = $lastInboundAt->floatDiffInHours(now());
        $remaining = self::WINDOW_HOURS - $hoursSince;

        return $remaining > 0 ? $remaining : null;
    }

    /**
     * Conveniência: a conversa usa Cloud API (via instance associada)?
     * Encapsula o check pra caller não precisar carregar relação.
     */
    public function isCloudApi(WhatsappConversation $conv): bool
    {
        return $conv->instance?->provider === 'cloud_api';
    }

    private function lastInboundAt(WhatsappConversation $conv): ?\Illuminate\Support\Carbon
    {
        $ts = WhatsappMessage::where('conversation_id', $conv->id)
            ->where('direction', 'inbound')
            ->latest('sent_at')
            ->value('sent_at');

        return $ts ? \Illuminate\Support\Carbon::parse($ts) : null;
    }
}
