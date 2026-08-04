<x-admin-shell>
<div class="flex items-center justify-between">
    <h1 class="text-2xl font-black">Pagos</h1>
    <span class="badge mock">Mercado Pago Sandbox</span>
</div>

<div class="mt-4 flex gap-2">
    @foreach([''=>'Todos','approved'=>'Aprobados','rejected'=>'Rechazados','pending'=>'Pendientes'] as $val=>$label)
    <a href="{{ route('admin.payments.index', array_filter(['status'=>$val])) }}"
        class="btn btn-sm border text-xs {{ request('status')===$val?'btn-primary':'' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="card mt-4 overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b text-left text-xs text-slate-400 uppercase tracking-wider">
                <th class="px-4 py-3">ID Proveedor</th>
                <th class="px-4 py-3">Cliente</th>
                <th class="px-4 py-3">Monto</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3">Ciclo</th>
                <th class="px-4 py-3">Aprobado</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($payments as $payment)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-3 font-mono text-xs">{{ Str::limit($payment->provider_payment_id, 22) }}</td>
                <td class="px-4 py-3">{{ $payment->subscription?->customer?->name ?? '—' }}</td>
                <td class="px-4 py-3">${{ number_format($payment->amount, 0, ',', '.') }}</td>
                <td class="px-4 py-3">
                    <span class="badge text-xs {{ match($payment->status) {'approved'=>'bg-green-100 text-green-800','rejected'=>'bg-red-100 text-red-800','pending'=>'bg-amber-100 text-amber-800',default=>'badge-neutral'} }}">
                        {{ $payment->status }}
                    </span>
                </td>
                <td class="px-4 py-3">{{ $payment->billing_cycle }}</td>
                <td class="px-4 py-3 text-xs text-slate-400">{{ $payment->approved_at?->format('d/m/Y') ?? '—' }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.payments.show', $payment) }}" class="text-[#087f8c] text-xs font-semibold">Ver →</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Sin pagos.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $payments->links() }}</div>
</x-admin-shell>
