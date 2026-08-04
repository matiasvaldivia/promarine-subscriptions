<x-admin-shell>
<h1 class="text-2xl font-black">Auditoría</h1>
<p class="text-sm text-slate-500 mt-1">Registro de todas las acciones de administradores.</p>

<form class="mt-4 flex flex-wrap gap-2" method="GET">
    <select name="action" class="input text-sm">
        <option value="">Todas las acciones</option>
        @foreach(['customer.created','customer.updated','customer.deleted','subscription.paused','subscription.resumed','subscription.cancelled','order.transition','inventory.adjusted','user.created','user.updated','shopify_sync.executed'] as $action)
        <option value="{{ $action }}" @selected(request('action')===$action)>{{ $action }}</option>
        @endforeach
    </select>
    <button class="btn border text-sm">Filtrar</button>
    <a href="{{ route('admin.audit-logs') }}" class="btn border text-sm">Limpiar</a>
</form>

<div class="card mt-4 overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b text-left text-xs text-slate-400 uppercase tracking-wider">
                <th class="px-4 py-3">Acción</th>
                <th class="px-4 py-3">Entidad</th>
                <th class="px-4 py-3">Usuario</th>
                <th class="px-4 py-3">IP</th>
                <th class="px-4 py-3">Fecha</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($logs as $log)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3 font-mono text-xs font-semibold">{{ $log->action }}</td>
                <td class="px-4 py-3 text-xs text-slate-400">
                    {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                </td>
                <td class="px-4 py-3">{{ $log->user?->name ?? 'Sistema' }}</td>
                <td class="px-4 py-3 font-mono text-xs">{{ $log->ip_address }}</td>
                <td class="px-4 py-3 text-xs text-slate-400">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Sin registros de auditoría.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
</x-admin-shell>
