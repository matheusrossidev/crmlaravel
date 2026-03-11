<?php

declare(strict_types=1);

namespace App\Notifications;

class LeadAssignedNotification extends BaseNotification
{
    protected string $notificationType = 'lead_assigned';

    public function __construct(string $leadName, string $assignedBy, ?string $url = null)
    {
        parent::__construct(
            'Lead Atribuído',
            "Lead \"{$leadName}\" foi atribuído a você por {$assignedBy}.",
            $url,
        );
    }
}
