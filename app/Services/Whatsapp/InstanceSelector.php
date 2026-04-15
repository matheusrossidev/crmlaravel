<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

use App\Models\WhatsappConversation;
use App\Models\WhatsappInstance;
use Illuminate\Support\Facades\Log;

/**
 * Seleciona qual WhatsappInstance usar em cada envio automático.
 *
 * Antes dessa classe, cada módulo (automação, chatbot, agente, nurture, scheduled)
 * tinha a própria lógica de fallback — alguns chamavam resolvePrimary, outros pegavam
 * a primeira connected, outros exigiam config. Gerava inconsistência.
 *
 * Agora a prioridade é única e explícita:
 *
 *   1. $context['instance_id']         — escolha explícita do user (config de automação)
 *   2. $context['conversation']->instance  — herdada da conversa ativa
 *   3. $context['entity']->instance    — relação da entidade (agent/flow/sequence)
 *   4. WhatsappInstance::resolvePrimary($tenantId) — default do tenant (is_primary ou 1a conectada)
 *   5. null  → caller DEVE logar erro e abortar; não deve fallback pra WahaService::first()
 *
 * Padrão SRP: uma única responsabilidade (escolher instance). Não envia, não persiste.
 */
class InstanceSelector
{
    /**
     * @param int   $tenantId
     * @param array $context Chaves aceitas:
     *   - 'instance_id'   (int|null)               — escolha explícita
     *   - 'conversation'  (WhatsappConversation|null)
     *   - 'entity'        (Model|null) — AiAgent, ChatbotFlow, NurtureSequence; deve ter relação instance()
     *   - 'require_connected' (bool, default true) — só retorna instances status='connected'
     */
    public function selectFor(int $tenantId, array $context = []): ?WhatsappInstance
    {
        $requireConnected = $context['require_connected'] ?? true;

        // 1. Explicit
        if (! empty($context['instance_id'])) {
            $instance = WhatsappInstance::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('id', (int) $context['instance_id'])
                ->first();

            if ($instance && $this->isUsable($instance, $requireConnected)) {
                return $instance;
            }
        }

        // 2. Conversation-inherited
        $conv = $context['conversation'] ?? null;
        if ($conv instanceof WhatsappConversation && $conv->instance_id) {
            $instance = WhatsappInstance::withoutGlobalScope('tenant')
                ->where('id', $conv->instance_id)
                ->first();

            if ($instance && $this->isUsable($instance, $requireConnected)) {
                return $instance;
            }
        }

        // 3. Entity-owned (agente/flow/sequence com relação instance)
        $entity = $context['entity'] ?? null;
        if ($entity && method_exists($entity, 'instance')) {
            $instance = $entity->instance;
            if ($instance instanceof WhatsappInstance && $this->isUsable($instance, $requireConnected)) {
                return $instance;
            }
        }

        // 4. Tenant primary fallback
        $primary = WhatsappInstance::resolvePrimary($tenantId);
        if ($primary && $this->isUsable($primary, $requireConnected)) {
            return $primary;
        }

        // 5. Nenhuma opção serve — loga e devolve null. Caller decide o que fazer.
        Log::channel('whatsapp')->warning('InstanceSelector: nenhuma instância disponível', [
            'tenant_id'     => $tenantId,
            'context_keys'  => array_keys($context),
            'had_explicit'  => ! empty($context['instance_id']),
            'had_conv'      => $conv instanceof WhatsappConversation,
        ]);

        return null;
    }

    private function isUsable(WhatsappInstance $instance, bool $requireConnected): bool
    {
        if (! $requireConnected) {
            return true;
        }
        return $instance->status === 'connected';
    }
}
