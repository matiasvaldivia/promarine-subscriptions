<x-admin-shell>
<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-black">Clientes</h1>
        <p class="text-sm text-slate-500">Total: {{ $customers->total() }}</p>
    </div>
    <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">+ Nuevo cliente</a>
</div>

{{-- Filtros --}}
<form class="mt-4 flex flex-wrap gap-2" method="GET">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar nombre, email, teléfono…"
        class="input flex-1 min-w-[200px]">
    <select name="status" class="input">
        <option value="">Todos los estados</option>
        <option value="active" @selected(request('status')==='active')>Activo</option>
        <option value="inactive" @selected(request('status')==='inactive')>Inactivo</option>
        <option value="blocked" @selected(request('status')==='blocked')>Bloqueado</option>
    </select>
    <button class="btn border" type="submit">Filtrar</button>
    <a href="{{ route('admin.customers.index') }}" class="btn border">Limpiar</a>
</form>

{{-- Tabla --}}
<div class="card mt-4 overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b text-left text-xs text-slate-400 uppercase tracking-wider">
                <th class="px-4 py-3">Cliente</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">Provincia</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3">Suscripciones</th>
                <th class="px-4 py-3">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($customers as $customer)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3">
                    <div class="font-semibold">{{ $customer->name }}</div>
                    <div class="text-xs text-slate-400">{{ $customer->phone }}</div>
                </td>
                <td class="px-4 py-3">{{ $customer->email }}</td>
                <td class="px-4 py-3">{{ $customer->province }}</td>
                <td class="px-4 py-3">
                    <span class="badge {{ match($customer->status) { 'active'=>'bg-green-100 text-green-800', 'inactive'=>'bg-slate-100 text-slate-600', 'blocked'=>'bg-red-100 text-red-800', default=>'badge-neutral' } }}">
                        {{ ucfirst($customer->status) }}
                    </span>
                </td>
                <td class="px-4 py-3">{{ $customer->subscriptions_count }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.customers.show', $customer) }}" class="text-[#087f8c] font-semibold text-xs">Ver</a>
                    <a href="{{ route('admin.customers.edit', $customer) }}" class="text-slate-500 text-xs ml-2">Editar</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Sin resultados.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Paginación --}}
<div class="mt-4">{{ $customers->links() }}</div>
</x-admin-shell>
