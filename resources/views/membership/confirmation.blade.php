<x-layouts.app title="Solicitud de suscripción recibida">
<main class="pm-membership-confirmation">
    <section>
        <img src="/assets/promarine/optimized/promarine-logo-300.webp" width="300" height="90" alt="Promarine">
        <span class="pm-membership-confirmation__badge">DEMO · SIN COBRO</span>
        <div class="pm-membership-confirmation__check">✓</div>
        <small>SOLICITUD RECIBIDA</small>
        <h1>{{ $membership->name }}, ya registramos tu interés.</h1>
        <p>Tu solicitud de suscripción anual quedó guardada sin agregar productos, crear pedidos ni realizar pagos.</p>
        <dl><div><dt>Plan</dt><dd>Suscripción anual</dd></div><div><dt>Estado</dt><dd>Pendiente de confirmación</dd></div><div><dt>Productos</dt><dd>Ninguno</dd></div><div><dt>Cobro</dt><dd>$ 0 · demostración</dd></div></dl>
        <div class="pm-membership-confirmation__mail"><span>✉</span><p><b>Email {{ $mailSent ? 'enviado' : 'pendiente' }}</b><small>{{ $membership->email }}</small></p></div>
        <p class="pm-membership-confirmation__notice">Promarine deberá comunicarte el valor anual, los descuentos y las condiciones definitivas antes de activar una suscripción real.</p>
        <a class="pm-membership-button" href="/">Volver al inicio <span>→</span></a>
    </section>
</main>
</x-layouts.app>
