<?php

return [
    'api_key' => env('ANTHROPIC_API_KEY'),
    'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
    'timeout' => env('ANTHROPIC_TIMEOUT', 60),

    'models' => [
        'default' => env('ANTHROPIC_DEFAULT_MODEL', 'claude-sonnet-4-20250514'),
        'fast' => env('ANTHROPIC_FAST_MODEL', 'claude-3-5-haiku-latest'),
        'powerful' => env('ANTHROPIC_POWERFUL_MODEL', 'claude-opus-4-1-20250805'),
    ],

    'web_tools' => [
        'search' => [
            'max_uses' => env('ANTHROPIC_SEARCH_MAX_USES', 5),
            'user_location' => [
                'city' => env('ANTHROPIC_USER_CITY'),
                'region' => env('ANTHROPIC_USER_REGION'),
                'country' => env('ANTHROPIC_USER_COUNTRY'),
                'timezone' => env('ANTHROPIC_USER_TIMEZONE'),
            ],
        ],
        'fetch' => [
            'max_uses' => env('ANTHROPIC_FETCH_MAX_USES', 10),
            'max_content_tokens' => env('ANTHROPIC_FETCH_MAX_TOKENS', 100000),
            'citations_enabled' => env('ANTHROPIC_FETCH_CITATIONS', true),
        ],
    ],
];
