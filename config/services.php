<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'facebook' => [
        'client_id'                    => env('FACEBOOK_CLIENT_ID'),
        'client_secret'                => env('FACEBOOK_CLIENT_SECRET'),
        'redirect'                     => env('FACEBOOK_REDIRECT_URI'),
        'leadgen_redirect'             => env('FACEBOOK_LEADGEN_REDIRECT_URI'),
        'leadgen_webhook_verify_token' => env('FACEBOOK_LEADGEN_WEBHOOK_VERIFY_TOKEN', 'syncro_fb_leadgen_verify_2026'),
        'api_version'                  => env('FACEBOOK_API_VERSION', 'v21.0'),
    ],

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI'),
    ],

    'waha' => [
        'base_url'       => env('WAHA_BASE_URL', 'http://localhost:3000'),
        'api_key'        => env('WAHA_API_KEY', ''),
        'webhook_secret' => env('WAHA_WEBHOOK_SECRET', ''),
    ],

    'whatsapp_cloud' => [
        'app_id'       => env('WHATSAPP_CLOUD_APP_ID', env('FACEBOOK_CLIENT_ID')),
        'app_secret'   => env('WHATSAPP_CLOUD_APP_SECRET', env('FACEBOOK_CLIENT_SECRET')),
        'config_id'    => env('WHATSAPP_CLOUD_CONFIG_ID'),
        'verify_token' => env('WHATSAPP_CLOUD_VERIFY_TOKEN', 'syncro_wa_cloud_verify_2026'),
        'api_version'  => env('WHATSAPP_CLOUD_API_VERSION', 'v21.0'),
        'redirect'     => env('WHATSAPP_CLOUD_REDIRECT'),
    ],

    'instagram' => [
        'client_id'            => env('INSTAGRAM_CLIENT_ID'),
        'client_secret'        => env('INSTAGRAM_CLIENT_SECRET'),
        'redirect'             => env('INSTAGRAM_REDIRECT_URI'),
        'webhook_verify_token' => env('INSTAGRAM_WEBHOOK_VERIFY_TOKEN', 'plataforma360_instagram_verify_2026'),
    ],

    'asaas' => [
        'url'           => env('ASAAS_API_URL', 'https://sandbox.asaas.com/api/v3'),
        'key'           => env('ASAAS_API_KEY'),
        'webhook_token' => env('ASAAS_WEBHOOK_TOKEN'),
    ],

    'stripe' => [
        'key'            => env('STRIPE_KEY'),
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'agno' => [
        'internal_token' => env('AGNO_INTERNAL_TOKEN', ''),
    ],

    'openai_extraction' => [
        'key'   => env('OPENAI_API_KEY', ''),
        'model' => env('OPENAI_EXTRACTION_MODEL', 'gpt-4o-mini'),
    ],

    'elevenlabs' => [
        'api_key'  => env('ELEVENLABS_API_KEY', ''),
        'voice_id' => env('ELEVENLABS_VOICE_ID', ''),
        'model_id' => env('ELEVENLABS_MODEL_ID', 'eleven_multilingual_v2'),
    ],

];
