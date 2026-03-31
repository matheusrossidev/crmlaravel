<?php

declare(strict_types=1);

namespace App\Notifications;

class GoalAlertNotification extends BaseNotification
{
    protected string $notificationType = 'goal_alert';

    public function __construct(string $title, string $body, ?string $url = null)
    {
        parent::__construct($title, $body, $url ?? '/metas');
    }
}
