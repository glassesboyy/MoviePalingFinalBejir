<?php

return [
    'paths' => [
        'api/*', 
        'storage/*', 
        'sanctum/csrf-cookie',
        'storage/app/public/posters/*',
        'public/storage/posters/*',
        '*'
    ],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['*'],
    'max_age' => 0,
    'supports_credentials' => true,
];
