<?php

declare(strict_types=1);

return [
    'provider'        => env('LLM_PROVIDER', 'openai'),
    'api_key'         => env('LLM_API_KEY', ''),
    'model'           => env('LLM_MODEL', 'gpt-4o-mini'),
    'whisper_api_key' => env('WHISPER_API_KEY', env('LLM_API_KEY', '')),
];
