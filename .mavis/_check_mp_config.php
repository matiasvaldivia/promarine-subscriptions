<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$token = config('services.mercadopago.access_token');
$pubkey = config('services.mercadopago.public_key');
$webhook = config('services.mercadopago.webhook_url');
$secret = config('services.mercadopago.webhook_secret');

echo "MP_ACCESS_TOKEN: " . ($token ? "set (" . strlen($token) . " chars, starts APP_USR-" . (substr($token, 0, 11) === 'APP_USR-' ? 'yes' : 'NO') . ")" : "EMPTY") . "\n";
echo "MP_PUBLIC_KEY: " . ($pubkey ? "set (" . strlen($pubkey) . " chars)" : "EMPTY") . "\n";
echo "URL_MP_WEBHOOKS: " . ($webhook ?: "EMPTY") . "\n";
echo "MP_CLAVE_WEBHOOK: " . ($secret ? "set (" . strlen($secret) . " chars)" : "EMPTY") . "\n";

echo "\nTest classes:\n";
echo "MockMercadoPagoGateway: " . (class_exists('App\Services\MockMercadoPagoGateway') ? 'OK' : 'FAIL') . "\n";
echo "MercadoPagoGateway: " . (class_exists('App\Services\MercadoPagoGateway') ? 'OK' : 'FAIL') . "\n";
echo "ServiceProvider: " . (class_exists('App\Providers\MercadoPagoServiceProvider') ? 'OK' : 'FAIL') . "\n";
echo "Bound interface: " . (app()->bound('App\Services\MercadoPagoGatewayInterface') ? 'YES' : 'NO') . "\n";

$gw = app('App\Services\MercadoPagoGatewayInterface');
echo "Resolved class: " . get_class($gw) . "\n";
