<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true);
        return [
            'name'               => $name,
            'slug'               => Str::slug($name) . '-' . $this->faker->unique()->numerify('###'),
            'short_description'  => $this->faker->sentence(),
            'reference_price'    => $this->faker->randomFloat(2, 1000, 10000),
            'subscription_price' => $this->faker->randomFloat(2, 800, 9000),
            'saving_percent'     => $this->faker->numberBetween(5, 20),
            'enabled'            => true,
            'featured'           => false,
            'is_mock'            => true,
            'shopify_product_id' => 'mock_' . $this->faker->numerify('########'),
        ];
    }
}
