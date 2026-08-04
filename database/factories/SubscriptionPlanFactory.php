<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariant::factory(),
            'name'               => $this->faker->randomElement(['Mensual', 'Bimestral', 'Trimestral']),
            'amount'             => $this->faker->randomFloat(2, 1000, 10000),
            'currency'           => 'ARS',
            'frequency'          => $this->faker->randomElement([30, 60, 90]),
            'frequency_type'     => 'days',
            'shipping_included'  => false,
            'enabled'            => true,
        ];
    }

    public function monthly(): static
    {
        return $this->state(['name' => 'Mensual', 'frequency' => 30]);
    }

    public function bimonthly(): static
    {
        return $this->state(['name' => 'Bimestral', 'frequency' => 60]);
    }

    public function quarterly(): static
    {
        return $this->state(['name' => 'Trimestral', 'frequency' => 90]);
    }
}
