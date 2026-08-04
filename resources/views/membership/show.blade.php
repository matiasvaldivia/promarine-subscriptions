<x-layouts.app title="Plan anual Promarine">
<main class="pm-membership">
    <header class="pm-membership-nav">
        <a href="/" aria-label="Volver al inicio"><img src="/assets/promarine/optimized/promarine-logo-300.webp" width="300" height="90" alt="Promarine"></a>
        <a href="#sumarme">Quiero ser miembro <span aria-hidden="true">→</span></a>
    </header>

    <section class="pm-membership-hero">
        <div class="pm-membership-hero__copy">
            <span class="pm-membership-eyebrow">MEMBRESÍA ANUAL · SIN COMPRA DE PRODUCTO</span>
            <h1>Un año más cerca de la <em>comunidad Promarine.</em></h1>
            <p>Sumate al programa anual para acceder a beneficios, prioridad e información para miembros. Elegís tus productos después, solamente cuando vos quieras.</p>
            <div class="pm-membership-hero__actions">
                <a class="pm-membership-button" href="#sumarme">Obtener mi membresía <span>→</span></a>
                <small>Solicitud de demostración · no genera cobros</small>
            </div>
        </div>
        <div class="pm-membership-visual" aria-label="Beneficios alrededor de la identidad Promarine">
            <div class="pm-membership-rings"></div>
            <img class="pm-membership-urchin" src="/assets/promarine/optimized/promarine-urchin-320.webp" width="320" height="320" alt="Símbolo Promarine">
            <article class="pm-membership-orbit pm-membership-orbit--one"><span>01</span><b>Descuentos</b><small>en productos elegidos por vos</small></article>
            <article class="pm-membership-orbit pm-membership-orbit--two"><span>02</span><b>Prioridad</b><small>en la gestión de entregas</small></article>
            <article class="pm-membership-orbit pm-membership-orbit--three"><span>03</span><b>Contenido</b><small>para miembros</small></article>
        </div>
    </section>

    <section class="pm-membership-benefits" aria-labelledby="beneficios">
        <div class="pm-membership-heading"><span>BENEFICIOS DEL PLAN ANUAL</span><h2 id="beneficios">Tu membresía acompaña. <em>Vos decidís cuándo comprar.</em></h2></div>
        <div class="pm-membership-benefit-grid">
            <article><span class="pm-membership-benefit-icon">%</span><small>BENEFICIO 01</small><h3>Precios para miembros</h3><p>Accedé a descuentos exclusivos al elegir productos Promarine durante la vigencia anual.</p><b>El porcentaje definitivo se informa antes de activar el plan.</b></article>
            <article><span class="pm-membership-benefit-icon">↟</span><small>BENEFICIO 02</small><h3>Entregas prioritarias</h3><p>Tus compras voluntarias reciben atención prioritaria en la preparación y coordinación logística.</p><b>Sujeto a stock, operador y zona de entrega.</b></article>
            <article><span class="pm-membership-benefit-icon">◎</span><small>BENEFICIO 03</small><h3>Información exclusiva</h3><p>Recibí guías, novedades, podcasts e invitaciones a charlas de la comunidad Promarine.</p><b>Vos elegís qué comunicaciones recibir.</b></article>
        </div>
    </section>

    <section class="pm-membership-clarity">
        <div class="pm-membership-heading"><span>SIMPLE Y TRANSPARENTE</span><h2>Una membresía. <em>Ningún producto agregado.</em></h2></div>
        <div class="pm-membership-clarity__grid">
            <article class="is-included"><span>✓</span><div><small>SÍ INCLUYE</small><h3>Acceso anual a beneficios</h3><ul><li>Descuentos reservados para miembros</li><li>Prioridad en compras futuras</li><li>Información y actividades exclusivas</li></ul></div></article>
            <article class="is-excluded"><span>×</span><div><small>NO INCLUYE</small><h3>Productos ni envíos automáticos</h3><ul><li>No agrega productos al carrito</li><li>No crea pedidos recurrentes</li><li>No realiza un cobro en esta demostración</li></ul></div></article>
        </div>
    </section>

    <section class="pm-membership-steps">
        <div class="pm-membership-heading"><span>ASÍ FUNCIONA</span><h2>Tres pasos, <em>sin compra obligatoria.</em></h2></div>
        <div class="pm-membership-step-grid">
            <article><b>01</b><div><h3>Te sumás</h3><p>Registrás tu nombre y correo para solicitar la membresía anual.</p></div></article>
            <article><b>02</b><div><h3>Recibís la confirmación</h3><p>Te informamos el valor y las condiciones definitivas antes de cualquier activación real.</p></div></article>
            <article><b>03</b><div><h3>Usás tus beneficios</h3><p>Cuando quieras comprar, elegís el producto y aplicás tus ventajas como miembro.</p></div></article>
        </div>
    </section>

    <section id="sumarme" class="pm-membership-enroll">
        <div class="pm-membership-enroll__summary">
            <span class="pm-membership-eyebrow">PLAN ANUAL PROMARINE</span>
            <h2>Solicitá tu membresía.</h2>
            <p>Esta versión permite probar la adhesión sin comprar productos y sin ingresar un medio de pago.</p>
            <dl><div><dt>Duración</dt><dd>12 meses</dd></div><div><dt>Productos incluidos</dt><dd>Ninguno</dd></div><div><dt>Valor anual</dt><dd>Por definir</dd></div><div><dt>Cobro ahora</dt><dd>$ 0 · DEMO</dd></div></dl>
        </div>
        <form class="pm-membership-form" method="POST" action="{{ route('membership.store') }}">
            @csrf
            <div><span>PASO ÚNICO</span><h2>Datos de la persona miembro</h2><p>Te enviaremos el comprobante de la solicitud a este correo.</p></div>
            @if($errors->any())<div class="pm-membership-errors" role="alert">Revisá los campos marcados antes de continuar.</div>@endif
            <label>Nombre completo<input name="name" value="{{ old('name') }}" autocomplete="name" required placeholder="Tu nombre"></label>
            <label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required placeholder="tu@email.com"></label>
            <label>Teléfono <small>opcional</small><input name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" placeholder="Tu teléfono"></label>
            <label class="pm-membership-check"><input type="checkbox" name="community_updates" value="1" @checked(old('community_updates'))><span><b>Quiero recibir información para miembros</b><small>Podcasts, charlas, guías y novedades. Es opcional.</small></span></label>
            <label class="pm-membership-check"><input type="checkbox" name="consent_terms" value="1" required @checked(old('consent_terms'))><span><b>Acepto registrar esta solicitud anual</b><small>Entiendo que no incluye productos, no crea pedidos y no genera un cobro real.</small></span></label>
            @error('consent_terms')<small class="pm-membership-field-error">{{ $message }}</small>@enderror
            <button class="pm-membership-button" type="submit">Quiero mi membresía anual <span>→</span></button>
            <p class="pm-membership-form__safe">Sin tarjeta · sin producto · sin cobro real</p>
        </form>
    </section>

    <x-site-footer />
</main>
</x-layouts.app>
