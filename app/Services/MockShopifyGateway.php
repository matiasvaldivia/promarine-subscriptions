<?php
namespace App\Services;
class MockShopifyGateway implements ShopifyGatewayInterface { public function createPaidOrder(array $data):GatewayResult{return new GatewayResult(true,'mock_order_'.bin2hex(random_bytes(6)),'created',['is_mock'=>true]);} public function getProduct(string $id):GatewayResult{return new GatewayResult(true,$id,'available',['is_mock'=>true]);} public function getInventory(string $variantId):GatewayResult{return new GatewayResult(true,$variantId,'available',['is_mock'=>true,'stock'=>100]);} }
