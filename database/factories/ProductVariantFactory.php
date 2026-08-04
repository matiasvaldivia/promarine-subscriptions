<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id'      => Product::factory(),
            'name'            => $this->faker->randomElement(['Tamaño Pequeño', 'Tamaño Grande', '150g', '300g', '200g', '400g']),
            'sku'             => strtoupper($this->faker->unique()->lexify('SKU-??????')),
            'presentation'    => $this->faker->randomElement(['150g', '300g', '200g', '400g']),
            'simulated_stock' => 100,
            'enabled'         => true,
        ];
    }
}
