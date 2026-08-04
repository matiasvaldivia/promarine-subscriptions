<?php
echo "PHP curl test to MercadoPago API" . PHP_EOL;
$token = getenv('MP_ACCESS_TOKEN') ?: 'TEST-1105001575611280-121118-e942e9407baadaab8de88e8436573618-499466249';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/v1/preapproval/search?limit=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$response = curl_exec($ch);
$error = curl_error($ch);
$errno = curl_errno($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "curl error ($errno): $error" . PHP_EOL;
echo "http code: $code" . PHP_EOL;
if ($response) {
    $data = json_decode($response, true);
    echo "MP response: " . ($data['message'] ?? 'OK') . PHP_EOL;
    echo "paging total: " . ($data['paging']['total'] ?? '?') . PHP_EOL;
}
