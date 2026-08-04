<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLevel;
use App\Services\{AuditService, InventoryService};
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        private InventoryService $inventory,
        private AuditService $audit
    ) {}

    public function index()
    {
        $levels = InventoryLevel::with(['variant.product', 'location'])
                                ->orderBy('sync_status')
                                ->paginate(20);
        $summary = $this->inventory->summary();
        return view('admin.inventory.index', compact('levels', 'summary'));
    }

    public function adjust(Request $request, InventoryLevel $level)
    {
        $request->validate([
            'delta'  => 'required|integer|min:-9999|max:9999',
            'reason' => 'nullable|string|max:300',
        ]);

        $before = $level->available_quantity;
        $this->inventory->adjust($level, $request->integer('delta'), $request->input('reason', 'Ajuste manual'));
        $this->audit->log('inventory.adjusted', $level, ['qty' => $before], ['qty' => $level->fresh()->available_quantity]);

        return back()->with('success', "Stock ajustado: {$before} → {$level->fresh()->available_quantity}");
    }

    public function sync(InventoryLevel $level)
    {
        // Simula re-sync individual desde Shopify
        $level->recalculateSyncStatus();
        $this->audit->log('inventory.synced', $level);
        return back()->with('success', 'Estado de stock re-sincronizado (mock).');
    }
}
