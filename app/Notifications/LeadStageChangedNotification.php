<?php

declare(strict_types=1);

namespace App\Notifications;

class LeadStageChangedNotification extends BaseNotification
{
    protected string $notificationType = 'lead_stage_changed';

    public function __construct(string $leadName, string $newStageName, ?string $url = null)
    {
        parent::__construct(
            'Lead Mudou de Etapa',
            "Lead \"{$leadName}\" avançou para \"{$newStageName}\".",
            $url,
        );
    }
}
