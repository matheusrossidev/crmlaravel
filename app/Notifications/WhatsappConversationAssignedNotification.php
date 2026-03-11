<?php

declare(strict_types=1);

namespace App\Notifications;

class WhatsappConversationAssignedNotification extends BaseNotification
{
    protected string $notificationType = 'whatsapp_assigned';

    public function __construct(string $contactName, string $assignedBy, ?string $url = null)
    {
        parent::__construct(
            'Conversa Atribuída',
            "Conversa com {$contactName} atribuída a você por {$assignedBy}.",
            $url,
        );
    }
}
