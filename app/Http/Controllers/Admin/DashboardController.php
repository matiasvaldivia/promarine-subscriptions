<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{IntegrationEvent, InventoryLevel, MockCustomer, MockOrder, MockPayment, MockSubscription, ShopifySyncRun};

class DashboardController extends Controller
{
    public function index()
    {
        $metrics = [
            // Clientes
            'customers_total'         => MockCustomer::count(),
            'customers_active'        => MockCustomer::where('status', 'active')->count(),
            'customers_inactive'      => MockCustomer::where('status', '!=', 'active')->count(),
            // Suscripciones
            'subscriptions_active'    => MockSubscription::active()->count(),
            'subscriptions_pending'   => MockSubscription::pending()->count(),
            'subscriptions_paused'    => MockSubscription::paused()->count(),
            'subscriptions_cancelled' => MockSubscription::cancelled()->count(),
            // Pedidos
            'orders_new'              => MockOrder::where('internal_status', 'payment_approved')->count(),
            'orders_transmitted'      => MockOrder::transmitted()->count(),
            'orders_delivered'        => MockOrder::delivered()->count(),
            'orders_error'            => MockOrder::withError()->count(),
            // Pagos
            'payments_approved'       => MockPayment::approved()->count(),
            'payments_rejected'       => MockPayment::rejected()->count(),
            // Stock
            'stock_low'               => InventoryLevel::lowStock()->count(),
            'stock_out'               => InventoryLevel::outOfStock()->count(),
            // Sync
            'last_sync'               => ShopifySyncRun::orderByDesc('finished_at')->value('finished_at'),
            'last_sync_status'        => ShopifySyncRun::orderByDesc('finished_at')->value('status'),
        ];

        $recentEvents = IntegrationEvent::latest()->limit(8)->get();
        $recentOrders = MockOrder::with('subscription.customer')
                                 ->whereIn('internal_status', ['sync_error', 'payment_approved'])
                                 ->latest()
                                 ->limit(5)
                                 ->get();

        return view('admin.dashboard.index', compact('metrics', 'recentEvents', 'recentOrders'));
    }
}
