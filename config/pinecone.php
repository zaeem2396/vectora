<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Pinecone API key
    |--------------------------------------------------------------------------
    */
    'api_key' => env('PINECONE_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Index host (e.g. https://xxx.svc.region.pinecone.io)
    |--------------------------------------------------------------------------
    */
    'host' => env('PINECONE_HOST', ''),

    /*
    |--------------------------------------------------------------------------
    | Default namespace
    |--------------------------------------------------------------------------
    */
    'namespace' => env('PINECONE_NAMESPACE', ''),

    /*
    |--------------------------------------------------------------------------
    | HTTP / resilience (wired in Core layer)
    |--------------------------------------------------------------------------
    */
    'http' => [
        'timeout' => (int) env('PINECONE_HTTP_TIMEOUT', 30),
        'connect_timeout' => (int) env('PINECONE_CONNECT_TIMEOUT', 10),
        'retries' => (int) env('PINECONE_HTTP_RETRIES', 3),
        'retry_delay_ms' => (int) env('PINECONE_RETRY_DELAY_MS', 500),
    ],
];
