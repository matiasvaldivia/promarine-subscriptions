<x-layouts.app title="Mi Promarine — verificar código">
<main class="pm-portal-auth is-verify">
    <a class="pm-portal-back" href="{{ route('customer.portal.request') }}">← Usar otro correo</a>
    <section class="pm-portal-verify-card">
        <img src="/assets/promarine/optimized/promarine-logo-300.webp" width="300" height="90" alt="Promarine">
        <div class="pm-portal-code-icon" aria-hidden="true">✉</div>
        <span class="pm-portal-kicker">REVISÁ TU CORREO</span>
        <h1>Ingresá el código</h1>
        <p>Enviamos seis números a <strong>{{ $maskedEmail }}</strong>. El código vence en 10 minutos.</p>
        @if(session('status'))<div class="pm-portal-message">{{ session('status') }}</div>@endif
        @if($demoCode)<div class="pm-portal-demo-code"><span>CÓDIGO DEMO LOCAL</span><strong>{{ $demoCode }}</strong><small>Este código aparece sólo para el correo ficticio de pruebas.</small></div>@endif
        @if($errors->any())<div class="pm-portal-error">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('customer.portal.verify-code') }}">
            @csrf
            <label class="pm-portal-code-field">Código de acceso
                <input type="text" name="code" value="{{ old('code') }}" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" placeholder="000000" required autofocus>
            </label>
            <button type="submit">Ver mi plan <span>→</span></button>
        </form>
        <form class="pm-portal-resend" method="POST" action="{{ route('customer.portal.send-code') }}">
            @csrf
            <input type="hidden" name="email" value="{{ session('customer_portal_pending_email') }}">
            <button type="submit">Enviar un código nuevo</button>
        </form>
    </section>
</main>
</x-layouts.app>
