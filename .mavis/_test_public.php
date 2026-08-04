<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$token = config("services.mercadopago.access_token");
$appUrl = config("app.url");
echo "APP_URL: $appUrl\n\n";

$body = [
    "reason" => "TEST Promarine sandbox",
    "external_reference" => "test-" . uniqid(),
    "payer_email" => "test_" . uniqid() . "@invalid.local",
    "back_url" => $appUrl . "/test/return",
    "auto_recurring" => [
        "frequency" => 1,
        "frequency_type" => "months",
        "transaction_amount" => 100.50,
        "currency_id" => "ARS",
    ],
    "status" => "pending",
    "notification_url" => $appUrl . "/webhooks/mercadopago",
];

$ch = curl_init("https://api.mercadopago.com/preapproval");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($body),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer " . $token,
        "X-Idempotency-Key: " . uniqid("test_", true),
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n";