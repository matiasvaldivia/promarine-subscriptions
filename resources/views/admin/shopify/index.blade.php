<x-admin-shell>
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-black">Shopify Sync</h1>
        <p class="text-xs text-slate-400 mt-1">⚠ SIMULACIÓN — NO MODIFICA SHOPIFY REAL · is_mock = true</p>
    </div>
    <span class="badge mock">MOCK GATEWAY</span>
</div>

{{-- Iniciar nuevo sync --}}
<section class="card mt-4 p-5">
    <h2 class="text-sm font-bold mb-3">Ejecutar nueva sincronización</h2>
    <form action="{{ route('admin.shopify.run') }}" method="POST" class="flex flex-wrap gap-2 items-end">
        @csrf
        <select name="entity_type" class="input text-sm" required>
            <option value="">Seleccionar entidad…</option>
            <option value="inventory">Inventario</option>
            <option value="orders">Pedidos</option>
            <option value="products">Productos</option>
        </select>
        <select name="direction" class="input text-sm">
            <option value="shopify_to_local">Shopify → Local</option>
            <option value="local_to_shopify">Local → Shopify</option>
        </select>
        <button class="btn btn-primary text-sm">Ejecutar sync mock</button>
    </form>
</section>

{{-- Historial de runs --}}
<div class="card mt-4 overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b text-left text-xs text-slate-400 uppercase tracking-wider">
                <th class="px-4 py-3">Entidad</th>
                <th class="px-4 py-3">Dirección</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3">Leídos / OK / Errores</th>
                <th class="px-4 py-3">Duración</th>
                <th class="px-4 py-3">Fecha</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($runs as $run)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3 font-semibold">{{ $run->entity_type }}</td>
                <td class="px-4 py-3 text-xs">{{ $run->direction }}</td>
                <td class="px-4 py-3">
                    <span class="badge text-xs {{ match($run->status) {
                        'completed'=>'bg-green-100 text-green-800',
                        'completed_with_errors'=>'bg-amber-100 text-amber-800',
                        'failed'=>'bg-red-100 text-red-800',
                        'running'=>'bg-blue-100 text-blue-800',
                        default=>'badge-neutral'
                    } }}">{{ $run->status }}</span>
                </td>
                <td class="px-4 py-3 text-xs">
                    {{ $run->records_read }} / {{ $run->records_read - $run->records_failed }} / {{ $run->records_failed }}
                </td>
                <td class="px-4 py-3 text-xs">{{ $run->duration ?? '—' }}</td>
                <td class="px-4 py-3 text-xs text-slate-400">{{ $run->created_at->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.shopify.show', $run) }}" class="text-[#087f8c] text-xs font-semibold">Ver →</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Sin runs de sync.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $runs->links() }}</div>
</x-admin-shell>
