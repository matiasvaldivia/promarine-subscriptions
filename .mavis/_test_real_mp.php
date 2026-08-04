<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Verificar que se inyecta la implementación REAL
$gw = app(App\Services\MercadoPagoGatewayInterface::class);
echo "Gateway class: " . get_class($gw) . "\n";
echo "Is real: " . ($gw instanceof App\Services\MercadoPagoGateway ? "YES" : "NO") . "\n\n";

echo "=== Test createSubscription con sandbox ===\n";
$result = $gw->createSubscription([
    'amount' => 100.50,
    'currency' => 'ARS',
    'reason' => 'TEST Promarine - tarjeta sandbox',
    'external_reference' => 'test-' . uniqid(),
    'payer_email' => 'test_payer_' . uniqid() . '@invalid.local',
    'delivery_frequency' => 30,
    // NO pasamos back_url para que el gateway use publicBaseUrl()
]);

echo "Success: " . ($result->success ? "YES" : "NO") . "\n";
echo "ID: " . $result->id . "\n";
echo "Status: " . $result->status . "\n";
echo "Payload:\n";
foreach ($result->payload as $k => $v) {
    echo "  $k: $v\n";
}
