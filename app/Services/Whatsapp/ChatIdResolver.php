<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Support\PhoneNormalizer;

/**
 * Resolve o chatId correto pra cada provider de WhatsApp.
 *
 * Padrão DIP: quem envia mensagem NÃO precisa saber se é WAHA ou Cloud API nem
 * se o sufixo é `@c.us`, `@g.us` ou nenhum. Chama:
 *
 *     $chatId = app(ChatIdResolver::class)->for($instance, $conv->phone, $conv->is_group);
 *
 * E recebe o formato certo:
 *   - WAHA 1:1  →  "5511999999999@c.us"
 *   - WAHA LID  →  "36576092528787@lid"  (se histórico da conversa mostra LID)
 *   - WAHA grupo → "5511999999999@g.us"
 *   - Cloud API  → "5511999999999"       (puro, sem sufixo)
 *
 * Bug histórico que isso resolve: vários callers faziam `$phone . '@c.us'` direto,
 * funcionando no Cloud só porque `WhatsappCloudService::normalizeChatId()` strippa
 * sufixo. Violava SRP/DIP — agora centralizado aqui.
 */
class ChatIdResolver
{
    /**
     * @param WhatsappInstance $instance      instância que vai enviar (define o provider)
     * @param string           $phone         número cru (com ou sem +, com ou sem formatação)
     * @param bool             $isGroup       se é grupo (WAHA usa @g.us)
     * @param WhatsappConversation|null $conv se passada, tenta preservar LID do histórico pra WAHA GOWS
     */
    public function for(
        WhatsappInstance $instance,
        string $phone,
        bool $isGroup = false,
        ?WhatsappConversation $conv = null,
    ): string {
        // Cloud API: só o número puro, sem sufixo. WhatsappCloudService::normalizeChatId()
        // ainda strippa defensivamente, mas aqui entregamos limpo na origem.
        if ($instance->isCloudApi()) {
            return $this->toE164($phone);
        }

        // WAHA: precisa de sufixo.
        $e164 = $this->toE164($phone);

        if ($isGroup) {
            return $e164 . '@g.us';
        }

        // WAHA GOWS pode ter registrado o contato com @lid (identificador interno WhatsApp).
        // Se a conversa tem histórico com @lid, preserva; senão @c.us.
        if ($conv) {
            $sampleId = WhatsappMessage::where('conversation_id', $conv->id)
                ->whereNotNull('waha_message_id')
                ->where('direction', 'inbound')
                ->latest('sent_at')
                ->value('waha_message_id');

            // Format do waha_message_id: "{true|false}_{jid}_{messageId}"
            // Ex: "false_36576092528787@lid_3EB0xxx"
            if ($sampleId && preg_match('/^(?:true|false)_(.+@[\w.]+)_/', $sampleId, $m)) {
                $jid = $m[1];
                if (str_ends_with($jid, '@lid')) {
                    $lidNumeric = preg_replace('/[:@].+$/', '', $jid);
                    return $lidNumeric . '@lid';
                }
            }
        }

        return $e164 . '@c.us';
    }

    /**
     * Garante um E.164 limpo (sem +, sem formatação).
     * Fallback: se PhoneNormalizer falhar, strippa não-dígitos como último recurso.
     */
    private function toE164(string $phone): string
    {
        $normalized = PhoneNormalizer::toE164($phone);
        if ($normalized !== null) {
            return $normalized;
        }

        // Fallback defensivo: tira tudo que não é dígito.
        return preg_replace('/\D/', '', $phone);
    }
}
