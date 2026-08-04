<x-layouts.app title="Mi Promarine — {{ $subscription->plan->variant->product->name }}">
<div class="pm-client-app">
    <header class="pm-client-header">
        <a href="{{ route('customer.portal.dashboard') }}"><img src="/assets/promarine/optimized/promarine-logo-300.webp" width="300" height="90" alt="Promarine"></a>
        <div><span>Hola, {{ Str::before($customer->name, ' ') }}</span><form method="POST" action="{{ route('customer.portal.logout') }}">@csrf<button type="submit">Salir</button></form></div>
    </header>

    <main class="pm-client-main">
        <section class="pm-client-welcome">
            <div><span class="pm-portal-kicker">MI PLAN ACTIVO</span><h1>Todo tu Promarine<br><em>en un solo lugar.</em></h1><p>Estas son las fechas previstas según la frecuencia que elegiste.</p></div>
            <span class="pm-client-demo">DEMO · SIN COBROS REALES</span>
        </section>

        <section class="pm-client-plan">
            <div class="pm-client-product">
                <img src="/assets/promarine/optimized/{{ $subscription->plan->variant->product->slug }}-composition-480.webp" width="480" height="600" alt="{{ $subscription->plan->variant->product->name }}">
            </div>
            <div class="pm-client-plan__info">
                <span class="pm-client-status"><i></i> Plan activo</span>
                <h2>{{ $subscription->plan->variant->product->name }}</h2>
                <p>{{ $subscription->plan->variant->name }} · {{ $subscription->plan->name }}</p>
                <div class="pm-client-plan__price"><strong>$ {{ number_format((float) $subscription->amount, 0, ',', '.') }}</strong><span>cada {{ $subscription->frequency }} días</span></div>
                <dl>
                    <div><dt>Próximo pago</dt><dd>{{ $schedule[1]['billing']->locale('es')->translatedFormat('d \d\e F') }}</dd></div>
                    <div><dt>Próxima entrega</dt><dd>{{ $schedule[1]['delivery_label'] }}</dd></div>
                    <div><dt>Envío a</dt><dd>{{ $customer->locality }}, {{ $customer->province }}</dd></div>
                </dl>
                <a class="pm-client-repurchase" href="/?recomprar=1#elegir"><span>＋</span><div><b>Comprar otro producto</b><small>Usamos tus datos guardados</small></div><i>→</i></a>
            </div>
        </section>

        <section class="pm-client-calendar">
            <div class="pm-client-section-title"><div><span class="pm-portal-kicker">PRÓXIMOS CICLOS</span><h2>Calendario de pagos y entregas</h2></div><span>6 ciclos</span></div>
            <div class="pm-client-timeline">
                @foreach($schedule as $entry)
                    <article class="pm-client-cycle {{ $entry['is_paid'] ? 'is-complete' : '' }} {{ $entry['is_next'] ? 'is-next' : '' }}">
                        <div class="pm-client-date"><strong>{{ $entry['day'] }}</strong><span>{{ $entry['month'] }}</span></div>
                        <div class="pm-client-cycle__body">
                            <span>CICLO {{ str_pad($entry['cycle'], 2, '0', STR_PAD_LEFT) }}</span>
                            <h3>{{ $entry['is_paid'] ? 'Primer pago aprobado' : ($entry['is_next'] ? 'Próximo pago programado' : 'Pago programado') }}</h3>
                            <p><b>Pago:</b> {{ $entry['billing_label'] }}</p>
                            <p><b>Entrega estimada:</b> {{ $entry['delivery_label'] }}</p>
                        </div>
                        <div class="pm-client-cycle__state">{{ $entry['is_paid'] ? '✓ Pagado' : ($entry['is_next'] ? 'Próximo' : 'Programado') }}</div>
                    </article>
                @endforeach
            </div>
            <p class="pm-client-calendar__note">Las entregas se muestran como una ventana estimada de 3 a 7 días después de cada pago. En esta demo no se realizan débitos ni envíos reales.</p>
        </section>

        <section class="pm-client-community">
            <div><span class="pm-portal-kicker">COMUNIDAD PROMARINE</span><h2>Además de tu producto,<br>formás parte de algo más.</h2><p>Te avisaremos por correo cuando se publique contenido que elegiste seguir.</p></div>
            <div class="pm-client-community__choices">
                <article class="{{ !empty($community['podcasts']) ? 'is-enabled' : '' }}"><span>◉</span><div><b>Podcasts Promarine</b><small>{{ !empty($community['podcasts']) ? 'Notificaciones activadas' : 'Notificaciones no activadas' }}</small></div></article>
                <article class="{{ !empty($community['talks']) ? 'is-enabled' : '' }}"><span>✦</span><div><b>Charlas y encuentros</b><small>{{ !empty($community['talks']) ? 'Notificaciones activadas' : 'Notificaciones no activadas' }}</small></div></article>
            </div>
        </section>

        <div class="pm-client-footer"><img src="/assets/promarine/optimized/promarine-urchin-320.webp" width="320" height="295" alt=""><p>Tu calendario se actualiza automáticamente según el plan.</p></div>
    </main>
    <x-site-footer />
</div>
</x-layouts.app>
