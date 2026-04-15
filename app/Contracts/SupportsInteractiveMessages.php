<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contrato segregado (ISP) pra Interactive Messages do WhatsApp Cloud API:
 * reply buttons (até 3) e list messages.
 *
 * WAHA tem sendList parecido mas NÃO tem reply_buttons no schema Meta — por isso
 * essa interface é implementada só pelo WhatsappCloudService. sendList base
 * continua no WhatsappServiceContract porque ambos suportam.
 *
 * Caller:
 *
 *     if ($service instanceof SupportsInteractiveMessages) {
 *         $service->sendInteractiveButtons($chatId, 'Escolha:', [
 *             ['id' => 'yes', 'title' => 'Sim'],
 *             ['id' => 'no',  'title' => 'Não'],
 *         ]);
 *     }
 */
interface SupportsInteractiveMessages
{
    /**
     * Envia mensagem com botões de resposta rápida (até 3 botões).
     *
     * @param  string       $chatId
     * @param  string       $body     texto principal da mensagem
     * @param  array        $buttons  [['id' => 'yes', 'title' => 'Sim'], ...]   title max 20 chars
     * @param  string|null  $footer   texto secundário opcional (max 60 chars)
     * @param  string|null  $header   header de texto opcional
     * @return array                  mesmo shape: ['id' => ?, 'error' => ?, ...]
     */
    public function sendInteractiveButtons(
        string $chatId,
        string $body,
        array $buttons,
        ?string $footer = null,
        ?string $header = null,
    ): array;
}
