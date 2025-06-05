<?php

return [

    'paths' => ['api/*', '*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://127.0.0.0',
        'http://denr_queuing.test:5173',
        'http://10.201.12.207:8000',
        'http://10.201.12.207:5173'
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
