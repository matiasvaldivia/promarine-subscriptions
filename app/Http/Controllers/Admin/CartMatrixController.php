<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductSubscriptionMatrix;
use App\Services\{AuditService, CartMatrixService};
use Illuminate\Http\Request;

class CartMatrixController extends Controller
{
    public function __construct(
        private CartMatrixService $matrix,
        private AuditService $audit
    ) {}

    public function index()
    {
        $rows    = $this->matrix->all();
        $summary = $this->matrix->summary();
        return view('admin.cart-matrix.index', compact('rows', 'summary'));
    }

    public function update(Request $request, ProductSubscriptionMatrix $row)
    {
        $request->validate([
            'base_price'     => 'nullable|numeric|min:0',
            'discount_value' => 'nullable|numeric|min:0|max:100',
            'status'         => 'nullable|in:active,inactive,archived',
            'shipping_included'     => 'nullable|boolean',
            'pause_allowed'         => 'nullable|boolean',
            'cancellation_allowed'  => 'nullable|boolean',
        ]);

        $before = $row->toArray();
        $updated = $this->matrix->updateRow($row, $request->only([
            'base_price', 'discount_type', 'discount_value', 'status',
            'shipping_included', 'pause_allowed', 'cancellation_allowed',
        ]));

        $this->audit->log('cart_matrix.updated', $row, $before, $updated->toArray());

        return back()->with('success', 'Fila de matriz actualizada.');
    }
}
