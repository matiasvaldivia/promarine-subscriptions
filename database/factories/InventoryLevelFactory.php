<?php

namespace Database\Factories;

use App\Models\InventoryLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryLevelFactory extends Factory
{
    protected $model = InventoryLevel::class;

    public function definition(): array
    {
        return [
            'variant_id'          => \App\Models\ProductVariant::factory(),
            'location_id'         => \App\Models\InventoryLocation::factory(),
            'available_quantity'  => 100,
            'reserved_quantity'   => 0,
            'committed_quantity'  => 0,
            'incoming_quantity'   => 0,
            'sync_status'         => 'in_stock',
            'last_synced_at'      => now(),
            'is_mock'             => true,
            'environment'         => 'local',
        ];
    }


    public function inStock(): static
    {
        return $this->state([
            'available_quantity' => $this->faker->numberBetween(50, 200),
            'sync_status'        => 'in_stock',
        ]);
    }

    public function lowStock(): static
    {
        return $this->state([
            'available_quantity' => $this->faker->numberBetween(3, 9),
            'sync_status'        => 'low_stock',
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state([
            'available_quantity' => 0,
            'sync_status'        => 'out_of_stock',
        ]);
    }
}
