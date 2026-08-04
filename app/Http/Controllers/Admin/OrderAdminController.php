<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MockOrder;
use App\Services\{AuditService, OrderService};
use Illuminate\Http\Request;

class OrderAdminController extends Controller
{
    public function __construct(
        private OrderService $orders,
        private AuditService $audit
    ) {}

    public function index(Request $request)
    {
        $orders = $this->orders->paginated($request->only(['status', 'search']));
        return view('admin.orders.index', compact('orders'));
    }

    public function show(MockOrder $order)
    {
        $order->load(['subscription.customer', 'payment', 'fulfillment', 'statusHistory.changedBy', 'notes.user']);
        $allowed = $this->orders->allowedTransitions($order);
        return view('admin.orders.show', compact('order', 'allowed'));
    }

    public function transition(Request $request, MockOrder $order)
    {
        $request->validate(['to' => 'required|string', 'reason' => 'nullable|string|max:500']);

        $before = $order->internal_status;

        try {
            $this->orders->transition($order, $request->to, auth()->user(), $request->reason);
            $this->audit->log(
                'order.transition',
                $order,
                ['status' => $before],
                ['status' => $order->fresh()->internal_status],
                "Transición: {$before} → {$request->to}"
            );
            return back()->with('success', "Pedido movido a [{$request->to}].");
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['transition' => $e->getMessage()]);
        }
    }
}
