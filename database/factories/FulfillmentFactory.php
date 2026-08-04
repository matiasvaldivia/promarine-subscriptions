<?php

namespace Database\Factories;

use App\Models\Fulfillment;
use Illuminate\Database\Eloquent\Factories\Factory;

class FulfillmentFactory extends Factory
{
    protected $model = Fulfillment::class;

    public function definition(): array
    {
        return [
            'mock_order_id'           => 1,
            'external_fulfillment_id' => 'mock_fulfillment_' . bin2hex(random_bytes(6)),
            'status'                  => 'pending',
            'carrier'                 => null,
            'tracking_number'         => null,
            'tracking_url'            => null,
            'is_mock'                 => true,
            'environment'             => 'local',
        ];
    }

    public function shipped(): static
    {
        $tracking = 'AR' . $this->faker->numerify('##########') . 'AR';
        return $this->state([
            'status'          => 'shipped',
            'carrier'         => $this->faker->randomElement(['OCA', 'Andreani', 'Correo Argentino']),
            'tracking_number' => $tracking,
            'tracking_url'    => "https://tracking.example.com/{$tracking}",
            'prepared_at'     => now()->subDays(3),
            'shipped_at'      => now()->subDays(2),
        ]);
    }

    public function delivered(): static
    {
        $tracking = 'AR' . $this->faker->numerify('##########') . 'AR';
        return $this->state([
            'status'          => 'delivered',
            'carrier'         => $this->faker->randomElement(['OCA', 'Andreani', 'Correo Argentino']),
            'tracking_number' => $tracking,
            'tracking_url'    => "https://tracking.example.com/{$tracking}",
            'prepared_at'     => now()->subDays(6),
            'shipped_at'      => now()->subDays(5),
            'delivered_at'    => now()->subDays(2),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status'         => 'failed',
            'failure_reason' => 'Dirección incorrecta — no se pudo entregar',
            'failed_at'      => now()->subDays(1),
        ]);
    }
}
