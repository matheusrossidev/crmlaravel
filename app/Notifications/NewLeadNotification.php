<?php

declare(strict_types=1);

namespace App\Notifications;

class NewLeadNotification extends BaseNotification
{
    protected string $notificationType = 'new_lead';

    public function __construct(string $leadName, ?string $url = null)
    {
        parent::__construct(
            'Novo Lead',
            "Lead \"{$leadName}\" foi criado.",
            $url,
        );
    }
}
