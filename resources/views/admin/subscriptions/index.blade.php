<x-admin-shell>
<div class="flex items-center justify-between">
    <h1 class="text-2xl font-black">Suscripciones</h1>
    <span class="badge mock">MOCK</span>
</div>

<div class="mt-4 flex flex-wrap gap-2">
    @foreach([''=>'Todas','payment_approved'=>'Activas','paused'=>'Pausadas','payment_rejected'=>'Rechazadas','cancelled'=>'Canceladas'] as $val=>$label)
    <a href="{{ route('admin.subscriptions.index', array_filter(['status'=>$val])) }}"
        class="btn btn-sm border text-xs {{ request('status')===$val ? 'btn-primary' : '' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="card mt-4 overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b text-left text-xs text-slate-400 uppercase tracking-wider">
                <th class="px-4 py-3">Cliente</th>
                <th class="px-4 py-3">Plan / Producto</th>
                <th class="px-4 py-3">Monto</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3">Próx. cobro</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($subscriptions as $sub)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3">
                    <div class="font-semibold">{{ $sub->customer?->name ?? '—' }}</div>
                    <div class="text-xs text-slate-400">{{ $sub->customer?->email }}</div>
                </td>
                <td class="px-4 py-3">
                    <div>{{ $sub->plan?->name }}</div>
                    <div class="text-xs text-slate-400">{{ $sub->plan?->variant?->product?->name }}</div>
                </td>
                <td class="px-4 py-3">${{ number_format($sub->amount, 0, ',', '.') }}</td>
                <td class="px-4 py-3"><span class="badge mock text-xs">{{ $sub->status }}</span></td>
                <td class="px-4 py-3 text-xs">{{ $sub->next_billing_at?->format('d/m/Y') ?? '—' }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.subscriptions.show', $sub) }}" class="text-[#087f8c] text-xs font-semibold">Ver →</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Sin suscripciones.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $subscriptions->links() }}</div>
</x-admin-shell>
