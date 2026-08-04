<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopifySyncRun;
use App\Services\{AuditService, ShopifySyncService};
use Illuminate\Http\Request;

class ShopifySyncController extends Controller
{
    public function __construct(
        private ShopifySyncService $syncService,
        private AuditService $audit
    ) {}

    public function index()
    {
        $runs = ShopifySyncRun::with('creator')
                              ->orderByDesc('created_at')
                              ->paginate(20);

        $lastRuns = ShopifySyncRun::orderByDesc('finished_at')
                                  ->get()
                                  ->groupBy('entity_type')
                                  ->map(fn ($g) => $g->first());

        return view('admin.shopify.index', compact('runs', 'lastRuns'));
    }

    public function run(Request $request)
    {
        $request->validate([
            'entity_type' => 'required|in:inventory,orders,products',
            'direction'   => 'nullable|in:shopify_to_local,local_to_shopify',
        ]);

        $run = $this->syncService->run(
            $request->entity_type,
            $request->input('direction', 'shopify_to_local'),
            auth()->user()
        );

        $this->audit->log('shopify_sync.executed', $run, null, ['entity' => $run->entity_type, 'status' => $run->status]);

        return redirect()->route('admin.shopify.show', $run)
                         ->with('success', "Sync [{$run->entity_type}] ejecutado: {$run->status}");
    }

    public function show(ShopifySyncRun $run)
    {
        $run->load(['items', 'creator']);
        return view('admin.shopify.show', compact('run'));
    }
}
