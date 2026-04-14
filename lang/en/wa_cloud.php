<?php

return [
    // Middleware
    'requires_cloud_api' => 'This feature requires an active WhatsApp Cloud API connection. Connect it first on the Integrations page.',

    // Banner
    'token_expired'      => 'Your WhatsApp Cloud token has expired. Reconnect to restore message sending and receiving.',
    'token_invalid'      => 'Your WhatsApp Cloud token is invalid. Reconnect on the Integrations page.',
    'token_expiring'     => 'Your WhatsApp Cloud token expires in :days days. Reconnect to avoid interruption.',

    // Notification
    'notif_subject_expiring' => 'WhatsApp Cloud expiring soon',
    'notif_subject_expired'  => 'WhatsApp Cloud expired — reconnect urgently',
    'notif_subject_invalid'  => 'WhatsApp Cloud invalid — reconnect urgently',
    'notif_cta'              => 'Reconnect now',
];
