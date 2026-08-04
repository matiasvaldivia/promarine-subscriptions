<x-admin-shell>
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-black">Inventario</h1>
        <p class="text-sm text-slate-500">Stock mock por variante y ubicación.</p>
    </div>
    <span class="badge mock">SIMULACIÓN · NO MODIFICA SHOPIFY REAL</span>
</div>

{{-- Resumen --}}
<div class="mt-4 grid gap-4 sm:grid-cols-3">
    <div class="card p-4 text-center">
        <p class="text-xs text-slate-400 uppercase">En stock</p>
        <p class="text-3xl font-black text-green-500">{{ $summary['in_stock'] }}</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-xs text-slate-400 uppercase">Bajo stock</p>
        <p class="text-3xl font-black text-amber-500">{{ $summary['low_stock'] }}</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-xs text-slate-400 uppercase">Sin stock</p>
        <p class="text-3xl font-black text-red-500">{{ $summary['out_of_stock'] }}</p>
    </div>
</div>

<div class="card mt-4 overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b text-left text-xs text-slate-400 uppercase tracking-wider">
                <th class="px-4 py-3">Variante</th>
                <th class="px-4 py-3">Disponible</th>
                <th class="px-4 py-3">Reservado</th>
                <th class="px-4 py-3">Comprometido</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($levels as $level)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3">
                    <div class="font-semibold">{{ $level->variant?->product?->name }}</div>
                    <div class="text-xs text-slate-400">{{ $level->variant?->name }} · {{ $level->variant?->sku }}</div>
                </td>
                <td class="px-4 py-3 font-bold">{{ $level->available_quantity }}</td>
                <td class="px-4 py-3">{{ $level->reserved_quantity }}</td>
                <td class="px-4 py-3">{{ $level->committed_quantity }}</td>
                <td class="px-4 py-3">
                    <span class="badge text-xs {{ match($level->sync_status) {'in_stock'=>'bg-green-100 text-green-800','low_stock'=>'bg-amber-100 text-amber-800','out_of_stock'=>'bg-red-100 text-red-800',default=>'badge-neutral'} }}">
                        {{ $level->sync_status }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <form method="POST" action="{{ route('admin.inventory.adjust', $level) }}" class="flex gap-1 items-center" x-data>
                        @csrf
                        <input type="number" name="delta" class="input w-16 text-xs" placeholder="±0" min="-999" max="999">
                        <button class="btn border text-xs">Ajustar</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Sin niveles de inventario.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $levels->links() }}</div>
</x-admin-shell>
