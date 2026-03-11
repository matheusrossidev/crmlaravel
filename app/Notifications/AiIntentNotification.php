<?php

declare(strict_types=1);

namespace App\Notifications;

class AiIntentNotification extends BaseNotification
{
    protected string $notificationType = 'ai_intent';

    public function __construct(string $contactName, string $intentType, ?string $url = null)
    {
        parent::__construct(
            'Sinal de Intenção',
            "{$contactName}: intenção de {$intentType} detectada.",
            $url,
        );
    }
}
