<?php

namespace Database\Factories;

use App\Models\MockPayment;
use App\Models\MockSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class MockPaymentFactory extends Factory
{
    protected $model = MockPayment::class;

    public function definition(): array
    {
        return [
            'mock_subscription_id' => MockSubscription::factory(),
            'provider_payment_id'  => 'mock_payment_' . bin2hex(random_bytes(6)),
            'status'               => 'approved',
            'amount'               => 93600.00,
            'currency'             => 'ARS',
            'idempotency_key'      => 'checkout-' . bin2hex(random_bytes(8)),
            'is_mock'              => true,
            'environment'          => 'local',
            // billing_cycle, approved_at, etc. son nullable y se agregan por migration admin
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved']);
    }

    public function rejected(): static
    {
        return $this->state(['status' => 'rejected']);
    }

    public function refunded(): static
    {
        return $this->state(['status' => 'refunded']);
    }
}
