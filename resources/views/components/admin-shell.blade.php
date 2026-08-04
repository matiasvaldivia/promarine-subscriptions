<x-layouts.app>
<div class="pm-admin-app">
    <header class="pm-admin-topbar">
        <div class="pm-admin-shell">
            <a href="/admin" class="pm-admin-brand">
                <img src="/assets/promarine/optimized/promarine-logo-300.webp" width="180" height="54" alt="Promarine">
                <span>Admin</span>
            </a>
            <div class="pm-admin-user">
                <span class="badge mock" title="Entorno local — sin datos reales">MOCK · LOCAL</span>
                <span>{{ auth()->user()->name }}</span>
                <a href="/" target="_blank">Sitio ↗</a>
                <form method="post" action="/admin/logout">@csrf
                    <button type="submit">Salir</button>
                </form>
            </div>
        </div>
    </header>

    <div class="pm-admin-shell pm-admin-layout">
        {{-- ── Sidebar nav ─────────────────────────────────────── --}}
        <nav class="pm-admin-nav" aria-label="Panel administrativo">
            {{-- Inicio --}}
            <a href="{{ route('admin.dashboard') }}" @class(['is-active'=>request()->is('admin') || request()->is('admin/dashboard')])>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>

            {{-- Clientes --}}
            <a href="{{ route('admin.customers.index') }}" @class(['is-active'=>request()->is('admin/customers*')])>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/><circle cx="19" cy="11" r="2"/><path d="M23 21v-1a2 2 0 00-2-2h-1"/></svg>
                Clientes
            </a>

            {{-- Suscripciones --}}
            <a href="{{ route('admin.subscriptions.index') }}" @class(['is-active'=>request()->is('admin/subscriptions*')])>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12a9 9 0 1018 0 9 9 0 00-18 0"/><polyline points="12 6 12 12 16 14"/></svg>
                Suscripciones
            </a>

            {{-- Pedidos --}}
            <a href="{{ route('admin.orders.index') }}" @class(['is-active'=>request()->is('admin/orders*')])>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                Pedidos
            </a>

            {{-- Pagos --}}
            <a href="{{ route('admin.payments.index') }}" @class(['is-active'=>request()->is('admin/payments*')])>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                Pagos
            </a>

            {{-- Fulfillments --}}
            <a href="{{ route('admin.fulfillments.index') }}" @class(['is-active'=>request()->is('admin/fulfillments*')])>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Entregas
            </a>

            <div class="nav-divider"></div>

            {{-- Productos --}}
            <a href="{{ route('admin.products.index') }}" @class(['is-active'=>request()->is('admin/products*')])>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                Productos
            </a>

            {{-- Matriz --}}
            <a href="{{ route('admin.cart-matrix') }}" @class(['is-active'=>request()->is('admin/cart-matrix*')])>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Matriz 24×
            </a>

            {{-- Inventario --}}
            <a href="{{ route('admin.inventory') }}" @class(['is-active'=>request()->is('admin/inventory*')])>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Inventario
            </a>

            <div class="nav-divider"></div>

            {{-- Shopify sync --}}
            <a href="{{ route('admin.shopify.index') }}" @class(['is-active'=>request()->is('admin/integrations/shopify*')])>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
                Shopify Sync
            </a>

            {{-- IGS --}}
            <a href="{{ route('admin.igs.index') }}" @class(['is-active'=>request()->is('admin/igs*')])>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                IGS / Comisiones
            </a>

            {{-- Eventos --}}
            <a href="{{ route('admin.integration-events') }}" @class(['is-active'=>request()->is('admin/integration-events*')])>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Eventos
            </a>

            <div class="nav-divider"></div>

            {{-- Legacy --}}
            <a href="/admin/interview" @class(['is-active'=>request()->is('admin/interview*')])>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Cuestionarios
            </a>
            <a href="/admin/simulator" @class(['is-active'=>request()->is('admin/simulator')])>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                Simulador
            </a>

            {{-- Usuarios --}}
            @if(auth()->user()->hasPermission('manage_users'))
            <a href="{{ route('admin.users.index') }}" @class(['is-active'=>request()->is('admin/users*')])>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                Usuarios
            </a>
            @endif

            {{-- Auditoría --}}
            @if(auth()->user()->hasPermission('view_audit'))
            <a href="{{ route('admin.audit-logs') }}" @class(['is-active'=>request()->is('admin/audit-logs*')])>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Auditoría
            </a>
            @endif
        </nav>

        <main class="pm-admin-main">
            {{-- Flash messages --}}
            @if(session('success'))
                <div class="alert alert-success mb-4">✓ {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error mb-4">
                    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                </div>
            @endif
            {{ $slot }}
        </main>
    </div>
</div>
</x-layouts.app>
