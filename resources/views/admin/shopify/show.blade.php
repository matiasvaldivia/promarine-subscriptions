<x-admin-shell>
<div class="flex items-center justify-between">
    <div>
        <a href="{{ route('admin.shopify.index') }}" class="text-sm text-slate-400">← Shopify Sync</a>
        <h1 class="text-xl font-black mt-1">Run: {{ $run->entity_type }}</h1>
        <p class="text-xs text-slate-400 font-mono">{{ $run->uuid }}</p>
    </div>
    <span class="badge mock">{{ $run->status }}</span>
</div>

<div class="mt-4 grid gap-4 sm:grid-cols-4">
    <div class="card p-4 text-center">
        <p class="text-xs text-slate-400">Leídos</p>
        <p class="text-3xl font-black">{{ $run->records_read }}</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-xs text-slate-400">Creados</p>
        <p class="text-3xl font-black text-green-500">{{ $run->records_created }}</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-xs text-slate-400">Actualizados</p>
        <p class="text-3xl font-black text-blue-500">{{ $run->records_updated }}</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-xs text-slate-400">Errores</p>
        <p class="text-3xl font-black text-red-500">{{ $run->records_failed }}</p>
    </div>
</div>

<div class="card mt-4 overflow-x-auto">
    <h2 class="px-5 pt-5 text-sm font-bold">Ítems sincronizados</h2>
    <table class="w-full text-sm mt-2">
        <thead>
            <tr class="border-b text-left text-xs text-slate-400 uppercase tracking-wider">
                <th class="px-4 py-3">Entidad</th>
                <th class="px-4 py-3">Local ID</th>
                <th class="px-4 py-3">External ID</th>
                <th class="px-4 py-3">Operación</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3">Error</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($run->items as $item)
            <tr class="{{ $item->status === 'failed' ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                <td class="px-4 py-2">{{ $item->entity_type }}</td>
                <td class="px-4 py-2 font-mono text-xs">{{ $item->local_id }}</td>
                <td class="px-4 py-2 font-mono text-xs">{{ Str::limit($item->external_id, 20) }}</td>
                <td class="px-4 py-2 text-xs">{{ $item->operation }}</td>
                <td class="px-4 py-2">
                    <span class="badge text-xs {{ $item->status==='processed'?'bg-green-100 text-green-800':'bg-red-100 text-red-800' }}">{{ $item->status }}</span>
                </td>
                <td class="px-4 py-2 text-xs text-red-500">{{ $item->last_error }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">Sin ítems.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
</x-admin-shell>
