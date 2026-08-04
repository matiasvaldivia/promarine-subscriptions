<?php

namespace App\Services;

use App\Models\MockOrder;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private OrderStateMachine $stateMachine) {}

    public function paginated(array $filters = []): LengthAwarePaginator
    {
        $query = MockOrder::with(['subscription.customer', 'payment', 'fulfillment'])
                          ->latest();

        if (!empty($filters['status'])) {
            $query->where('internal_status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $query->where('shopify_order_id', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate(20)->withQueryString();
    }

    public function transition(MockOrder $order, string $to, ?User $actor = null, ?string $reason = null): MockOrder
    {
        DB::transaction(fn () => $this->stateMachine->transition($order, $to, $actor, $reason));
        return $order->fresh(['statusHistory', 'fulfillment']);
    }

    public function allowedTransitions(MockOrder $order): array
    {
        return $this->stateMachine->allowedFrom($order->internal_status ?? 'draft');
    }
}
