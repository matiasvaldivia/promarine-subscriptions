<x-layouts.app>
<main class="pm-login-page">
    <a href="/" class="pm-login-back">← Volver al sitio</a>
    <section class="pm-login-shell">
        <div class="pm-login-portrait">
            <img src="/assets/promarine/optimized/tamara-hero-768.webp" width="768" height="1152" alt="Tamara">
            <div><img src="/assets/promarine/optimized/promarine-logo-300.webp" width="300" height="90" alt="Promarine"><p>Panel privado de definiciones y decisiones.</p></div>
        </div>
        <form method="post" action="{{ route('login.submit') }}" class="pm-login-form" x-data="{ showPassword: false }">
            @csrf
            <span class="pm-login-pill">Acceso privado</span>
            <h1>Hola, Tamara</h1>
            <p>Ingresá para revisar preguntas, responder decisiones pendientes y consultar el historial.</p>
            @if($errors->any())<div class="pm-login-error" role="alert">{{$errors->first()}}</div>@endif
            <label><span>Usuario</span><input name="username" autocomplete="username" autocapitalize="none" spellcheck="false" value="{{ old('username') }}" required autofocus></label>
            <label><span>Contraseña</span><div class="pm-login-password"><input :type="showPassword ? 'text' : 'password'" name="password" autocomplete="current-password" required><button type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'" x-text="showPassword ? 'Ocultar' : 'Mostrar'"></button></div></label>
            <button type="submit">Entrar al panel <span aria-hidden="true">→</span></button>
            <small>Acceso protegido. La contraseña nunca se muestra ni se comparte.</small>
        </form>
    </section>
</main>
</x-layouts.app>
