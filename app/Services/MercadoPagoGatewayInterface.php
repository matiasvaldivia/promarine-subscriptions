<?php
namespace App\Services;
interface MercadoPagoGatewayInterface { public function createSubscription(array $data): GatewayResult; public function getSubscription(string $id): GatewayResult; public function pauseSubscription(string $id): GatewayResult; public function cancelSubscription(string $id): GatewayResult; public function getPayment(string $id): GatewayResult; }
