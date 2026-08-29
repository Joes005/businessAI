<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    | Supported options: "openai", "anthropic", "local"
    | Default is "local" (deterministic mock engine) if no API key present.
    */
    'default' => env('AI_PROVIDER', 'local'),

    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model'   => env('OPENAI_MODEL', 'gpt-4o-mini'),
        ],
        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model'   => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
        ],
        'local' => [
            'name' => 'Local Deterministic Intelligence Engine',
        ],
    ],
];
