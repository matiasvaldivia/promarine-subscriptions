<?php
require __DIR__ . '/../vendor/autoload.php';

$classes = [
    'MercadoPago\SDK',
    'MercadoPago\MercadoPagoConfig',
    'MercadoPago\Client\Payment\PaymentClient',
    'MercadoPago\Client\Customer\CustomerClient',
    'MercadoPago\Client\Preference\PreferenceClient',
    'MercadoPago\Net\MPHttpClient',
];

echo "=== Autoload test ===\n";
foreach ($classes as $cls) {
    $ok = class_exists($cls);
    echo $cls . ': ' . ($ok ? 'OK' : 'FAIL') . "\n";
}

echo "\n=== SDK version ===\n";
echo 'dx-php version: ' (defined('MercadoPago\SDK::VERSION') ? MercadoPago\SDK::VERSION : 'unknown') . "\n";
