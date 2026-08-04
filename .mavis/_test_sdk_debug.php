<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

MercadoPago\MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
MercadoPago\MercadoPagoConfig::setRuntimeEnviroment(MercadoPago\MercadoPagoConfig::LOCAL);

$webhook = config('services.mercadopago.webhook_url');
$base = rtrim(preg_replace('#/webhooks/mercadopago/?$#', '', $webhook), '/');

$client = new MercadoPago\Client\PreApproval\PreApprovalClient();

$body = [
    'reason' => 'TEST SDK debug',
    'external_reference' => 'test-sdk-' . uniqid(),
    'payer_email' => 'test_' . uniqid() . '@invalid.local',
    'back_url' => $base . '/test/return',
    'auto_recurring' => [
        'frequency' => 1,
        'frequency_type' => 'months',
        'transaction_amount' => 100.50,
        'currency_id' => 'ARS',
    ],
    'status' => 'pending',
    'notification_url' => $webhook,
];

echo "Body: " . json_encode($body) . "\n\n";

try {
    $preapproval = $client->create($body);
    echo "SUCCESS!\n";
    echo "ID: " . $preapproval->id . "\n";
    echo "Status: " . $preapproval->status . "\n";
    echo "Init point: " . ($preapproval->init_point ?? '?') . "\n";
} catch (MercadoPago\Exceptions\MPApiException $e) {
    echo "MPApiException:\n";
    echo "  Status: " . $e->getApiResponse()->getStatusCode() . "\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  Body: " . $e->getApiResponse()->getContent() . "\n";
} catch (Throwable $e) {
    echo "Throwable: " . get_class($e) . "\n";
    echo "  Message: " . $e->getMessage() . "\n";
}
