<?php

declare(strict_types=1);

namespace App\Notifications;

class PartnerNotification extends BaseNotification
{
    protected string $notificationType = 'partner_alert';

    public function __construct(string $title, string $body, ?string $url = null)
    {
        parent::__construct($title, $body, $url ?? '/parceiro');
    }
}
