<?php
// Test raw del SDK de MP sin Laravel bootstrap
require __DIR__ . '/../vendor/autoload.php';

use MercadoPago\Client\PreApproval\PreApprovalClient;
use MercadoPago\MercadoPagoConfig;

$accessToken = 'TEST-1105001575611280-121118-e942e9407baadaab8de88e8436573618-499466249';

echo "=== Raw MP SDK Test ===" . PHP_EOL;
echo "PHP version: " . PHP_VERSION . PHP_EOL;

// Check DNS
$ip = gethostbyname('api.mercadopago.com');
echo "DNS resolve api.mercadopago.com: $ip" . PHP_EOL;

// Check curl
$ch = curl_init('https://api.mercadopago.com/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$r = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "cURL to api.mercadopago.com: HTTP $code, error: '$err'" . PHP_EOL . PHP_EOL;

// Now test with SDK
MercadoPagoConfig::setAccessToken($accessToken);
MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);

echo "Testing preapproval create..." . PHP_EOL;

try {
    $client = new PreApprovalClient();
    $preapproval = $client->create([
        'reason'             => 'Test Promarine Suscripcion',
        'external_reference' => 'raw-test-' . uniqid(),
        'payer_email'        => 'matiestaenlanet@hotmail.com',
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

    echo "SUCCESS!" . PHP_EOL;
    echo "id:         " . $preapproval->id . PHP_EOL;
    echo "status:     " . $preapproval->status . PHP_EOL;
    echo "init_point: " . ($preapproval->init_point ?? 'NULL') . PHP_EOL;

} catch (\MercadoPago\Exceptions\MPApiException $e) {
    echo "MPApiException:" . PHP_EOL;
    echo "  status:  " . $e->getApiResponse()->getStatusCode() . PHP_EOL;
    echo "  content: " . json_encode($e->getApiResponse()->getContent()) . PHP_EOL;
} catch (\Throwable $e) {
    echo "EXCEPTION (" . get_class($e) . "): " . $e->getMessage() . PHP_EOL;
    $lines = explode("\n", $e->getTraceAsString());
    foreach (array_slice($lines, 0, 8) as $line) echo "  $line" . PHP_EOL;
}
