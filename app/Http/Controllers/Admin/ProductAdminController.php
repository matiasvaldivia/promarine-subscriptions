<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Product, User};
use Illuminate\Http\Request;

class ProductAdminController extends Controller
{
    public function index()
    {
        $products = Product::withCount('variants')
                           ->with('variants.primaryInventoryLevel')
                           ->paginate(20);
        return view('admin.products.index', compact('products'));
    }

    public function edit(Product $product)
    {
        $product->load(['variants.plans', 'variants.inventoryLevels.location']);
        return view('admin.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'status' => 'required|in:active,inactive,archived',
            'enabled'=> 'boolean',
        ]);

        $product->update($request->only(['name', 'status', 'enabled', 'shopify_product_id', 'igs_product_id']));
        return back()->with('success', 'Producto actualizado.');
    }
}
