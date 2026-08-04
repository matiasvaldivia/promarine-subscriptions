@php
    $firstProduct = $products->first();
    $firstPlan = $firstProduct?->variants->first()?->plans->first();
    $productMeta = [
        'marine-epic' => ['accent' => 'epic', 'kicker' => 'Energía y defensas'],
        'marine-fusion' => ['accent' => 'fusion', 'kicker' => 'Omega-3 marino'],
        'echa-marine' => ['accent' => 'echa', 'kicker' => 'Defensas y bienestar'],
        'marine-pulse' => ['accent' => 'pulse', 'kicker' => 'Vitalidad con vitamina C'],
    ];
    $orbitLabels = ['Nutrición avanzada', 'Fórmulas inteligentes', 'Máxima absorción', 'Práctico y eficiente'];
    $trustItems = [
        ['src' => '/assets/promarine/optimized/trust/conicet-240.webp', 'alt' => 'CONICET'],
        ['src' => '/assets/promarine/optimized/trust/seal-gmp-240.webp', 'alt' => 'Recurso visual GMP de demostración'],
        ['src' => '/assets/promarine/optimized/trust/seal-heavy-metals-tested-240.webp', 'alt' => 'Recurso visual de metales pesados de demostración'],
        ['src' => '/assets/promarine/optimized/trust/seal-non-gmo-240.webp', 'alt' => 'Recurso visual non-GMO de demostración'],
        ['src' => '/assets/promarine/optimized/trust/seal-cruelty-free-240.webp', 'alt' => 'Recurso visual cruelty-free de demostración'],
        ['src' => '/assets/promarine/optimized/trust/seal-gluten-free-240.webp', 'alt' => 'Recurso visual libre de gluten de demostración'],
        ['src' => '/assets/promarine/optimized/trust/seal-clinically-tested-240.webp', 'alt' => 'Recurso visual clínicamente testeado de demostración'],
        ['src' => '/assets/promarine/optimized/trust/icon-respiratory-support-240.webp', 'alt' => 'Recurso visual de soporte respiratorio de demostración'],
    ];
    $wizardProducts = $products->map(fn ($product) => [
        'id' => $product->id,
        'name' => $product->name,
        'slug' => $product->slug,
        'description' => $product->short_description,
        'image' => '/assets/promarine/optimized/'.$product->slug.'-composition-480.webp',
        'base_price' => (float) $product->reference_price,
        'variants' => $product->variants->where('enabled', true)->values()->map(fn ($variant) => [
            'id' => $variant->id,
            'name' => $variant->name,
            'type' => $variant->type,
            'units_per_package' => $variant->units_per_package ? (float) $variant->units_per_package : null,
            'unit_measure' => $variant->unit_measure,
            'recommended_daily_dose' => $variant->recommended_daily_dose ? (float) $variant->recommended_daily_dose : null,
            'estimated_days' => $variant->estimated_days,
            'price' => $variant->price ? (float) $variant->price : (float) $product->reference_price,
            'image' => in_array($variant->type, ['botella', 'monodosis'], true)
                ? '/assets/promarine/optimized/'.$product->slug.'-'.($variant->type === 'botella' ? 'bottle' : 'box').'-480.webp'
                : '/assets/promarine/optimized/'.$product->slug.'-composition-480.webp',
            'stock' => $variant->simulated_stock,
            'plans' => $variant->plans->where('enabled', true)->values()->map(fn ($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'amount' => (float) $plan->amount,
                'discount' => (float) $plan->discount_value,
                'minimum_cycles' => $plan->minimum_cycles,
                'can_pause' => (bool) $plan->can_pause,
                'can_cancel' => (bool) $plan->can_cancel,
            ])->all(),
        ])->all(),
    ])->values()->all();
@endphp

<x-layouts.app>
<div
    class="pm-site"
    :class="themeLight ? '' : 'pm-theme-dark'"
    x-data="subscriptionWizard(@js($wizardProducts), @js($customerProfile), @js($portalRepurchase))"
