<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$token = config('services.mercadopago.access_token');
$email = 'test_payer_' . uniqid() . '@invalid.local';

$body = [
    'reason' => 'TEST Promarine sandbox',
    'external_reference' => 'test-' . uniqid(),
    'payer_email' => $email,
    'back_url' => 'http://localhost:8080/test/return',
    'auto_recurring' => [
        'frequency' => 1,
        'frequency_type' => 'months',
        'transaction_amount' => 100.50,
        'currency_id' => 'ARS',
    ],
    'status' => 'pending',
    'notification_url' => 'https://promarine.matiasvaldivia.com.ar/webhooks/mercadopago',
];

echo "=== Test directo con curl ===\n";
echo "Token: " . substr($token, 0, 15) . "...\n";
echo "Email: $email\n";
echo "Body: " . json_encode($body, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init('https://api.mercadopago.com/preapproval');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($body),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
        'X-Idempotency-Key: ' . uniqid('test_', true),
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Curl Error: " . ($error ?: 'none') . "\n";
echo "Response: $response\n\n";

if ($httpCode === 200 || $httpCode === 201) {
    $data = json_decode($response, true);
    echo "Preapproval ID: " . ($data['id'] ?? '?') . "\n";
    echo "Status: " . ($data['status'] ?? '?') . "\n";
    echo "Init point: " . ($data['init_point'] ?? '?') . "\n";
}
