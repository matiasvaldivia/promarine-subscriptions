<?php
// Crear usuarios de prueba en MercadoPago
// Requiere Access Token de PRODUCCIÓN o TEST según la doc
$accessToken = 'TEST-1105001575611280-121118-e942e9407baadaab8de88e8436573618-499466249';

echo "=== Crear usuarios de prueba en MP ===" . PHP_EOL;

// Crear usuario de prueba COMPRADOR (payer)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/users/test_user');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['site_id' => 'MLA']));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $code" . PHP_EOL;
if ($error) echo "cURL Error: $error" . PHP_EOL;

$data = json_decode($response, true);
echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL;
