<?php

namespace App\Services;

use App\Models\MockSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function pause(MockSubscription $subscription, ?User $actor = null): void
    {
        $this->assertState($subscription, ['payment_approved', 'authorized', 'active']);

        DB::transaction(function () use ($subscription) {
            $subscription->update([
                'status'    => 'paused',
                'paused_at' => now(),
            ]);
        });
    }

    public function resume(MockSubscription $subscription, ?User $actor = null): void
    {
        $this->assertState($subscription, ['paused']);

        DB::transaction(function () use ($subscription) {
            $subscription->update([
                'status'     => 'payment_approved',
                'resumed_at' => now(),
                'paused_at'  => null,
            ]);
        });
    }

    public function cancel(MockSubscription $subscription, ?string $reason = null, ?User $actor = null): void
    {
        $this->assertState($subscription, ['payment_approved', 'authorized', 'active', 'paused', 'pending']);

        DB::transaction(function () use ($subscription, $reason) {
            $subscription->update([
                'status'       => 'cancelled',
                'cancelled_at' => now(),
                'metadata_json'=> array_merge($subscription->metadata_json ?? [], [
                    'cancellation_reason' => $reason,
                    'cancelled_at'        => now()->toIso8601String(),
                ]),
            ]);
        });
    }

    private function assertState(MockSubscription $subscription, array $allowedStates): void
    {
        if (!in_array($subscription->status, $allowedStates)) {
            throw new \InvalidArgumentException(
                "Suscripción en estado [{$subscription->status}] no puede aplicar esta acción. " .
                "Estados válidos: " . implode(', ', $allowedStates)
            );
        }
    }
}
