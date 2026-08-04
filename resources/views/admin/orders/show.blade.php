<x-admin-shell>
<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="{{ route('admin.orders.index') }}" class="text-sm text-slate-400 hover:text-[#087f8c]">← Pedidos</a>
        <h1 class="text-xl font-black mt-1 font-mono">{{ $order->shopify_order_id }}</h1>
    </div>
    <span class="badge mock">{{ $order->internal_status }}</span>
</div>

{{-- Info principal --}}
<div class="mt-6 grid gap-4 sm:grid-cols-3">
    <div class="card p-5">
        <h3 class="text-xs font-semibold text-slate-400 uppercase mb-2">Cliente</h3>
        <p class="font-semibold">{{ $order->subscription?->customer?->name ?? '—' }}</p>
        <p class="text-xs text-slate-400">{{ $order->subscription?->customer?->email }}</p>
        <a href="{{ $order->subscription?->customer ? route('admin.customers.show', $order->subscription->customer) : '#' }}" class="text-xs text-[#087f8c] mt-1 block">Ver cliente →</a>
    </div>
    <div class="card p-5">
        <h3 class="text-xs font-semibold text-slate-400 uppercase mb-2">Monto</h3>
        <p class="text-3xl font-black">${{ number_format($order->total, 0, ',', '.') }}</p>
        <p class="text-xs text-slate-400">{{ $order->financial_status }} · {{ $order->fulfillment_status }}</p>
    </div>
    <div class="card p-5">
        <h3 class="text-xs font-semibold text-slate-400 uppercase mb-2">Fulfillment</h3>
        @if($order->fulfillment)
        <p class="font-semibold">{{ $order->fulfillment->status }}</p>
        <p class="text-xs text-slate-400">{{ $order->fulfillment->carrier }} · {{ $order->fulfillment->tracking_number }}</p>
        @else
        <p class="text-slate-400 text-sm">Sin fulfillment aún</p>
        @endif
    </div>
</div>

{{-- Transición de estado --}}
@if(!empty($allowed))
<section class="card mt-6 p-5">
    <h2 class="text-sm font-bold mb-3">Cambiar estado</h2>
    <form method="POST" action="{{ route('admin.orders.transition', $order) }}" class="flex flex-wrap gap-2 items-end">
        @csrf
        <select name="to" class="input text-sm" required>
            <option value="">Seleccionar estado destino…</option>
            @foreach($allowed as $state)
            <option value="{{ $state }}">{{ $state }}</option>
            @endforeach
        </select>
        <input type="text" name="reason" class="input text-sm flex-1" placeholder="Motivo (opcional)">
        <button class="btn btn-primary text-sm">Aplicar transición</button>
    </form>
</section>
@endif

{{-- Timeline de estados --}}
<section class="card mt-6 p-5">
    <h2 class="text-sm font-bold mb-3">Timeline de estados</h2>
    <div class="space-y-3">
        @foreach($order->statusHistory as $entry)
        <div class="flex items-start gap-3 text-sm">
            <div class="w-2 h-2 rounded-full bg-[#087f8c] mt-1.5 flex-shrink-0"></div>
            <div>
                <span class="font-semibold">{{ $entry->to_status }}</span>
                @if($entry->from_status) <span class="text-slate-400 text-xs">← {{ $entry->from_status }}</span> @endif
                <p class="text-xs text-slate-400">{{ $entry->reason }}</p>
                <p class="text-xs text-slate-300">{{ $entry->created_at->format('d/m/Y H:i') }} · {{ $entry->changedBy?->name ?? 'Sistema' }}</p>
            </div>
        </div>
        @endforeach
    </div>
</section>
</x-admin-shell>
