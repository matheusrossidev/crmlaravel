<?php

return [
    // Middleware
    'requires_cloud_api' => 'Esta funcionalidade requer uma conexão WhatsApp Cloud API ativa. Conecte primeiro na página de Integrações.',

    // Banner
    'token_expired'      => 'Seu token do WhatsApp Cloud expirou. Reconecte pra restabelecer envios e recebimentos.',
    'token_invalid'      => 'Seu token do WhatsApp Cloud está inválido. Reconecte na página de Integrações.',
    'token_expiring'     => 'Seu token do WhatsApp Cloud expira em :days dias. Reconecte pra evitar interrupção.',

    // Notification
    'notif_subject_expiring' => 'WhatsApp Cloud expira em breve',
    'notif_subject_expired'  => 'WhatsApp Cloud expirou — reconectar urgente',
    'notif_subject_invalid'  => 'WhatsApp Cloud inválido — reconectar urgente',
    'notif_cta'              => 'Reconectar agora',
];
