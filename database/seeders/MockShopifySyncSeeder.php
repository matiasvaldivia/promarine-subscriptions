<?php

namespace Database\Seeders;

use App\Models\ShopifySyncItem;
use App\Models\ShopifySyncRun;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MockShopifySyncSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();

        $runs = [
            [
                'entity_type'     => 'inventory',
                'direction'       => 'shopify_to_local',
                'status'          => 'completed',
                'records_read'    => 8,
                'records_created' => 0,
                'records_updated' => 8,
                'records_failed'  => 0,
                'started_at'      => now()->subHours(2),
                'finished_at'     => now()->subHours(2)->addMinutes(1),
            ],
            [
                'entity_type'     => 'orders',
                'direction'       => 'local_to_shopify',
                'status'          => 'completed_with_errors',
                'records_read'    => 10,
                'records_created' => 7,
                'records_updated' => 1,
                'records_failed'  => 2,
                'started_at'      => now()->subHours(5),
                'finished_at'     => now()->subHours(5)->addMinutes(3),
            ],
            [
                'entity_type'     => 'products',
                'direction'       => 'shopify_to_local',
                'status'          => 'completed',
                'records_read'    => 4,
                'records_created' => 0,
                'records_updated' => 4,
                'records_failed'  => 0,
                'started_at'      => now()->subDay(),
                'finished_at'     => now()->subDay()->addSeconds(45),
            ],
        ];

        foreach ($runs as $runData) {
            $run = ShopifySyncRun::create(array_merge($runData, [
                'uuid'       => (string) Str::uuid(),
                'is_mock'    => true,
                'created_by' => $admin?->id,
            ]));

            // Ítems de la sincronización
            for ($i = 0; $i < min($runData['records_read'], 5); $i++) {
                $isFailed = $i < $runData['records_failed'];
                ShopifySyncItem::create([
                    'sync_run_id'  => $run->id,
                    'entity_type'  => $runData['entity_type'],
                    'local_id'     => $i + 1,
                    'external_id'  => 'shopify_ext_' . ($i + 1),
                    'operation'    => $isFailed ? 'fail' : ($i === 0 ? 'create' : 'update'),
                    'status'       => $isFailed ? 'failed' : 'processed',
                    'attempts'     => $isFailed ? 3 : 1,
                    'last_error'   => $isFailed ? 'Connection timeout after 30s' : null,
                    'payload_hash' => md5('payload_' . $i),
                    'processed_at' => $runData['finished_at'],
                ]);
            }
        }

        $this->command->info('✓ 3 Shopify sync runs mock con ítems creados');
    }
}
