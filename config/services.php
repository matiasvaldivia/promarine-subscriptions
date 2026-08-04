<?php

return [

    'firecrawl' => ['url' => env('FIRECRAWL_API_URL', 'http://100.80.187.55:3002')],

    'mercadopago' => [
        'mode'             => env('MERCADOPAGO_MODE', 'mock'),
        'access_token'     => env('MP_ACCESS_TOKEN'),
        'public_key'       => env('MP_PUBLIC_KEY'),
        'webhook_url'      => env('URL_MP_WEBHOOKS'),
        'webhook_secret'   => env('MP_CLAVE_WEBHOOK'),
        // URL pública para back_url / redirect. En prod = dominio real. En local = ngrok u APP_URL.
        'public_url'       => env('MP_PUBLIC_URL', env('APP_URL')),
        // Email del test user de MP (creado en el panel de desarrolladores de MP).
        // Si está vacío, MP pedirá las credenciales en su pantalla de sandbox.
        'test_payer_email' => env('MP_TEST_PAYER_EMAIL'),
    ],
    'shopify' => ['mode' => env('SHOPIFY_MODE', 'mock')],
    'igs' => ['mode' => env('IGS_MODE', 'mock')],

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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
