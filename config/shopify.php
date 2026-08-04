<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shopify Integration Mode
    |--------------------------------------------------------------------------
    | 'mock'  → MockShopifyGateway  (persistente, sin llamadas reales)
    | 'real'  → RealShopifyGateway  (NO activar hasta producción)
    */
    'mode' => env('SHOPIFY_MODE', 'mock'),

    'store_domain'        => env('SHOPIFY_STORE_DOMAIN', ''),
    'admin_access_token'  => env('SHOPIFY_ADMIN_ACCESS_TOKEN', ''),
    'api_version'         => env('SHOPIFY_API_VERSION', '2024-10'),
    'location_id'         => env('SHOPIFY_LOCATION_ID', ''),
    'webhook_secret'      => env('SHOPIFY_WEBHOOK_SECRET', ''),
];
