<?php

namespace App\Services;

use App\Models\ShopifySyncItem;
use App\Models\ShopifySyncRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShopifySyncService
{
    public function __construct(private ShopifyGatewayInterface $gateway) {}

    /**
     * Crea un sync_run y lo ejecuta de inmediato (sync).
     * Para async: encolar un Job que llame a executeRun().
     */
    public function run(string $entityType, string $direction = 'shopify_to_local', ?User $actor = null): ShopifySyncRun
    {
        $run = ShopifySyncRun::create([
            'uuid'         => (string) Str::uuid(),
            'entity_type'  => $entityType,
            'direction'    => $direction,
            'status'       => 'queued',
            'is_mock'      => true,
            'created_by'   => $actor?->id,
        ]);

        return $this->executeRun($run);
    }

    public function executeRun(ShopifySyncRun $run): ShopifySyncRun
    {
        $run->update(['status' => 'running', 'started_at' => now()]);

        try {
            $result = match ($run->entity_type) {
                'inventory' => $this->syncInventory($run),
                'orders'    => $this->syncOrders($run),
                'products'  => $this->syncProducts($run),
                default     => throw new \RuntimeException("Tipo de sync desconocido: {$run->entity_type}"),
            };

            $status = $run->records_failed > 0 ? 'completed_with_errors' : 'completed';
            $run->update(['status' => $status, 'finished_at' => now()]);
        } catch (\Throwable $e) {
            $run->update([
                'status'        => 'failed',
                'finished_at'   => now(),
                'error_message' => $e->getMessage(),
            ]);
        }

        return $run->fresh(['items']);
    }

    private function syncInventory(ShopifySyncRun $run): void
    {
        $result  = $this->gateway->getInventoryLevels();
        $records = $result->data['records'] ?? [];

        foreach ($records as $record) {
            $run->increment('records_read');
            $run->increment('records_updated');
            ShopifySyncItem::create([
                'sync_run_id'  => $run->id,
                'entity_type'  => 'inventory',
                'local_id'     => $record['variant_id'] ?? null,
                'external_id'  => $record['external_variant_id'] ?? null,
                'operation'    => 'update',
                'status'       => 'processed',
                'attempts'     => 1,
                'payload_hash' => md5(serialize($record)),
                'processed_at' => now(),
            ]);
        }
    }

    private function syncOrders(ShopifySyncRun $run): void
    {
        $result  = $this->gateway->getOrders(['limit' => 50]);
        $records = $result->data['records'] ?? [];

        foreach ($records as $record) {
            $run->increment('records_read');
            $run->increment('records_updated');
            ShopifySyncItem::create([
                'sync_run_id'  => $run->id,
                'entity_type'  => 'orders',
                'local_id'     => $record['local_id'] ?? null,
                'external_id'  => $record['id'] ?? null,
                'operation'    => 'update',
                'status'       => 'processed',
                'attempts'     => 1,
                'payload_hash' => md5(serialize($record)),
                'processed_at' => now(),
            ]);
        }
    }

    private function syncProducts(ShopifySyncRun $run): void
    {
        $result  = $this->gateway->getProducts();
        $records = $result->data['records'] ?? [];

        foreach ($records as $record) {
            $run->increment('records_read');
            $run->increment('records_updated');
            ShopifySyncItem::create([
                'sync_run_id'  => $run->id,
                'entity_type'  => 'products',
                'local_id'     => $record['local_id'] ?? null,
                'external_id'  => $record['id'] ?? null,
                'operation'    => 'update',
                'status'       => 'processed',
                'attempts'     => 1,
                'payload_hash' => md5(serialize($record)),
                'processed_at' => now(),
            ]);
        }
    }
}
