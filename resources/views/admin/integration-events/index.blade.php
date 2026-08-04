<x-admin-shell>
<h1 class="text-2xl font-black">Eventos de integración</h1>

<div class="card mt-4 overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b text-left text-xs text-slate-400 uppercase tracking-wider">
                <th class="px-4 py-3">Integración</th>
                <th class="px-4 py-3">Tipo de evento</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3">Intentos</th>
                <th class="px-4 py-3">Fecha</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($events as $event)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3 font-semibold">{{ $event->integration }}</td>
                <td class="px-4 py-3 font-mono text-xs">{{ $event->event_type }}</td>
                <td class="px-4 py-3"><span class="badge mock text-xs">{{ $event->status }}</span></td>
                <td class="px-4 py-3">{{ $event->attempts ?? 1 }}</td>
                <td class="px-4 py-3 text-xs text-slate-400">{{ $event->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Sin eventos.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $events->links() }}</div>
</x-admin-shell>
