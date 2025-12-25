<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Typesense Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to Typesense vector search engine.
    | All values should be set via environment variables.
    |
    */

    'api_key' => env('TYPESENSE_API_KEY', 'xyz'),
    'host' => env('TYPESENSE_HOST', 'localhost'),
    'port' => env('TYPESENSE_PORT', '8108'),
    'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
    'path' => env('TYPESENSE_PATH', ''),

    /*
    |--------------------------------------------------------------------------
    | Connection Settings
    |--------------------------------------------------------------------------
    */

    'connection_timeout' => env('TYPESENSE_CONNECTION_TIMEOUT', 2),
    'healthcheck_interval' => env('TYPESENSE_HEALTHCHECK_INTERVAL', 30),
    'num_retries' => env('TYPESENSE_NUM_RETRIES', 3),
    'retry_interval' => env('TYPESENSE_RETRY_INTERVAL', 1),

    /*
    |--------------------------------------------------------------------------
    | Search Settings
    |--------------------------------------------------------------------------
    */

    'search' => [
        'hybrid_alpha' => env('TYPESENSE_HYBRID_ALPHA', 0.5),
        'default_limit' => env('TYPESENSE_DEFAULT_LIMIT', 10),
    ],
];
