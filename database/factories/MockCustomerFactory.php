<?php

namespace Database\Factories;

use App\Models\MockCustomer;
use Illuminate\Database\Eloquent\Factories\Factory;

class MockCustomerFactory extends Factory
{
    protected $model = MockCustomer::class;

    public function definition(): array
    {
        return [
            'uuid'         => $this->faker->uuid(),
            'name'         => $this->faker->name(),
            'email'        => $this->faker->unique()->safeEmail(),
            'phone'        => '011-' . $this->faker->numerify('####-####'),
            'province'     => $this->faker->randomElement(['Buenos Aires', 'Córdoba', 'Santa Fe', 'Mendoza', 'Tucumán']),
            'locality'     => $this->faker->city(),
            'postal_code'  => $this->faker->numerify('####'),
            'address'      => $this->faker->streetName(),
            'address_number'  => $this->faker->buildingNumber(),
            'apartment'    => null,
            'address_reference' => null,
            'status'       => 'active',
            'source'       => 'wizard',
            'is_mock'      => true,
            'environment'  => 'local',
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }

    public function blocked(): static
    {
        return $this->state(['status' => 'blocked']);
    }
}
