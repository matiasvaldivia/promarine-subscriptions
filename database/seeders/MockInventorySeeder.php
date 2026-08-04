<?php

namespace Database\Seeders;

use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class MockInventorySeeder extends Seeder
{
    public function run(): void
    {
        // ── Ubicación Shopify mock ──────────────────────────────────
        $location = InventoryLocation::updateOrCreate(
            ['name' => 'Depósito Promarine (Mock)'],
            [
                'external_id' => 'shopify_location_mock_001',
                'source'      => 'shopify_mock',
                'is_active'   => true,
                'is_mock'     => true,
            ]
        );

        $variants = ProductVariant::all();
        if ($variants->isEmpty()) {
            $this->command->warn('⚠ No hay variantes. Ejecutá ProductSeeder primero.');
            return;
        }

        // Stock por variante con escenarios variados
        $stockScenarios = [
            ['available' => 150, 'reserved' => 5,  'committed' => 10, 'status' => 'in_stock'],
            ['available' => 80,  'reserved' => 0,  'committed' => 5,  'status' => 'in_stock'],
            ['available' => 8,   'reserved' => 0,  'committed' => 0,  'status' => 'low_stock'],
            ['available' => 120, 'reserved' => 10, 'committed' => 8,  'status' => 'in_stock'],
            ['available' => 0,   'reserved' => 0,  'committed' => 0,  'status' => 'out_of_stock'],
            ['available' => 60,  'reserved' => 2,  'committed' => 3,  'status' => 'in_stock'],
            ['available' => 5,   'reserved' => 0,  'committed' => 1,  'status' => 'low_stock'],
            ['available' => 200, 'reserved' => 20, 'committed' => 15, 'status' => 'in_stock'],
        ];

        foreach ($variants as $index => $variant) {
            $scenario = $stockScenarios[$index % count($stockScenarios)];

            InventoryLevel::updateOrCreate(
                ['variant_id' => $variant->id, 'location_id' => $location->id],
                [
                    'available_quantity' => $scenario['available'],
                    'reserved_quantity'  => $scenario['reserved'],
                    'committed_quantity' => $scenario['committed'],
                    'incoming_quantity'  => 0,
                    'sync_status'        => $scenario['status'],
                    'last_synced_at'     => now(),
                    'is_mock'            => true,
                    'environment'        => 'local',
                ]
            );

            // Movimiento inicial de sync
            InventoryMovement::create([
                'variant_id'     => $variant->id,
                'location_id'    => $location->id,
                'type'           => 'sync',
                'quantity'       => $scenario['available'],
                'reference_type' => null,
                'reference_id'   => null,
                'reason'         => 'Sync inicial desde MockShopifyGateway',
                'metadata_json'  => ['source' => 'mock_seeder'],
            ]);
        }

        $this->command->info('✓ Inventario mock: 1 ubicación + ' . $variants->count() . ' niveles de stock');
    }
}
