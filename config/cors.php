<?php

return [

    'paths' => [
        'api/*',
        'login',
        'logout',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'https://apiapk.tudealer.app',
        'http://10.4.166.163:8000', 
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // 🔥 IMPORTANTE: con JWT normalmente va en false
    'supports_credentials' => false,

];