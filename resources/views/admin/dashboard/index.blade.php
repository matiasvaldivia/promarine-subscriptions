<x-admin-shell>
{{-- ── Header ─────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <span class="badge mock">SIMULACIÓN · ENTORNO LOCAL · NO MODIFICA SHOPIFY REAL</span>
        <h1 class="mt-3 text-3xl font-black">Dashboard de {{ auth()->user()->name ?? 'Tamara' }}</h1>
        <p class="text-sm text-slate-500 mt-1">Métricas en tiempo real desde la base de datos local.</p>
    </div>
    <div class="flex gap-2">
        <a class="btn btn-primary text-sm" href="{{ route('admin.interview') }}">Abrir cuestionarios</a>
        <form action="{{ route('admin.shopify.run') }}" method="POST">
            @csrf
            <input type="hidden" name="entity_type" value="inventory">
            <button class="btn border text-sm" type="submit">↻ Sync Inventario</button>
        </form>
    </div>
</div>

{{-- ── KPI Grid ────────────────────────────────────────────────── --}}
<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

    {{-- Clientes --}}
    <a href="{{ route('admin.customers.index') }}" class="card p-5 hover:shadow-md transition-shadow">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Clientes</p>
        <p class="mt-2 text-4xl font-black">{{ number_format($metrics['customers_total']) }}</p>
        <p class="text-sm text-slate-500 mt-1">{{ $metrics['customers_active'] }} activos</p>
    </a>

    {{-- Suscripciones activas --}}
    <a href="{{ route('admin.subscriptions.index', ['status'=>'payment_approved']) }}" class="card p-5 hover:shadow-md transition-shadow">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Suscripciones activas</p>
        <p class="mt-2 text-4xl font-black">{{ number_format($metrics['subscriptions_active']) }}</p>
        <p class="text-sm text-slate-500 mt-1">
            {{ $metrics['subscriptions_paused'] }} pausadas ·
            {{ $metrics['subscriptions_cancelled'] }} canceladas
        </p>
    </a>

    {{-- Pedidos pendientes --}}
    <a href="{{ route('admin.orders.index', ['status'=>'payment_approved']) }}" class="card p-5 hover:shadow-md transition-shadow">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pedidos pendientes</p>
        <p class="mt-2 text-4xl font-black">{{ number_format($metrics['orders_new']) }}</p>
        <p class="text-sm text-slate-500 mt-1">
            {{ $metrics['orders_transmitted'] }} transmitidos ·
            {{ $metrics['orders_delivered'] }} entregados
        </p>
    </a>

    {{-- Alertas stock --}}
    <a href="{{ route('admin.inventory') }}" class="card p-5 hover:shadow-md transition-shadow {{ $metrics['stock_out'] > 0 ? 'border-red-300' : '' }}">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Stock</p>
        <p class="mt-2 text-4xl font-black {{ $metrics['stock_out'] > 0 ? 'text-red-500' : '' }}">
            {{ $metrics['stock_out'] }}
        </p>
        <p class="text-sm text-slate-500 mt-1">sin stock · {{ $metrics['stock_low'] }} bajo stock</p>
    </a>
</div>

{{-- ── Segunda fila de KPIs ────────────────────────────────────── --}}
<div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="card p-5">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pagos aprobados</p>
        <p class="mt-2 text-3xl font-black text-green-500">{{ number_format($metrics['payments_approved']) }}</p>
    </div>
    <div class="card p-5">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pagos rechazados</p>
        <p class="mt-2 text-3xl font-black text-red-500">{{ number_format($metrics['payments_rejected']) }}</p>
    </div>
    <div class="card p-5">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Errores de sync</p>
        <p class="mt-2 text-3xl font-black {{ $metrics['orders_error'] > 0 ? 'text-amber-500' : '' }}">{{ number_format($metrics['orders_error']) }}</p>
        @if($metrics['orders_error'] > 0)
            <a href="{{ route('admin.orders.index', ['status'=>'sync_error']) }}" class="text-xs text-[#087f8c] mt-1 block">Ver errores →</a>
        @endif
    </div>
    <div class="card p-5">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Última sync</p>
        <p class="mt-2 text-sm font-semibold">
            {{ $metrics['last_sync'] ? \Carbon\Carbon::parse($metrics['last_sync'])->diffForHumans() : 'Nunca' }}
        </p>
        <p class="text-xs text-slate-400">{{ $metrics['last_sync_status'] ?? '—' }}</p>
    </div>
</div>

{{-- ── Barras SVG de estados de suscripciones ─────────────────── --}}
<section class="card mt-6 p-6">
    <h2 class="text-xl font-black mb-4">Estado de suscripciones</h2>
    @php
        $subTotal = max(1, $metrics['subscriptions_active'] + $metrics['subscriptions_pending'] + $metrics['subscriptions_paused'] + $metrics['subscriptions_cancelled']);
        $bars = [
            ['Activas',    $metrics['subscriptions_active'],    '#087f8c'],
            ['Pendientes', $metrics['subscriptions_pending'],   '#f59e0b'],
            ['Pausadas',   $metrics['subscriptions_paused'],    '#6366f1'],
            ['Canceladas', $metrics['subscriptions_cancelled'], '#ef4444'],
        ];
    @endphp
    <div class="flex gap-2 h-8 rounded-lg overflow-hidden">
        @foreach($bars as [$label, $count, $color])
            @if($count > 0)
            <div style="width:{{ round($count/$subTotal*100) }}%; background:{{ $color }}" title="{{ $label }}: {{ $count }}" class="flex items-center justify-center text-white text-xs font-bold">
                {{ round($count/$subTotal*100) }}%
            </div>
            @endif
        @endforeach
    </div>
    <div class="flex flex-wrap gap-4 mt-3 text-xs text-slate-500">
        @foreach($bars as [$label, $count, $color])
        <span><span class="inline-block w-3 h-3 rounded-sm mr-1" style="background:{{ $color }}"></span>{{ $label }}: {{ $count }}</span>
        @endforeach
    </div>
</section>

{{-- ── Alertas de acción requerida ────────────────────────────── --}}
@if($recentOrders->isNotEmpty())
<section class="card mt-6 p-6">
    <h2 class="text-xl font-black mb-4">⚠ Acción requerida</h2>
    <div class="divide-y">
        @foreach($recentOrders as $order)
        <div class="flex items-center justify-between py-3">
            <div>
                <b class="font-semibold">{{ $order->shopify_order_id }}</b>
                <p class="text-xs text-slate-500">{{ $order->subscription?->customer?->name ?? '—' }} · ${{ number_format($order->total, 0, ',', '.') }} ARS</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge mock text-xs">{{ $order->internal_status }}</span>
                <a href="{{ route('admin.orders.show', $order) }}" class="text-[#087f8c] text-xs font-semibold">Ver →</a>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif

{{-- ── Últimos eventos de integración ─────────────────────────── --}}
<section class="card mt-6 p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-black">Eventos recientes</h2>
        <a href="{{ route('admin.integration-events') }}" class="text-sm text-[#087f8c]">Ver todos →</a>
    </div>
    <div class="divide-y">
        @forelse($recentEvents as $event)
        <div class="flex items-center justify-between py-3">
            <div>
                <b>{{ $event->event_type }}</b>
                <p class="text-xs text-slate-500">{{ $event->integration }}</p>
            </div>
            <span class="badge mock">MOCK · {{ $event->status }}</span>
        </div>
        @empty
        <p class="text-sm text-slate-400 py-3">Sin eventos registrados.</p>
        @endforelse
    </div>
</section>
</x-admin-shell>
