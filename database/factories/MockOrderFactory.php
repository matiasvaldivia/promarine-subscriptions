<?php

namespace Database\Factories;

use App\Models\MockOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class MockOrderFactory extends Factory
{
    protected $model = MockOrder::class;

    public function definition(): array
    {
        return [
            'mock_subscription_id' => \App\Models\MockSubscription::factory(),
            'mock_payment_id'      => \App\Models\MockPayment::factory(),
            'shopify_order_id'     => 'mock_order_' . bin2hex(random_bytes(6)),
            'status'               => 'created',
            'internal_status'      => 'payment_approved',
            'financial_status'     => 'paid',
            'fulfillment_status'   => 'unfulfilled',
            'total'                => 93600.00,
            'is_mock'              => true,
            'environment'          => 'local',
        ];
    }


    public function transmitted(): static
    {
        return $this->state([
            'internal_status'    => 'transmitted',
            'fulfillment_status' => 'unfulfilled',
            'transmitted_at'     => now()->subHours(2),
        ]);
    }

    public function delivered(): static
    {
        return $this->state([
            'internal_status'    => 'delivered',
            'fulfillment_status' => 'fulfilled',
            'financial_status'   => 'paid',
            'transmitted_at'     => now()->subDays(7),
            'confirmed_at'       => now()->subDays(6),
            'dispatched_at'      => now()->subDays(5),
            'delivered_at'       => now()->subDays(2),
        ]);
    }

    public function syncError(): static
    {
        return $this->state([
            'internal_status' => 'sync_error',
            'transmitted_at'  => now()->subHours(1),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'internal_status' => 'cancelled',
            'financial_status'=> 'voided',
            'cancelled_at'    => now()->subDays(1),
        ]);
    }
}
