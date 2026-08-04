<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\MercadoPagoGateway;

echo "=== Test MercadoPagoGateway con back_url pública ===" . PHP_EOL;
echo "MP_PUBLIC_URL: " . config('services.mercadopago.public_url') . PHP_EOL;
echo PHP_EOL;

$gw = new MercadoPagoGateway();
$testRef = 'gw-test-' . uniqid();
$backUrl = rtrim(config('services.mercadopago.public_url'), '/')
           . '/checkout/simulate/' . $testRef . '/payment';

echo "back_url que se enviará a MP: $backUrl" . PHP_EOL . PHP_EOL;

$result = $gw->createSubscription([
    'amount'             => 12500,
    'delivery_frequency' => 30,
    'payer_email'        => 'ana.test.promarine@gmail.com',
    'external_reference' => $testRef,
    'reason'             => 'Promarine · Erizo de mar · Pack mensual',
    'back_url'           => $backUrl,
    'currency'           => 'ARS',
]);

echo "success:     " . ($result->success ? '✓ TRUE' : '✗ FALSE') . PHP_EOL;
echo "status:      " . $result->status . PHP_EOL;
echo "id:          " . $result->id . PHP_EOL;
echo "init_point:  " . ($result->payload['init_point'] ?? 'NULL') . PHP_EOL;
echo "is_mock:     " . (($result->payload['is_mock'] ?? true) ? 'true' : 'false') . PHP_EOL;

if ($result->success) {
    echo PHP_EOL . "=== ¡EXITO! ===" . PHP_EOL;
    echo "👉 " . $result->payload['init_point'] . PHP_EOL;
} else {
    echo PHP_EOL . "=== ERROR ===" . PHP_EOL;
    print_r($result->payload);
}
