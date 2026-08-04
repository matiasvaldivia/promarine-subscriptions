<x-layouts.app>
<div class="pm-admin-app">
    <header class="pm-admin-topbar">
        <div class="pm-admin-shell">
            <a href="/admin" class="pm-admin-brand"><img src="/assets/promarine/optimized/promarine-logo-300.webp" width="300" height="90" alt="Promarine"><span>Decisiones</span></a>
            <div class="pm-admin-user"><span>Hola, {{ auth()->user()->name }}</span><a href="/" target="_blank">Ver sitio ↗</a><form method="post" action="/admin/logout">@csrf<button>Salir</button></form></div>
        </div>
    </header>
    <div class="pm-admin-shell pm-admin-layout">
        <nav class="pm-admin-nav" aria-label="Panel de Tamara">
            <a href="/admin" @class(['is-active'=>request()->is('admin')])><span>⌂</span>Inicio</a>
            <a href="/admin/interview" @class(['is-active'=>request()->is('admin/interview')])><span>?</span>Cuestionarios</a>
            <a href="/admin/interview/report" @class(['is-active'=>request()->is('admin/interview/report')])><span>▤</span>Informe</a>
            <a href="/admin/simulator" @class(['is-active'=>request()->is('admin/simulator')])><span>◇</span>Simulador</a>
            <a href="/admin#events"><span>↻</span>Eventos</a>
        </nav>
        <main class="pm-admin-main">{{ $slot }}</main>
    </div>
</div>
</x-layouts.app>
