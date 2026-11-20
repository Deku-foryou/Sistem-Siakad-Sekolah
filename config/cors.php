<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    /*
    | Fase 1: API hanya untuk client Flutter (native; tidak tunduk pada CORS).
    | Browser cross-origin hanya diizinkan untuk origin eksplisit yang dicantumkan
    | di sini (daftar origin, tanpa wildcard '*'). Tidak ada ketergantungan pada
    | ENV: daftar bersifat tetap (kecuali ditambahkan secara sadar).
    |
    | Spread: salah satu dari kedua origin Flutter Web lokal ini
    | (localhost:8080 atau 127.0.0.1:8080).
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:8080',
        'http://127.0.0.1:8080',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
