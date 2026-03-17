<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InstagramConversation;
use App\Models\Lead;
use App\Models\WhatsappConversation;

class ChatbotVariableService
{
    /**
     * Interpola {{variavel}} e {{$variavel_sistema}} no texto.
     */
    public static function interpolate(string $text, array $vars): string
    {
        return preg_replace_callback(
            '/\{\{(\$?[\w]+)\}\}/',
            fn (array $m) => (string) ($vars[$m[1]] ?? ''),
            $text,
        );
    }

    /**
     * Carrega variáveis de sistema da conversa (somente leitura, prefixo $).
     * Mescladas com chatbot_variables da sessão no início de cada execução.
     */
    public static function loadSystemVars(WhatsappConversation|InstagramConversation $conv): array
    {
        $lead = $conv->lead_id
            ? Lead::withoutGlobalScope('tenant')
                ->with(['stage'])
                ->find($conv->lead_id)
            : null;

        $tags = $lead ? implode(', ', $lead->tags ?? []) : '';

        $vars = [
            '$lead_exists'          => $lead ? 'sim' : 'não',
            '$lead_stage_name'      => $lead?->stage?->name ?? '',
            '$lead_stage_id'        => (string) ($lead?->stage_id ?? ''),
            '$lead_source'          => $lead?->source ?? '',
            '$lead_tags'            => $tags,
            '$messages_count'       => (string) $conv->messages()->count(),
        ];

        if ($conv instanceof WhatsappConversation) {
            $convCount = WhatsappConversation::withoutGlobalScope('tenant')
                ->where('phone', $conv->phone)
                ->where('tenant_id', $conv->tenant_id)
                ->count();
            $vars['$contact_phone'] = $conv->phone ?? '';
            $vars['$contact_name']  = $conv->contact_name ?? '';
        } else {
            $convCount = InstagramConversation::withoutGlobalScope('tenant')
                ->where('igsid', $conv->igsid)
                ->where('tenant_id', $conv->tenant_id)
                ->count();
            $vars['$contact_phone'] = '';
            $vars['$contact_name']  = $conv->contact_name ?? $conv->contact_username ?? '';
        }

        $vars['$conversations_count']  = (string) $convCount;
        $vars['$is_returning_contact'] = $convCount > 1 ? 'sim' : 'não';

        return $vars;
    }

    /**
     * Merge de variáveis: sistema (read-only) + sessão.
     */
    public static function buildVars(WhatsappConversation|InstagramConversation $conv): array
    {
        $session = $conv->chatbot_variables ?? [];
        $system  = self::loadSystemVars($conv);

        return array_merge($session, $system);
    }
}
