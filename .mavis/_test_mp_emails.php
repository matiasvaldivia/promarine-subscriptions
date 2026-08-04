<?php
require __DIR__ . '/../vendor/autoload.php';

use MercadoPago\Client\PreApproval\PreApprovalClient;
use MercadoPago\MercadoPagoConfig;

$accessToken = 'TEST-1105001575611280-121118-e942e9407baadaab8de88e8436573618-499466249';

echo "=== Raw MP SDK Test — diferentes emails ===" . PHP_EOL;

MercadoPagoConfig::setAccessToken($accessToken);
MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);

// Lista de emails a probar
$emails = [
    'test_buyer_promarine@testuser.com',     // genérico test
    'tamara@promarine.com.ar',               // email de la empresa
    'buyer_test_12345@test.com',             // inventado
];

foreach ($emails as $email) {
    echo PHP_EOL . "--- Probando: $email ---" . PHP_EOL;
    
    try {
        $client = new PreApprovalClient();
        $preapproval = $client->create([
            'reason'             => 'Test Promarine',
            'external_reference' => 'test-' . uniqid(),
            'payer_email'        => $email,
            'back_url'           => 'https://promarine.matiasvaldivia.com.ar/checkout/test/payment',
            'auto_recurring'     => [
                'frequency'          => 1,
                'frequency_type'     => 'months',
                'transaction_amount' => 12500.0,
                'currency_id'        => 'ARS',
            ],
            'status'           => 'pending',
            'notification_url' => 'https://promarine.matiasvaldivia.com.ar/webhooks/mercadopago',
        ]);

        echo "✓ SUCCESS! id=" . $preapproval->id . " init_point=" . substr($preapproval->init_point ?? 'NULL', 0, 60) . "..." . PHP_EOL;
        echo PHP_EOL . "=== INIT POINT COMPLETO ===" . PHP_EOL;
        echo $preapproval->init_point . PHP_EOL;
        break;

    } catch (\MercadoPago\Exceptions\MPApiException $e) {
        $content = $e->getApiResponse()->getContent();
        echo "✗ MPApiException " . $e->getApiResponse()->getStatusCode() . ": " . ($content['message'] ?? json_encode($content)) . PHP_EOL;
    } catch (\Throwable $e) {
        echo "✗ Exception: " . $e->getMessage() . PHP_EOL;
    }
}
