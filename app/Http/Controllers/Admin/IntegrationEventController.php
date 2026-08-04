<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationEvent;
use Illuminate\Http\Request;

class IntegrationEventController extends Controller
{
    public function index(Request $request)
    {
        $events = IntegrationEvent::when($request->input('integration'), fn ($q, $i) => $q->where('integration', $i))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.integration-events.index', compact('events'));
    }
}
