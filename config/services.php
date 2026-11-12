<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Midtrans (Snap)
    |--------------------------------------------------------------------------
    | Konfigurasi dibaca dari environment oleh aplikasi; file .env tidak dibaca
    | di sini. Server Key dan Client Key HARUS diisi di .env lingkungan terkait
    | (sandbox: app.sandbox.midtrans.com / api.sandbox.midtrans.com;
    |  production: app.midtrans.com / api.midtrans.com).
    |
    | MIDTRANS_MODE: sandbox | production (default sandbox — aman).
    | Selama MIDTRANS_SERVER_KEY / MIDTRANS_CLIENT_KEY kosong, gateway dianggap
    | belum terkonfigurasi dan transaksi online tidak bisa dibuat (BLOCKED).
    */
    'midtrans' => [
        'mode' => env('MIDTRANS_MODE', 'sandbox'),
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
    ],

];
