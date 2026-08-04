<x-layouts.app>
<main class="mp-page" x-data="{ processing: false, method: 'card' }">
    <header class="mp-topbar">
        <a href="/" class="mp-promarine" aria-label="Volver a Promarine"><img src="/assets/promarine/optimized/promarine-logo-300.webp" width="300" height="90" alt="Promarine"></a>
        <div class="mp-brand"><span class="mp-brand__mark" aria-hidden="true">⌁</span><strong>mercado pago</strong></div>
    </header>

    <div class="mp-shell">
        <section class="mp-checkout-card">
            <div class="mp-demo-banner"><span aria-hidden="true">◆</span><div><strong>Pago de demostración</strong><small>No se solicitarán ni procesarán datos bancarios reales.</small></div></div>
            <a href="/" class="mp-back">← Volver a Promarine</a>
            <span class="mp-kicker">Suscripción mensual</span>
            <h1>¿Cómo querés pagar?</h1>

            <form method="post" action="{{ route('checkout.process', $subscription) }}" @submit.prevent="processing = true; setTimeout(() => $el.submit(), 1100)">
                @csrf
                <input type="hidden" name="mock_result" value="approved">

                <div class="mp-methods" role="radiogroup" aria-label="Medio de pago simulado">
                    <button type="button" class="mp-method" :class="method === 'card' ? 'is-selected' : ''" @click="method = 'card'" role="radio" :aria-checked="method === 'card'">
                        <span class="mp-method__icon">▰</span><span><b>Tarjeta de prueba</b><small>Visa terminada en 1234</small></span><i>✓</i>
                    </button>
                    <button type="button" class="mp-method" :class="method === 'balance' ? 'is-selected' : ''" @click="method = 'balance'" role="radio" :aria-checked="method === 'balance'">
                        <span class="mp-method__icon">$</span><span><b>Dinero de prueba disponible</b><small>Saldo simulado de Mercado Pago</small></span><i>✓</i>
                    </button>
                </div>

                <div class="mp-card-preview" x-show="method === 'card'">
                    <div><span>VISA</span><b>•••• •••• •••• 1234</b></div><small>DATOS FICTICIOS · NO ES UNA TARJETA REAL</small>
                </div>

                <div class="mp-security"><span aria-hidden="true">▣</span><p><b>Simulación protegida</b> Esta pantalla imita el recorrido de Mercado Pago. No captura números de tarjeta, códigos de seguridad ni credenciales.</p></div>
                <button class="mp-pay-button" type="submit">Pagar $ {{ number_format((float) $subscription->amount, 0, ',', '.') }}</button>
            </form>
        </section>

        <aside class="mp-order-card">
            <span class="mp-kicker">Resumen de compra</span>
            <img src="/assets/promarine/optimized/{{ $subscription->plan->variant->product->slug }}-composition-480.webp" width="480" height="600" alt="{{ $subscription->plan->variant->product->name }}">
            <h2>{{ $subscription->plan->variant->product->name }}</h2>
            <p>{{ $subscription->plan->variant->name }} · {{ $subscription->plan->name }}</p>
            <dl>
                <div><dt>Entrega</dt><dd>Cada {{ $subscription->frequency }} días</dd></div>
                <div><dt>Recibe</dt><dd>{{ $customer->name }}</dd></div>
                <div><dt>Dirección</dt><dd>{{ $customer->address }} {{ $customer->address_number }}, {{ $customer->locality }}</dd></div>
                <div class="mp-order-total"><dt>Total simulado</dt><dd>$ {{ number_format((float) $subscription->amount, 0, ',', '.') }}</dd></div>
            </dl>
        </aside>
    </div>

    <div class="mp-processing" x-show="processing" x-cloak x-transition.opacity aria-live="assertive">
        <div class="mp-spinner"></div><strong>Procesando pago simulado</strong><p>Estamos autorizando tu suscripción de demostración…</p>
    </div>
</main>
</x-layouts.app>
