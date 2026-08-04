<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MockIgsEvent;
use Illuminate\Http\Request;

class IgsController extends Controller
{
    public function index(Request $request)
    {
        $events = MockIgsEvent::with('order.subscription.customer')
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $totalCommission = MockIgsEvent::where('status', 'sent')
                                       ->whereNull('reversed_at')
                                       ->sum('commission');

        return view('admin.igs.index', compact('events', 'totalCommission'));
    }
}
