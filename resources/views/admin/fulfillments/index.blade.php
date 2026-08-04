<x-admin-shell>
<div class="flex items-center justify-between">
    <h1 class="text-2xl font-black">Entregas / Fulfillments</h1>
</div>

<div class="card mt-4 overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b text-left text-xs text-slate-400 uppercase tracking-wider">
                <th class="px-4 py-3">ID Externo</th>
                <th class="px-4 py-3">Pedido</th>
                <th class="px-4 py-3">Cliente</th>
                <th class="px-4 py-3">Carrier</th>
                <th class="px-4 py-3">Tracking</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3">Entregado</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($fulfillments as $ff)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3 font-mono text-xs">{{ Str::limit($ff->external_fulfillment_id, 20) }}</td>
                <td class="px-4 py-3 font-mono text-xs">{{ Str::limit($ff->order?->shopify_order_id, 16) }}</td>
                <td class="px-4 py-3">{{ $ff->order?->subscription?->customer?->name ?? '—' }}</td>
                <td class="px-4 py-3">{{ $ff->carrier }}</td>
                <td class="px-4 py-3">
                    @if($ff->tracking_url)
                        <a href="{{ $ff->tracking_url }}" target="_blank" class="text-[#087f8c] text-xs">{{ $ff->tracking_number }}</a>
                    @else
                        {{ $ff->tracking_number ?? '—' }}
                    @endif
                </td>
                <td class="px-4 py-3"><span class="badge mock text-xs">{{ $ff->status }}</span></td>
                <td class="px-4 py-3 text-xs text-slate-400">{{ $ff->delivered_at?->format('d/m/Y') ?? '—' }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.fulfillments.show', $ff) }}" class="text-[#087f8c] text-xs font-semibold">Ver →</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">Sin entregas registradas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $fulfillments->links() }}</div>
</x-admin-shell>
