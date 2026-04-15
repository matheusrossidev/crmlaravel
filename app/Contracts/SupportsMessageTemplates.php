<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contrato segregado (ISP): implementado SÓ pelos services cujo provider
 * suporta Message Templates HSM da Meta. Hoje isso é exclusivo Cloud API —
 * WAHA não suporta e portanto NÃO implementa essa interface.
 *
 * Quem consome deve fazer type-check antes:
 *
 *     $service = WhatsappServiceFactory::for($instance);
 *     if ($service instanceof SupportsMessageTemplates) {
 *         $service->sendTemplate(...);
 *     }
 *
 * Isso respeita LSP (subclasse não precisa "fingir" método que não suporta) e
 * ISP (clientes que só enviam texto não dependem dessa interface).
 */
interface SupportsMessageTemplates
{
    /**
     * Envia mensagem via Message Template HSM pré-aprovado pela Meta.
     *
     * @param  string  $chatId         número do destinatário (só dígitos, E.164 sem +)
     * @param  string  $templateName   nome exato registrado na Meta (snake_case)
     * @param  string  $language       código BCP-47 (ex: pt_BR, en_US)
     * @param  array   $components     payload já formatado conforme spec da Meta
     * @return array                   mesmo shape dos outros métodos: ['id' => ?, 'error' => ?, ...]
     */
    public function sendTemplate(
        string $chatId,
        string $templateName,
        string $language,
        array $components,
    ): array;
}
