<x-layouts.app title="Mi Promarine — acceso">
<main class="pm-portal-auth">
    <a class="pm-portal-back" href="/">← Volver a Promarine</a>
    <section class="pm-portal-auth__card">
        <div class="pm-portal-auth__visual">
            <img class="pm-portal-auth__logo" src="/assets/promarine/optimized/promarine-logo-300.webp" width="300" height="90" alt="Promarine">
            <div class="pm-portal-auth__orb"><img src="/assets/promarine/optimized/promarine-urchin-320.webp" width="320" height="295" alt=""></div>
            <div><span>MI PROMARINE</span><h1>Tu rutina,<br><em>siempre a mano.</em></h1><p>Consultá el plan, los pagos y las próximas entregas desde cualquier teléfono.</p></div>
        </div>
        <div class="pm-portal-auth__form">
            <span class="pm-portal-kicker">ACCESO DEL CLIENTE</span>
            <h2>Ingresá a tu plan</h2>
            <p>Escribí el mismo correo que usaste en la compra. Te enviaremos un código de seis números.</p>

            @if(session('status'))<div class="pm-portal-message">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="pm-portal-error">{{ $errors->first() }}</div>@endif

            <form method="POST" action="{{ route('customer.portal.send-code') }}">
                @csrf
                <label>Correo electrónico
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="tu@email.com" autocomplete="email" inputmode="email" required autofocus>
                </label>
                <button type="submit">Recibir código <span>→</span></button>
            </form>
            <small>El acceso es privado. No necesitás recordar una contraseña.</small>
            <p class="pm-portal-auth__help">¿Todavía no completaste la compra de demostración? <a href="/#elegir">Armá tu plan primero</a>.</p>
        </div>
    </section>
</main>
</x-layouts.app>
