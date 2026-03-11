<?php

declare(strict_types=1);

namespace App\Notifications;

class WhatsappMessageNotification extends BaseNotification
{
    protected string $notificationType = 'whatsapp_message';

    public function __construct(string $contactName, string $messagePreview, ?string $url = null)
    {
        parent::__construct(
            $contactName,
            mb_substr($messagePreview, 0, 120),
            $url,
        );
    }
}
