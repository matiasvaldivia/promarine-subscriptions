<?php
echo "PHP DNS test:" . PHP_EOL;
echo "gethostbyname: " . gethostbyname('api.mercadopago.com') . PHP_EOL;

// Force re-test with Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "After Laravel bootstrap:" . PHP_EOL;
echo "gethostbyname: " . gethostbyname('api.mercadopago.com') . PHP_EOL;

use MercadoPago\Client\PreApproval\PreApprovalClient;
use MercadoPago\MercadoPagoConfig;

MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);

try {
    $client = new PreApprovalClient();
    $preapproval = $client->create([
        'reason'             => 'Test Promarine con Laravel',
        'external_reference' => 'laravel-test-' . uniqid(),
        'payer_email'        => 'tamara@promarine.com.ar',
        'back_url'           => 'https://promarine.matiasvaldivia.com.ar/checkout/simulate/test/payment',
        'auto_recurring'     => [
            'frequency'          => 1,
            'frequency_type'     => 'months',
            'transaction_amount' => 12500.0,
            'currency_id'        => 'ARS',
        ],
        'status'           => 'pending',
        'notification_url' => 'https://promarine.matiasvaldivia.com.ar/webhooks/mercadopago',
    ]);

    echo "SUCCESS! init_point: " . ($preapproval->init_point ?? 'NULL') . PHP_EOL;
} catch (\MercadoPago\Exceptions\MPApiException $e) {
    echo "MPApiException " . $e->getApiResponse()->getStatusCode() . ": " . json_encode($e->getApiResponse()->getContent()) . PHP_EOL;
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
}
