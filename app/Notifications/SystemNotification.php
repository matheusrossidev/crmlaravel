<?php

declare(strict_types=1);

namespace App\Notifications;

class SystemNotification extends BaseNotification
{
    protected string $notificationType = 'master_notification';

    public function __construct(string $title, string $body, ?string $url = null)
    {
        parent::__construct($title, $body, $url);
    }
}
