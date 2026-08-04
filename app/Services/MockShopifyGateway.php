<?php

namespace App\Services;

use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\MockOrder;
use App\Models\Product;
use App\Models\ShopifySyncItem;
use App\Models\ShopifySyncRun;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * MockShopifyGateway — Persistente, idempotente, sin llamadas reales a Shopify.
 * Toda operación escribe en DB (shopify_sync_items) y retorna GatewayResult.
 * NUNCA modifica Shopify real.
 */
class MockShopifyGateway implements ShopifyGatewayInterface
{
    // ── Compatibilidad hacia atrás ──────────────────────────────────

    public function createPaidOrder(array $data): GatewayResult
    {
        return $this->createOrder($data);
    }

    public function getProduct(string $id): GatewayResult
    {
        $result = $this->getProducts(['external_id' => $id]);
        $records = $result->data['records'] ?? [];
        return new GatewayResult(
            success: true,
            id: $id,
            status: 'available',
            data: array_merge(['is_mock' => true], $records[0] ?? [])
        );
    }

    public function getInventory(string $variantId): GatewayResult
    {
        $level = InventoryLevel::where('variant_id', $variantId)->first();
        return new GatewayResult(
            success: true,
            id: $variantId,
            status: 'available',
            data: [
                'is_mock'             => true,
                'available_quantity'  => $level?->available_quantity ?? 100,
                'reserved_quantity'   => $level?->reserved_quantity ?? 0,
                'sync_status'         => $level?->sync_status ?? 'in_stock',
            ]
        );
    }

    // ── Productos ──────────────────────────────────────────────────

    public function getProducts(array $filters = []): GatewayResult
    {
        $query = Product::with('variants')->enabled();
        if (!empty($filters['status'])) $query->where('status', $filters['status']);

        $products = $query->get()->map(fn ($p) => [
            'id'          => $p->shopify_product_id ?? 'mock_product_' . $p->id,
            'local_id'    => $p->id,
            'title'       => $p->name,
            'status'      => $p->status,
            'variants'    => $p->variants->pluck('sku')->toArray(),
            'is_mock'     => true,
        ]);

        return new GatewayResult(true, 'products_list', 'ok', [
            'is_mock' => true,
            'records' => $products->toArray(),
            'count'   => $products->count(),
        ]);
    }

    // ── Inventario ────────────────────────────────────────────────

    public function getInventoryLevels(array $variantIds = []): GatewayResult
    {
        $location = InventoryLocation::where('is_active', true)->first();
        $query    = InventoryLevel::with('variant');

        if (!empty($variantIds)) $query->whereIn('variant_id', $variantIds);

        $levels = $query->get()->map(fn ($l) => [
            'variant_id'         => $l->variant_id,
            'external_variant_id'=> $l->variant->shopify_variant_id ?? 'mock_var_' . $l->variant_id,
            'location_id'        => $l->location_id,
            'available'          => $l->available_quantity,
            'reserved'           => $l->reserved_quantity,
            'committed'          => $l->committed_quantity,
            'sync_status'        => $l->sync_status,
            'is_mock'            => true,
        ]);

        $this->recordSyncItem('inventory', null, null, 'update', 'processed', md5(serialize($levels)));

        return new GatewayResult(true, 'inventory_levels', 'ok', [
            'is_mock'     => true,
            'records'     => $levels->toArray(),
            'location_id' => $location?->external_id ?? 'mock_location_001',
        ]);
    }

    // ── Pedidos ───────────────────────────────────────────────────

    public function getOrders(array $filters = []): GatewayResult
    {
        $query = MockOrder::with('subscription.customer');
        if (!empty($filters['status'])) $query->where('internal_status', $filters['status']);
        if (!empty($filters['limit']))  $query->limit($filters['limit']);

        $orders = $query->latest()->get()->map(fn ($o) => $this->mapOrder($o));

        return new GatewayResult(true, 'orders_list', 'ok', [
            'is_mock' => true,
            'records' => $orders->toArray(),
            'count'   => $orders->count(),
        ]);
    }

    public function getOrder(string $externalId): GatewayResult
    {
        $order = MockOrder::where('shopify_order_id', $externalId)->first();
        return new GatewayResult(
            success: (bool) $order,
            id: $externalId,
            status: $order?->internal_status ?? 'not_found',
            data: $order ? $this->mapOrder($order) : ['is_mock' => true]
        );
    }

    public function createOrder(array $payload): GatewayResult
    {
        $externalId   = 'mock_order_' . bin2hex(random_bytes(6));
        $payloadHash  = md5(serialize($payload));

        // Idempotencia por hash de payload
        $existing = ShopifySyncItem::where('payload_hash', $payloadHash)
                                   ->where('operation', 'create')
                                   ->first();
        if ($existing) {
            return new GatewayResult(true, $existing->external_id, 'created', ['is_mock' => true, 'duplicate' => true]);
        }

        $this->recordSyncItem('orders', null, $externalId, 'create', 'processed', $payloadHash);

        return new GatewayResult(true, $externalId, 'created', ['is_mock' => true]);
    }

    public function updateOrder(string $externalId, array $payload): GatewayResult
    {
        $order = MockOrder::where('shopify_order_id', $externalId)->first();
        if (!$order) return new GatewayResult(false, $externalId, 'not_found', ['is_mock' => true]);

        $this->recordSyncItem('orders', $order->id, $externalId, 'update', 'processed', md5(serialize($payload)));

        return new GatewayResult(true, $externalId, 'updated', ['is_mock' => true]);
    }

    // ── Fulfillments ──────────────────────────────────────────────

    public function getFulfillments(string $orderExternalId): GatewayResult
    {
        $order = MockOrder::where('shopify_order_id', $orderExternalId)
                          ->with('fulfillment')
                          ->first();

        $fulfillments = $order?->fulfillment ? [
            [
                'id'             => $order->fulfillment->external_fulfillment_id,
                'status'         => $order->fulfillment->status,
                'tracking_number'=> $order->fulfillment->tracking_number,
                'carrier'        => $order->fulfillment->carrier,
                'is_mock'        => true,
            ]
        ] : [];

        return new GatewayResult(true, $orderExternalId, 'ok', [
            'is_mock'      => true,
            'fulfillments' => $fulfillments,
        ]);
    }

    // ── Helpers privados ──────────────────────────────────────────

    private function mapOrder(MockOrder $o): array
    {
        return [
            'id'               => $o->shopify_order_id,
            'local_id'         => $o->id,
            'financial_status' => $o->financial_status,
            'internal_status'  => $o->internal_status,
            'fulfillment_status'=> $o->fulfillment_status,
            'total'            => $o->total,
            'currency'         => 'ARS',
            'is_mock'          => true,
        ];
    }

    private function recordSyncItem(
        string $entityType,
        ?int $localId,
        ?string $externalId,
        string $operation,
        string $status,
        string $payloadHash
    ): void {
        // Solo registra si hay un sync_run activo en contexto (opcional)
        try {
            $activeRun = ShopifySyncRun::where('status', 'running')
                                       ->where('entity_type', $entityType)
                                       ->latest()
                                       ->first();
            if ($activeRun) {
                ShopifySyncItem::create([
                    'sync_run_id'  => $activeRun->id,
                    'entity_type'  => $entityType,
                    'local_id'     => $localId,
                    'external_id'  => $externalId,
                    'operation'    => $operation,
                    'status'       => $status,
                    'attempts'     => 1,
                    'payload_hash' => $payloadHash,
                    'processed_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("[MockShopifyGateway] Could not record sync item: {$e->getMessage()}");
        }
    }
}
