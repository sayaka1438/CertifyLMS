<?php

declare(strict_types=1);

return [
    'enabled' => env('AI_CHAT_ENABLED', true),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
    ],

    'daily_message_limit' => env('AI_CHAT_DAILY_MESSAGE_LIMIT', 50),

    'history_limit' => env('AI_CHAT_HISTORY_LIMIT', 10),
];
