<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Interface comum pros 3 models de conversa (WhatsApp, Instagram, Website).
 *
 * Permite que codigo generico itere/manipule conversas sem precisar
 * de `if instanceof` espalhado. Cada model continua sendo uma tabela
 * separada — a interface so padroniza o vocabulario de leitura.
 */
interface ConversationContract
{
    /** Retorna 'whatsapp' | 'instagram' | 'website'. */
    public function getChannelName(): string;

    /** Nome do contato (pode ser null se ainda nao identificado). */
    public function getContactName(): ?string;

    /** Telefone do contato — null pra canais que nao tem (Instagram). */
    public function getContactPhone(): ?string;

    /** URL da foto/avatar do contato — null se nao disponivel. */
    public function getContactPictureUrl(): ?string;

    /** Label legivel pra UI: nome + canal (ou identificador fallback). */
    public function getDisplayLabel(): string;
}
