<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ConversationContract;
use App\Models\InstagramConversation;
use App\Models\WebsiteConversation;
use App\Models\WhatsappConversation;
use InvalidArgumentException;

/**
 * Resolve uma conversa pelo nome do canal + ID, retornando o model concreto
 * que implementa ConversationContract. Usado pelo endpoint generico de
 * inbox `PUT /chats/inbox/{channel}/{conversation}/contact`.
 */
class ConversationResolver
{
    public const CHANNELS = ['whatsapp', 'instagram', 'website'];

    public function resolve(string $channel, int $id): ?ConversationContract
    {
        return match ($channel) {
            'whatsapp'  => WhatsappConversation::find($id),
            'instagram' => InstagramConversation::find($id),
            'website'   => WebsiteConversation::find($id),
            default     => throw new InvalidArgumentException("Canal invalido: {$channel}"),
        };
    }

    public function isValidChannel(string $channel): bool
    {
        return in_array($channel, self::CHANNELS, true);
    }
}
