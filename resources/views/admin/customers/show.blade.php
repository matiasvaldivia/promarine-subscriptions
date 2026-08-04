<x-admin-shell>
{{-- Header --}}
<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="{{ route('admin.customers.index') }}" class="text-sm text-slate-400 hover:text-[#087f8c]">← Clientes</a>
        <h1 class="text-2xl font-black mt-1">{{ $customer->name }}</h1>
        <p class="text-sm text-slate-500">{{ $customer->email }} · {{ $customer->phone }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.customers.edit', $customer) }}" class="btn border">Editar</a>
        <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm('¿Eliminar cliente?')">
            @csrf @method('DELETE')
            <button class="btn border border-red-200 text-red-600" type="submit">Eliminar</button>
        </form>
    </div>
</div>

{{-- Info --}}
<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-slate-400 mb-2">Datos personales</h3>
        <dl class="text-sm space-y-1">
            <div><dt class="inline text-slate-400">Estado:</dt> <dd class="inline font-semibold">{{ ucfirst($customer->status) }}</dd></div>
            <div><dt class="inline text-slate-400">Provincia:</dt> <dd class="inline">{{ $customer->province }}</dd></div>
            <div><dt class="inline text-slate-400">Localidad:</dt> <dd class="inline">{{ $customer->locality }}</dd></div>
            <div><dt class="inline text-slate-400">CP:</dt> <dd class="inline">{{ $customer->postal_code }}</dd></div>
            <div><dt class="inline text-slate-400">Dirección:</dt> <dd class="inline">{{ $customer->address }} {{ $customer->address_number }}</dd></div>
            <div><dt class="inline text-slate-400">Fuente:</dt> <dd class="inline">{{ $customer->source }}</dd></div>
        </dl>
    </div>
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-slate-400 mb-2">Integraciones</h3>
        <dl class="text-sm space-y-1">
            <div><dt class="inline text-slate-400">Shopify ID:</dt> <dd class="inline font-mono text-xs">{{ $customer->shopify_customer_id ?? '—' }}</dd></div>
            <div><dt class="inline text-slate-400">MP ID:</dt> <dd class="inline font-mono text-xs">{{ $customer->mercadopago_customer_id ?? '—' }}</dd></div>
            <div><dt class="inline text-slate-400">IGS ID:</dt> <dd class="inline font-mono text-xs">{{ $customer->igs_customer_id ?? '—' }}</dd></div>
        </dl>
        <span class="badge mock mt-2">MOCK</span>
    </div>
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-slate-400 mb-2">Resumen</h3>
        <p class="text-3xl font-black">{{ $customer->subscriptions->count() }}</p>
        <p class="text-sm text-slate-500">suscripciones en total</p>
        <a href="{{ route('admin.subscriptions.index', ['q'=>$customer->email]) }}" class="text-xs text-[#087f8c] mt-2 block">Ver suscripciones →</a>
    </div>
</div>

{{-- Suscripciones --}}
<section class="card mt-6 p-6">
    <h2 class="text-lg font-bold mb-4">Suscripciones</h2>
    <div class="divide-y text-sm">
        @forelse($customer->subscriptions as $sub)
        <div class="flex items-center justify-between py-3">
            <div>
                <b>{{ $sub->plan?->name ?? 'Sin plan' }}</b>
                <p class="text-xs text-slate-500">{{ $sub->plan?->variant?->product?->name }} · ${{ number_format($sub->amount, 0, ',', '.') }} ARS/mes</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge mock text-xs">{{ $sub->status }}</span>
                <a href="{{ route('admin.subscriptions.show', $sub) }}" class="text-[#087f8c] text-xs">Ver →</a>
            </div>
        </div>
        @empty
        <p class="text-slate-400 py-3">Sin suscripciones.</p>
        @endforelse
    </div>
</section>
</x-admin-shell>
