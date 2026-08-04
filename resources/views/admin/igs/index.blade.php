<x-admin-shell>
<div class="flex items-center justify-between">
    <h1 class="text-2xl font-black">IGS · Comisiones</h1>
    <div class="text-right">
        <p class="text-xs text-slate-400">Total comisiones enviadas</p>
        <p class="text-2xl font-black">${{ number_format($totalCommission, 0, ',', '.') }} ARS</p>
    </div>
</div>

<div class="card mt-4 overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b text-left text-xs text-slate-400 uppercase tracking-wider">
                <th class="px-4 py-3">Evento</th>
                <th class="px-4 py-3">Tipo</th>
                <th class="px-4 py-3">Código influencer</th>
                <th class="px-4 py-3">Base</th>
                <th class="px-4 py-3">Comisión</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3">Fecha</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($events as $event)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3 font-mono text-xs">{{ Str::limit($event->event_id, 20) }}</td>
                <td class="px-4 py-3 text-xs">{{ $event->type }}</td>
                <td class="px-4 py-3">{{ $event->influencer_code ?? '—' }}</td>
                <td class="px-4 py-3">${{ number_format($event->base_amount, 0, ',', '.') }}</td>
                <td class="px-4 py-3 font-bold">${{ number_format($event->commission, 0, ',', '.') }}</td>
                <td class="px-4 py-3"><span class="badge mock text-xs">{{ $event->status }}</span></td>
                <td class="px-4 py-3 text-xs text-slate-400">{{ $event->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Sin eventos IGS.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $events->links() }}</div>
</x-admin-shell>
