<?php

declare(strict_types=1);

namespace App\Support;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Normalização de números de telefone via libphonenumber (Google).
 *
 * Centralizado aqui pra ser reutilizado em qualquer lugar que precise enviar
 * pra WAHA, WhatsApp Cloud API, ou validar phones de leads. NÃO duplicar
 * `preg_replace('/\D/', '', $phone)` em código novo — sempre usar este helper.
 *
 * Por que libphonenumber e não regex caseira: a regex `preg_replace('/\D/', '', X)`
 * NÃO sabe diferenciar "(11) 99999-9999" (BR sem DDI, precisa do 55) de
 * "(555) 123-4567" (EUA, NÃO precisa de 55). libphonenumber valida formato real
 * por país e é a referência da indústria (mesma lib que Twilio/WhatsApp usam).
 */
class PhoneNormalizer
{
    /**
     * Converte qualquer formato comum em chatId WAHA.
     *
     *   "(11) 99999-9999"   + defaultRegion=BR → "5511999999999@c.us"
     *   "11 99999-9999"     + defaultRegion=BR → "5511999999999@c.us"
     *   "+55 11 99999-9999"                    → "5511999999999@c.us"
     *   "+1 555 123 4567"                      → "15551234567@c.us"
     *   "+44 7700 900123"                      → "447700900123@c.us"
     *
     * Se o número começa com `+`, libphonenumber detecta o país sozinho.
     * Senão, usa $defaultRegion como fallback (default 'BR').
     *
     * Retorna null se o número for inválido (lib valida formato/tamanho/DDI).
     */
    public static function toWahaChatId(?string $phone, string $defaultRegion = 'BR'): ?string
    {
        $e164 = self::toE164($phone, $defaultRegion);
        return $e164 !== null ? $e164 . '@c.us' : null;
    }

    /**
     * Devolve apenas a parte E.164 sem o `+` e sem o sufixo @c.us.
     * Útil pra WhatsApp Cloud API, Asaas SMS, e outros serviços que não usam
     * o formato chatId do WAHA.
     */
    public static function toE164(?string $phone, string $defaultRegion = 'BR'): ?string
    {
        if (!$phone || trim($phone) === '') {
            return null;
        }

        try {
            $util  = PhoneNumberUtil::getInstance();
            $proto = $util->parse($phone, $defaultRegion);

            if (!$util->isValidNumber($proto)) {
                // Fallback: se já parece um número internacional (só dígitos, 10-15 chars),
                // retorna como está — pode ser formato WAHA sem nono dígito que
                // libphonenumber não reconhece.
                $digits = preg_replace('/\D/', '', $phone);
                if (strlen($digits) >= 10 && strlen($digits) <= 15) {
                    // Remove nono dígito BR se presente
                    if (strlen($digits) === 13 && str_starts_with($digits, '55') && $digits[4] === '9') {
                        $digits = substr($digits, 0, 4) . substr($digits, 5);
                    }
                    return $digits;
                }
                return null;
            }

            // E164 = "+5511999999999"; tiramos o `+`
            $e164 = ltrim($util->format($proto, PhoneNumberFormat::E164), '+');

            // WAHA/WhatsApp no Brasil usa 12 dígitos (sem nono dígito) pra celulares.
            // libphonenumber retorna 13 dígitos (55 + DDD 2dig + 9 + 8dig).
            // Remove o nono dígito (posição 4, o "9" após o DDD) pra ficar 12 dígitos.
            if (strlen($e164) === 13 && str_starts_with($e164, '55') && $e164[4] === '9') {
                $e164 = substr($e164, 0, 4) . substr($e164, 5);
            }

            return $e164;
        } catch (NumberParseException $e) {
            return null;
        }
    }
}
