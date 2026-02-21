<?php

declare(strict_types=1);

namespace App\Services;

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
    public static function loadSystemVars(WhatsappConversation $conv): array
    {
        $lead = $conv->lead_id
            ? Lead::withoutGlobalScope('tenant')
                ->with(['stage', 'tags'])
                ->find($conv->lead_id)
            : null;

        $convCount = WhatsappConversation::withoutGlobalScope('tenant')
            ->where('phone', $conv->phone)
            ->where('tenant_id', $conv->tenant_id)
            ->count();

        $tags = $lead && $lead->relationLoaded('tags')
            ? $lead->tags->pluck('name')->join(', ')
            : '';

        return [
            '$lead_exists'          => $lead ? 'sim' : 'não',
            '$lead_stage_name'      => $lead?->stage?->name ?? '',
            '$lead_stage_id'        => (string) ($lead?->stage_id ?? ''),
            '$lead_source'          => $lead?->source ?? '',
            '$lead_tags'            => $tags,
            '$conversations_count'  => (string) $convCount,
            '$is_returning_contact' => $convCount > 1 ? 'sim' : 'não',
            '$messages_count'       => (string) $conv->messages()->count(),
            '$contact_phone'        => $conv->phone ?? '',
            '$contact_name'         => $conv->contact_name ?? '',
        ];
    }

    /**
     * Merge de variáveis: sistema (read-only) + sessão.
     */
    public static function buildVars(WhatsappConversation $conv): array
    {
        $session = $conv->chatbot_variables ?? [];
        $system  = self::loadSystemVars($conv);

        // Sistema tem precedência na leitura, mas não sobrescreve sessão na escrita
        return array_merge($session, $system);
    }
}
