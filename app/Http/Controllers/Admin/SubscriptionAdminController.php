<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MockSubscription;
use App\Services\{AuditService, SubscriptionService};
use Illuminate\Http\Request;

class SubscriptionAdminController extends Controller
{
    public function __construct(
        private SubscriptionService $service,
        private AuditService $audit
    ) {}

    public function index(Request $request)
    {
        $subscriptions = MockSubscription::with(['customer', 'plan.variant.product'])
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('q'), fn ($q, $term) =>
                $q->whereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.subscriptions.index', compact('subscriptions'));
    }

    public function show(MockSubscription $subscription)
    {
        $subscription->load(['customer', 'plan.variant.product', 'payments', 'orders.fulfillment', 'notes.user']);
        return view('admin.subscriptions.show', compact('subscription'));
    }

    public function pause(MockSubscription $subscription)
    {
        try {
            $this->service->pause($subscription, auth()->user());
            $this->audit->log('subscription.paused', $subscription);
            return back()->with('success', 'Suscripción pausada.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }
    }

    public function resume(MockSubscription $subscription)
    {
        try {
            $this->service->resume($subscription, auth()->user());
            $this->audit->log('subscription.resumed', $subscription);
            return back()->with('success', 'Suscripción reactivada.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }
    }

    public function cancel(Request $request, MockSubscription $subscription)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);
        try {
            $this->service->cancel($subscription, $request->reason, auth()->user());
            $this->audit->log('subscription.cancelled', $subscription, null, null, $request->reason);
            return back()->with('success', 'Suscripción cancelada.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }
    }
}
