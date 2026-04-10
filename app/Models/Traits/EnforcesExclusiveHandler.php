<?php

declare(strict_types=1);

namespace App\Models\Traits;

/**
 * Garante que chatbot e agente IA sao mutuamente exclusivos.
 *
 * Se alguem tentar salvar uma conversa com chatbot_flow_id E ai_agent_id
 * populados ao mesmo tempo, DomainException e disparada em vez de salvar
 * silenciosamente. Isso forca o desenvolvedor a limpar um antes de setar o
 * outro — previne bugs onde bot e IA competem pra responder.
 *
 * Aplicado em WhatsappConversation, InstagramConversation, WebsiteConversation.
 */
trait EnforcesExclusiveHandler
{
    public static function bootEnforcesExclusiveHandler(): void
    {
        static::saving(function ($model): void {
            if (! empty($model->chatbot_flow_id) && ! empty($model->ai_agent_id)) {
                throw new \DomainException(
                    "Conversa #{$model->id} nao pode ter chatbot_flow_id ({$model->chatbot_flow_id}) " .
                    "e ai_agent_id ({$model->ai_agent_id}) ao mesmo tempo. " .
                    'Limpe um antes de setar o outro.'
                );
            }
        });
    }
}
