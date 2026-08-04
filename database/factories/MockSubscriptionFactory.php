<?php

namespace Database\Factories;

use App\Models\MockSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MockSubscriptionFactory extends Factory
{
    protected $model = MockSubscription::class;

    public function definition(): array
    {
        return [
            'uuid'                     => (string) Str::uuid(),
            'customer_id'              => \App\Models\MockCustomer::factory(),
            'subscription_plan_id'     => \App\Models\SubscriptionPlan::factory(),
            'provider'                 => 'mercadopago',
            'provider_subscription_id' => 'mock_pre_' . bin2hex(random_bytes(6)),
            'status'                   => 'payment_approved',
            'amount'                   => 93600.00,
            'currency'                 => 'ARS',
            'frequency'                => 30,
            'frequency_type'           => 'days',
            'current_cycle'            => 1,
            'next_billing_at'          => now()->addDays(30),
            'started_at'               => now(),
            'is_mock'                  => true,
            'environment'              => 'local',
            'metadata_json'            => ['source' => 'factory'],
        ];
    }


    public function active(): static
    {
        return $this->state([
            'status'     => 'payment_approved',
            'started_at' => now()->subDays(30),
        ]);
    }

    public function paused(): static
    {
        return $this->state([
            'status'    => 'paused',
            'paused_at' => now()->subDays(5),
        ]);
    }

    public function paymentRejected(): static
    {
        return $this->state([
            'status'    => 'payment_rejected',
            'paused_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status'       => 'cancelled',
            'cancelled_at' => now()->subDays(10),
        ]);
    }
}