>
    <header class="pm-header" :class="wizardActive ? 'is-purchase-active' : ''">
        <div class="pm-container pm-header__inner">
            <a href="#inicio" class="pm-header__brand" aria-label="Promarine — inicio">
                <span class="pm-header__urchin" aria-hidden="true"><img
                    src="/assets/promarine/optimized/promarine-urchin-320.webp"
                    class="pm-theme-image"
                    data-dark-src="/assets/promarine/optimized/promarine-urchin-320.webp"
                    data-light-src="/assets/promarine/brand/promarine-sea-urchin-mark-black.png"
                    width="320" height="295" alt="" fetchpriority="high" decoding="async"></span>
                <img class="pm-header__wordmark pm-theme-image"
                    src="/assets/promarine/optimized/promarine-logo-300.webp"
                    data-dark-src="/assets/promarine/optimized/promarine-logo-300.webp"
                    data-light-src="/assets/promarine/brand/promarine-logo-dark.svg"
                    width="300" height="90" alt="Promarine" fetchpriority="high" decoding="async">
            </a>
            <nav class="pm-header__nav" aria-label="Navegación principal">
                <a href="#faq">Preguntas</a>
                <a href="/mi-plan" class="pm-client-access" x-show="!wizardActive" aria-label="Consultar mi plan Promarine"><span aria-hidden="true">◷</span><b>Mi plan</b></a>
                <button type="button" class="pm-install-button" x-show="installAvailable && !wizardActive" x-cloak @click="installApp()" aria-label="Instalar Promarine en este dispositivo">Instalar</button>
                <button type="button" class="pm-theme-toggle" x-show="!wizardActive" @click="toggleTheme()" :aria-label="themeLight ? 'Activar tema oscuro' : 'Activar tema claro'" :title="themeLight ? 'Activar tema oscuro' : 'Activar tema claro'"><span x-text="themeLight ? '☾' : '☀'" aria-hidden="true"></span><b x-text="themeLight ? 'Oscuro' : 'Claro'"></b></button>
                <button type="button" class="pm-header-summary" x-show="wizardActive" @click="tap(); summaryOpen = true" aria-label="Abrir resumen de la configuración">Resumen · <strong x-text="money(total)"></strong></button>
                <button type="button" class="pm-header-exit" x-show="wizardActive" @click="tap(); closeWizard()" aria-label="Salir del proceso de compra">×</button>
                <a href="#elegir" class="pm-menu" x-show="!wizardActive" aria-label="Ir al catálogo" @click="startWizard()"><span></span><span></span><span></span></a>
            </nav>
        </div>
    </header>

    <main>
        <section class="pm-hero" id="inicio">
            <div class="pm-water-layer" aria-hidden="true"></div>
            <div class="pm-caustics" aria-hidden="true"></div>
            <div class="pm-water-lines" aria-hidden="true"></div>

            <div class="pm-container pm-hero__grid">
                <div class="pm-hero__content">
                    <img class="pm-hero__tamara" src="/assets/promarine/optimized/tamara-hero-768.webp" width="768" height="1152" alt="" aria-hidden="true" fetchpriority="high" decoding="async">
                    <span class="pm-eyebrow">Demo interactiva · sin cobros</span>
                    <h1>Tu Promarine,<em>todos los meses</em></h1>
                    <p>Recibí tu producto automáticamente y mantené la continuidad de tu rutina.</p>
                    <ul class="pm-benefits">
                        <li>Ahorro configurable</li>
                        <li>Entrega cada 30 días</li>
                        <li>Gestión simple</li>
                        <li class="pm-benefits__pending">Cancelación a definir</li>
                    </ul>
                    <a href="#elegir" class="pm-button" @click="startWizard()">Elegir mi suscripción <span aria-hidden="true">→</span></a>
                </div>

                <div class="pm-orbit" data-orbit>
                    <div class="pm-orbit__rings" aria-hidden="true"></div>
                    <img
                        src="/assets/promarine/optimized/promarine-urchin-320.webp"
                        alt=""
                        class="pm-urchin pm-theme-image"
                        data-scroll-urchin
                        data-dark-src="/assets/promarine/optimized/promarine-urchin-320.webp"
                        data-light-src="/assets/promarine/brand/promarine-sea-urchin-mark-black.png"
                        width="320"
                        height="295"
                        decoding="async"
                    >
                    @foreach($products->take(4) as $product)
                        <button
                            type="button"
                            class="pm-orbit-card pm-orbit-card--{{ $loop->iteration }}"
                            @click="chooseProduct({{ $product->id }}); startWizard(2)"
                            aria-label="Seleccionar {{ $product->name }}"
                        >
                            <strong>{{ $orbitLabels[$loop->index] }}</strong>
                            <img class="pm-theme-image" src="/assets/promarine/optimized/{{ $product->slug }}-composition-480.webp" data-dark-src="/assets/promarine/optimized/{{ $product->slug }}-composition-480.webp" data-light-src="/assets/promarine/products/{{ $product->slug }}-packshot-square.png" width="480" height="600" alt="" loading="{{ $loop->first ? 'eager' : 'lazy' }}" fetchpriority="{{ $loop->first ? 'high' : 'low' }}" decoding="async">
                            <span>{{ $product->name }}</span>
                        </button>
                    @endforeach
                    <a href="#elegir" class="pm-scroll-cue"><span aria-hidden="true">↓</span> Scroll para explorar</a>
                </div>
            </div>
        </section>

        @include('landing.wizard')

        <section class="pm-section pm-how">
            <div class="pm-container">
                <header class="pm-section__header pm-section__header--center"><span>Flujo mensual</span><h2>Así funcionaría</h2></header>
                <div class="pm-how__grid">
                    @foreach([
                        ['Elegís tu producto.', '▣'],
                        ['Autorizás el pago recurrente.', '▤'],
                        ['Recibís un nuevo pedido después de cada cobro aprobado.', '◇'],
                    ] as $i => $step)
                        <article class="pm-how-card"><span class="pm-how-card__number">0{{ $i + 1 }}</span><p>{{ $step[0] }}</p><span class="pm-how-card__icon" aria-hidden="true">{{ $step[1] }}</span></article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="pm-certifications" aria-labelledby="trust-title">
            <div class="pm-certifications__header"><p id="trust-title">Recursos visuales que nos acompañan</p><small>Los sellos de demostración están sujetos a validación documental.</small></div>
            <div class="pm-marquee">
                <div class="pm-marquee__track">
                    @foreach(array_merge($trustItems, $trustItems) as $item)
                        <div class="pm-cert-item" @if($loop->iteration > count($trustItems)) aria-hidden="true" @endif><img src="{{ $item['src'] }}" width="240" height="160" alt="{{ $loop->iteration <= count($trustItems) ? $item['alt'] : '' }}" loading="lazy" decoding="async"></div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="faq" class="pm-section pm-faq">
            <div class="pm-container">
                <header class="pm-section__header"><span>Información preliminar</span><h2>Preguntas frecuentes</h2><p>Las respuestas definitivas se aprobarán desde la entrevista interna.</p></header>
                <div class="pm-faq-grid">
                    @foreach(['¿Cuándo se realiza el cobro?', '¿Cuándo se genera el pedido?', '¿Puedo cancelar?', '¿Puedo cambiar el producto?', '¿Qué pasa si el pago falla?', '¿Puedo cambiar la dirección?', '¿El precio puede cambiar?', '¿El envío está incluido?', '¿Qué pasa si no hay stock?', '¿Cómo actualizo mi medio de pago?'] as $faq)
                        <details><summary>{{ $faq }}<span aria-hidden="true">⌄</span></summary><p>Respuesta pendiente de aprobación. Documento preliminar para revisión interna.</p></details>
                    @endforeach
                </div>
                <div class="pm-policy-list">@foreach($policies as $policy)<span>{{ $policy->title }} · borrador</span>@endforeach</div>
            </div>
        </section>

        <section class="pm-final-cta">
            <div class="pm-container"><img src="/assets/promarine/optimized/promarine-urchin-320.webp" class="pm-theme-image" data-dark-src="/assets/promarine/optimized/promarine-urchin-320.webp" data-light-src="/assets/promarine/brand/promarine-sea-urchin-mark-black.png" width="320" height="295" alt="" loading="lazy" decoding="async"><div><span>Rutina marina, gestión simple</span><h2>Elegí tu Promarine.</h2></div><a href="#elegir" class="pm-button" @click="startWizard()">Comenzar la simulación <span aria-hidden="true">→</span></a></div>
        </section>
    </main>

    <x-site-footer />

</div>
</x-layouts.app>
