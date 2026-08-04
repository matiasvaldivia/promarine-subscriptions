<x-admin-shell>
<div class="flex items-center justify-between">
    <h1 class="text-2xl font-black">Productos</h1>
</div>

<div class="card mt-4 overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b text-left text-xs text-slate-400 uppercase tracking-wider">
                <th class="px-4 py-3">Producto</th>
                <th class="px-4 py-3">Variantes</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3">Shopify ID</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($products as $product)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3">
                    <div class="font-semibold">{{ $product->name }}</div>
                    @if($product->primary_variant_label)
                    <div class="text-xs text-slate-400">{{ $product->primary_variant_label }}</div>
                    @endif
                </td>
                <td class="px-4 py-3">{{ $product->variants_count }}</td>
                <td class="px-4 py-3">
                    <span class="badge text-xs {{ $product->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-500' }}">
                        {{ $product->status }}
                    </span>
                </td>
                <td class="px-4 py-3 font-mono text-xs">{{ $product->shopify_product_id ?? '—' }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.products.edit', $product) }}" class="text-[#087f8c] text-xs font-semibold">Editar →</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Sin productos.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $products->links() }}</div>
</x-admin-shell>
