<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fulfillment;
use Illuminate\Http\Request;

class FulfillmentAdminController extends Controller
{
    public function index(Request $request)
    {
        $fulfillments = Fulfillment::with('order.subscription.customer')
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.fulfillments.index', compact('fulfillments'));
    }

    public function show(Fulfillment $fulfillment)
    {
        $fulfillment->load(['order.subscription.customer', 'order.statusHistory']);
        return view('admin.fulfillments.show', compact('fulfillment'));
    }
}
