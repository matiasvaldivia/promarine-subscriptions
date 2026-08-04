<?php
namespace App\Services;
interface ShopifyGatewayInterface { public function createPaidOrder(array $data): GatewayResult; public function getProduct(string $id): GatewayResult; public function getInventory(string $variantId): GatewayResult; }
