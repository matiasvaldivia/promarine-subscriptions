<?php

namespace App\Services;

interface ShopifyGatewayInterface
{
    // ── Compatibilidad hacia atrás ──────────────────────────────────
    /** @deprecated Use createOrder() instead */
    public function createPaidOrder(array $data): GatewayResult;
    public function getProduct(string $id): GatewayResult;
    public function getInventory(string $variantId): GatewayResult;

    // ── Productos ──────────────────────────────────────────────────
    public function getProducts(array $filters = []): GatewayResult;

    // ── Inventario ────────────────────────────────────────────────
    public function getInventoryLevels(array $variantIds = []): GatewayResult;

    // ── Pedidos ───────────────────────────────────────────────────
    public function getOrders(array $filters = []): GatewayResult;
    public function getOrder(string $externalId): GatewayResult;
    public function createOrder(array $payload): GatewayResult;
    public function updateOrder(string $externalId, array $payload): GatewayResult;

    // ── Fulfillments ──────────────────────────────────────────────
    public function getFulfillments(string $orderExternalId): GatewayResult;
}
