<?php

declare(strict_types=1);

namespace App\Notifications;

class CampaignCompletedNotification extends BaseNotification
{
    protected string $notificationType = 'campaign_completed';

    public function __construct(string $campaignName, ?string $url = null)
    {
        parent::__construct(
            'Campanha Finalizada',
            "A campanha \"{$campaignName}\" foi concluída.",
            $url,
        );
    }
}
