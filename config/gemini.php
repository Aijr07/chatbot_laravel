<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | The API key for the Gemini API. This is read from your .env file.
    |
    */
    'api_key' => env('GEMINI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for the HTTP client.
    |
    */
    'http_client_timeout' => env('GEMINI_HTTP_CLIENT_TIMEOUT', 30),
];