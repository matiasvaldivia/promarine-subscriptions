<?php

namespace Database\Factories;

use App\Models\InventoryLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryLocationFactory extends Factory
{
    protected $model = InventoryLocation::class;

    public function definition(): array
    {
        return [
            'name'      => 'Depósito Test ' . $this->faker->city(),
            'source'    => 'shopify_mock',
            'is_active' => true,
            'is_mock'   => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
