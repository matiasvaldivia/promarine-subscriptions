<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MockPayment;
use Illuminate\Http\Request;

class PaymentAdminController extends Controller
{
    public function index(Request $request)
    {
        $payments = MockPayment::with('subscription.customer')
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    public function show(MockPayment $payment)
    {
        $payment->load(['subscription.customer', 'order.fulfillment']);
        return view('admin.payments.show', compact('payment'));
    }
}
