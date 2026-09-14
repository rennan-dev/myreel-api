<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter([
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        // dashboard de analytics (roda na 5174 para não conflitar com o myreel-web)
        'http://localhost:5174',
        'http://127.0.0.1:5174',
        env('FRONTEND_URL', ''),
    ])),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
