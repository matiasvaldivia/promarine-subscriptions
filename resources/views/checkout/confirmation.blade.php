<x-layouts.app>
<main class="pm-success-page">
    <div class="pm-success-card">
        <img class="pm-success-logo" src="/assets/promarine/optimized/promarine-logo-300.webp" width="300" height="90" alt="Promarine">
        <span class="pm-success-demo">SIMULACIÓN · NO GENERÓ UN COBRO REAL</span>
        <div class="pm-success-check" aria-hidden="true"><span>✓</span></div>
        <span class="pm-success-kicker">Pago aprobado</span>
        <h1>¡Tu Promarine está en camino!</h1>
        <p class="pm-success-lead">Gracias, {{ $customer->name }}. Creamos tu suscripción y primer pedido de demostración.</p>

        <section class="pm-success-product">
            <img src="/assets/promarine/optimized/{{ $subscription->plan->variant->product->slug }}-composition-480.webp" width="480" height="600" alt="{{ $subscription->plan->variant->product->name }}">
            <div><small>Tu selección</small><h2>{{ $subscription->plan->variant->product->name }}</h2><p>{{ $subscription->plan->variant->name }} · cada {{ $subscription->frequency }} días</p><strong>$ {{ number_format((float) $subscription->amount, 0, ',', '.') }}</strong></div>
        </section>

        <div class="pm-success-status">
            <div><span>01</span><p><b>Pago autorizado</b><small>Operación simulada aprobada</small></p></div>
            <div><span>02</span><p><b>Pedido generado</b><small>{{ $order?->shopify_order_id }}</small></p></div>
            <div><span>03</span><p><b>Email {{ $mailSent ? 'enviado' : 'pendiente' }}</b><small>{{ $customer->email }}</small></p></div>
        </div>

        <div class="pm-success-notice {{ $mailSent ? 'is-sent' : 'is-pending' }}">
            <span aria-hidden="true">{{ $mailSent ? '✉' : '!' }}</span>
            <p><b>{{ $mailSent ? 'Revisá tu email' : 'La compra fue aprobada' }}</b>{{ $mailSent ? ' Enviamos el detalle de esta simulación y las próximas fechas.' : ' El servidor no pudo enviar el correo en este intento, pero el pedido simulado quedó registrado.' }}</p>
        </div>

        <p class="pm-success-reference">Referencia de pago: <code>{{ $payment->provider_payment_id }}</code></p>
        <div class="pm-success-actions">
            <a class="pm-button pm-success-home" href="{{ route('customer.portal.request') }}">Ver mi plan y calendario <span aria-hidden="true">→</span></a>
            <a class="pm-success-secondary" href="/">Volver al inicio</a>
        </div>
    </div>
</main>
</x-layouts.app>
