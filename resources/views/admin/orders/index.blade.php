<x-admin-shell>
<div class="flex items-center justify-between">
    <h1 class="text-2xl font-black">Pedidos</h1>
    <span class="badge mock">SIMULACIÓN MOCK</span>
</div>

{{-- Filtros rápidos --}}
<div class="mt-4 flex flex-wrap gap-2">
    @foreach([''=>'Todos','payment_approved'=>'Pendientes','transmitted'=>'Transmitidos','delivered'=>'Entregados','sync_error'=>'Error sync','cancelled'=>'Cancelados'] as $val=>$label)
    <a href="{{ route('admin.orders.index', array_filter(['status'=>$val])) }}"
        class="btn btn-sm border text-xs {{ request('status')===$val ? 'btn-primary' : '' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="card mt-4 overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b text-left text-xs text-slate-400 uppercase tracking-wider">
                <th class="px-4 py-3">Pedido</th>
                <th class="px-4 py-3">Cliente</th>
                <th class="px-4 py-3">Total</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3">Fecha</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($orders as $order)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3 font-mono text-xs">{{ Str::limit($order->shopify_order_id, 20) }}</td>
                <td class="px-4 py-3">{{ $order->subscription?->customer?->name ?? '—' }}</td>
                <td class="px-4 py-3">${{ number_format($order->total, 0, ',', '.') }}</td>
                <td class="px-4 py-3">
                    <span class="badge text-xs {{ $order->status_badge }}">{{ $order->internal_status }}</span>
                </td>
                <td class="px-4 py-3 text-xs text-slate-400">{{ $order->created_at->format('d/m/Y') }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.orders.show', $order) }}" class="text-[#087f8c] text-xs font-semibold">Ver →</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Sin pedidos.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
</x-admin-shell>
