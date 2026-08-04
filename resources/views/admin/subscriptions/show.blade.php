<x-admin-shell>
<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="{{ route('admin.subscriptions.index') }}" class="text-sm text-slate-400">← Suscripciones</a>
        <h1 class="text-xl font-black mt-1">Suscripción #{{ $subscription->id }}</h1>
        <p class="text-sm text-slate-500">{{ $subscription->customer?->name }} · {{ $subscription->customer?->email }}</p>
    </div>
    <span class="badge mock text-sm">{{ $subscription->status }}</span>
</div>

{{-- Acciones --}}
<div class="mt-4 flex flex-wrap gap-2">
    @if(in_array($subscription->status, ['payment_approved', 'authorized', 'active']))
    <form method="POST" action="{{ route('admin.subscriptions.pause', $subscription) }}">
        @csrf <button class="btn border text-sm">⏸ Pausar</button>
    </form>
    @endif
    @if($subscription->status === 'paused')
    <form method="POST" action="{{ route('admin.subscriptions.resume', $subscription) }}">
        @csrf <button class="btn border text-sm">▶ Reactivar</button>
    </form>
    @endif
    @if(!in_array($subscription->status, ['cancelled', 'expired']))
    <form method="POST" action="{{ route('admin.subscriptions.cancel', $subscription) }}"
          onsubmit="return confirm('¿Cancelar esta suscripción?')">
        @csrf
        <input type="hidden" name="reason" value="Cancelación manual desde panel admin">
        <button class="btn border border-red-200 text-red-600 text-sm">✕ Cancelar</button>
    </form>
    @endif
</div>

{{-- Detalles --}}
<div class="mt-6 grid gap-4 sm:grid-cols-3">
    <div class="card p-5">
        <h3 class="text-xs font-semibold text-slate-400 uppercase mb-2">Plan</h3>
        <p class="font-semibold">{{ $subscription->plan?->name }}</p>
        <p class="text-xs text-slate-400">{{ $subscription->plan?->variant?->product?->name }}</p>
        <p class="text-xs text-slate-400">Ciclo {{ $subscription->current_cycle }} · cada {{ $subscription->frequency }} días</p>
    </div>
    <div class="card p-5">
        <h3 class="text-xs font-semibold text-slate-400 uppercase mb-2">Monto</h3>
        <p class="text-3xl font-black">${{ number_format($subscription->amount, 0, ',', '.') }}</p>
        <p class="text-xs text-slate-400">{{ $subscription->currency }}</p>
    </div>
    <div class="card p-5">
        <h3 class="text-xs font-semibold text-slate-400 uppercase mb-2">Próximo cobro</h3>
        <p class="font-semibold">{{ $subscription->next_billing_at?->format('d/m/Y') ?? '—' }}</p>
        <p class="text-xs text-slate-400">Inicio: {{ $subscription->started_at?->format('d/m/Y') }}</p>
    </div>
</div>

{{-- Pagos --}}
<section class="card mt-6 p-5">
    <h2 class="text-sm font-bold mb-3">Pagos ({{ $subscription->payments->count() }})</h2>
    <div class="divide-y text-sm">
        @foreach($subscription->payments->take(5) as $payment)
        <div class="flex items-center justify-between py-2">
            <span class="font-mono text-xs">{{ $payment->provider_payment_id }}</span>
            <span class="badge mock text-xs">{{ $payment->status }}</span>
            <span>${{ number_format($payment->amount, 0, ',', '.') }}</span>
        </div>
        @endforeach
    </div>
</section>
</x-admin-shell>
