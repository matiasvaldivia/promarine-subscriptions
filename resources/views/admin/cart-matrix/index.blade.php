<x-admin-shell>
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-black">Matriz Comercial</h1>
        <p class="text-sm text-slate-500">{{ $summary['total'] }} combinaciones totales · {{ $summary['active'] }} activas</p>
    </div>
    <span class="badge mock">SIMULACIÓN</span>
</div>

<div class="card mt-6 overflow-x-auto">
    <table class="w-full text-xs">
        <thead>
            <tr class="border-b text-left text-xs text-slate-400 uppercase tracking-wider">
                <th class="px-3 py-3">Producto</th>
                <th class="px-3 py-3">Variante</th>
                <th class="px-3 py-3">Plan</th>
                <th class="px-3 py-3">Precio base</th>
                <th class="px-3 py-3">Descuento</th>
                <th class="px-3 py-3">Precio suscripción</th>
                <th class="px-3 py-3">Estado</th>
                <th class="px-3 py-3">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @foreach($rows as $row)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50" x-data="{ editing: false }">
                <td class="px-3 py-2 font-semibold">{{ $row->product?->name }}</td>
                <td class="px-3 py-2">{{ $row->variant?->name }}</td>
                <td class="px-3 py-2">{{ $row->plan?->name }}</td>
                <td class="px-3 py-2">${{ number_format($row->base_price, 0, ',', '.') }}</td>
                <td class="px-3 py-2">{{ $row->discount_value }}{{ $row->discount_type === 'percentage' ? '%' : ' ARS' }}</td>
                <td class="px-3 py-2 font-bold">${{ number_format($row->subscription_price, 0, ',', '.') }}</td>
                <td class="px-3 py-2">
                    <span class="badge text-xs {{ $row->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-500' }}">{{ $row->status }}</span>
                </td>
                <td class="px-3 py-2">
                    <button @click="editing=!editing" class="text-[#087f8c] text-xs font-semibold">Editar</button>
                    <template x-if="editing">
                        <form method="POST" action="{{ route('admin.cart-matrix.update', $row) }}" class="mt-2 flex gap-1 items-center">
                            @csrf @method('PUT')
                            <input type="number" name="discount_value" value="{{ $row->discount_value }}" class="input w-16 text-xs" min="0" max="100" step="0.5">
                            <select name="status" class="input text-xs">
                                <option value="active" @selected($row->status==='active')>activa</option>
                                <option value="inactive" @selected($row->status==='inactive')>inactiva</option>
                            </select>
                            <button class="btn btn-primary text-xs" type="submit">✓</button>
                        </form>
                    </template>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
</x-admin-shell>
