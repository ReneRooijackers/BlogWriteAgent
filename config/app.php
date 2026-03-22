<?php

declare(strict_types=1);

return [
    'app_name' => 'Blog AI',
    'env' => env('APP_ENV', 'local'),
    'url' => env('APP_URL', 'http://localhost:8000'),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
        'endpoint' => 'https://api.openai.com/v1/chat/completions',
        'timeout' => 120,
    ],

    'storage' => [
        'json_db' => base_path('storage/data/blogs.json'),
        'approved_dir' => base_path('storage/blogs'),
        'log_file' => base_path('storage/logs/app.log'),
    ],
];