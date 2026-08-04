import './bootstrap';
import Alpine from 'alpinejs';
window.Alpine = Alpine;

Alpine.data('subscriptionWizard', (products = [], customerProfile = null, repurchaseMode = false) => ({
    products,
    customerProfile,
    repurchaseMode,
    useSavedCustomer: Boolean(customerProfile),
    step: 1,
    selectedProductId: products[0]?.id ?? null,
    selectedVariantId: products[0]?.variants?.[0]?.id ?? null,
    selectedPlanId: products[0]?.variants?.[0]?.plans?.[0]?.id ?? null,
    people: 1,
    dosesPerDay: 1,
    deliveryFrequency: 30,
    summaryOpen: false,
    wizardActive: false,
    navigatingForward: true,
    installAvailable: false,
    installPrompt: null,
    isStandalone: false,
    themeLight: localStorage.getItem('promarine-theme') !== 'dark',
    consentModalOpen: false,
    activeConsentKey: null,
    consentReturnFocus: null,
    calendarAtStart: true,
    calendarAtEnd: false,
    calendarDragging: false,
    calendarDragStartX: 0,
    calendarDragStartLeft: 0,
    communityOptIn: Boolean(customerProfile?.community?.member),
    notifyPodcasts: Boolean(customerProfile?.community?.podcasts),
    notifyTalks: Boolean(customerProfile?.community?.talks),
    frequencies: [15, 30, 45, 60],
    stepTitles: ['Producto', 'Presentación', 'Consumo', 'Frecuencia', 'Plan', 'Dirección y envío', 'Calendario'],
    consentDefinitions: {
        community: {
            eyebrow: 'COMUNIDAD PROMARINE',
            title: 'Ser parte de la comunidad',
            description: 'Aceptás recibir información educativa y novedades generales de Promarine en el correo registrado.',
            points: ['Es completamente opcional.', 'No modifica el precio ni las condiciones de tu plan.', 'Podés darte de baja de las comunicaciones cuando quieras.'],
        },
        podcasts: {
            eyebrow: 'NOTIFICACIONES OPCIONALES',
            title: 'Avisos de nuevos podcasts',
            description: 'Autorizás a Promarine a enviarte un correo cuando se publique un nuevo episodio o contenido de audio.',
            points: ['Solo se activa si también elegiste formar parte de la comunidad.', 'No implica una suscripción paga al podcast.', 'Podés desactivar estos avisos posteriormente.'],
        },
        talks: {
            eyebrow: 'NOTIFICACIONES OPCIONALES',
            title: 'Invitaciones a charlas y encuentros',
            description: 'Autorizás el envío de invitaciones a actividades educativas, encuentros y charlas de la comunidad Promarine.',
            points: ['La invitación no confirma automáticamente una inscripción.', 'Cada actividad puede tener condiciones propias.', 'Podés dejar de recibir invitaciones cuando quieras.'],
        },
        recurring: {
            eyebrow: 'AUTORIZACIÓN RECURRENTE',
            title: 'Cobros según el calendario',
            description: 'Aceptás que el medio de pago autorizado se utilice en cada fecha del calendario, por el importe y la frecuencia que seleccionaste.',
            points: ['Un pago rechazado no debe generar un nuevo pedido.', 'Las fechas e importes se muestran antes de continuar.', 'En esta demostración no se ejecutan débitos reales.'],
        },
        terms: {
            eyebrow: 'CONDICIONES DE SUSCRIPCIÓN',
            title: 'Términos del plan elegido',
            description: 'Confirmás que revisaste el producto, la presentación, la frecuencia, el importe y la duración mínima preliminar del plan.',
            points: ['Las condiciones mostradas corresponden a este plan.', 'Los términos definitivos deben estar disponibles antes de una compra real.', 'Este prototipo mantiene el carácter de demostración.'],
        },
        order: {
            eyebrow: 'GENERACIÓN DEL PEDIDO',
            title: 'Un pedido después de cada pago',
            description: 'Entendés que cada cobro aprobado genera un nuevo pedido del producto configurado para iniciar la preparación de la entrega.',
            points: ['El pedido se crea únicamente después de la aprobación del pago.', 'La dirección utilizada será la confirmada en el paso anterior.', 'En la demo los pedidos y entregas son simulados.'],
        },
        cancellation: {
            eyebrow: 'CANCELACIÓN Y CAMBIOS',
            title: 'Política de cancelación y modificaciones',
            description: 'Confirmás que revisaste las reglas informadas para cancelar, pausar o modificar el plan y sus próximas entregas.',
            points: ['Las reglas definitivas deben ser aprobadas y publicadas.', 'La política debe indicar plazos, reintegros y canales de contacto.', 'En este prototipo la política todavía se presenta como preliminar.'],
        },
    },

    get product() {
        return this.products.find((product) => product.id === this.selectedProductId) ?? null;
    },

    get activeConsent() {
        return this.consentDefinitions[this.activeConsentKey] ?? null;
    },

    get variants() {
        return this.product?.variants ?? [];
    },

    get variant() {
        return this.variants.find((variant) => variant.id === this.selectedVariantId) ?? null;
    },

    get plans() {
        return this.variant?.plans ?? [];
    },

    get plan() {
        return this.plans.find((plan) => plan.id === this.selectedPlanId) ?? null;
    },

    get durationDays() {
        const units = Number(this.variant?.units_per_package);
        const dailyConsumption = Number(this.people) * Number(this.dosesPerDay);

        if (!units || !dailyConsumption) {
            return this.variant?.estimated_days ?? null;
        }

        return Math.max(1, Math.floor(units / dailyConsumption));
    },

    get recommendedFrequency() {
        if (!this.durationDays) return null;
        return this.frequencies.reduce((closest, frequency) => (
            Math.abs(frequency - this.durationDays) < Math.abs(closest - this.durationDays) ? frequency : closest
        ));
    },

    get total() {
        return Number(this.plan?.amount ?? this.variant?.price ?? this.product?.base_price ?? 0);
    },

    get calendarEntries() {
        const minimumCycles = Number(this.plan?.minimum_cycles ?? 1);
        const cycleCount = minimumCycles === 1 ? 4 : Math.min(6, minimumCycles);
        const start = new Date();
        start.setHours(12, 0, 0, 0);

        return Array.from({ length: cycleCount }, (_, index) => {
            const billingDate = new Date(start);
            billingDate.setDate(start.getDate() + (index * Number(this.deliveryFrequency)));

            const deliveryStart = new Date(billingDate);
            deliveryStart.setDate(billingDate.getDate() + 3);

            const deliveryEnd = new Date(billingDate);
            deliveryEnd.setDate(billingDate.getDate() + 7);

            return {
                cycle: index + 1,
                day: billingDate.toLocaleDateString('es-AR', { day: '2-digit' }),
                month: billingDate.toLocaleDateString('es-AR', { month: 'short' }).replace('.', '').toUpperCase(),
                billingLabel: index === 0 ? 'Hoy, al autorizar' : this.formatCalendarDate(billingDate),
                deliveryLabel: `${this.formatCalendarDate(deliveryStart)} al ${this.formatCalendarDate(deliveryEnd)}`,
            };
        });
    },

    init() {
        this.isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        document.documentElement.classList.toggle('pm-standalone', this.isStandalone);
        this.applyTheme();
        this.$watch('themeLight', () => this._swapThemeImages());
        if (this.repurchaseMode) {
            sessionStorage.removeItem('promarine-subscription-draft');
        } else {
            this.restoreDraft();
        }
        this.$watch('wizardActive', () => this.syncScrollLock());
        ['step', 'selectedProductId', 'selectedVariantId', 'selectedPlanId', 'people', 'dosesPerDay', 'deliveryFrequency', 'communityOptIn', 'notifyPodcasts', 'notifyTalks', 'useSavedCustomer']
            .forEach((property) => this.$watch(property, () => this.saveDraft()));
        window.addEventListener('resize', () => this.syncScrollLock(), { passive: true });
        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            this.installPrompt = event;
            this.installAvailable = true;
        });
        window.addEventListener('appinstalled', () => {
            this.installAvailable = false;
            this.installPrompt = null;
        });
        this.syncScrollLock();
        if (this.repurchaseMode) {
            this.$nextTick(() => this.startWizard(1));
        }
    },

    applyTheme() {
        // Solo sincroniza estado — Alpine :class maneja el DOM
        this.themeLight = localStorage.getItem('promarine-theme') !== 'dark';
        this.$nextTick(() => this._swapThemeImages());
    },

    _swapThemeImages() {
        document.querySelectorAll('.pm-theme-image').forEach((image) => {
            const source = this.themeLight ? image.dataset.lightSrc : image.dataset.darkSrc;
            if (source && image.getAttribute('src') !== source) image.setAttribute('src', source);
        });
    },

    toggleTheme() {
        this.themeLight = !this.themeLight;
        localStorage.setItem('promarine-theme', this.themeLight ? 'light' : 'dark');
    },

    restoreDraft() {
        try {
            const draft = JSON.parse(sessionStorage.getItem('promarine-subscription-draft') ?? 'null');
            if (!draft) return;

            const savedProduct = this.products.find((product) => product.id === draft.selectedProductId);
            if (savedProduct) {
                this.selectedProductId = savedProduct.id;
                const savedVariant = savedProduct.variants?.find((variant) => variant.id === draft.selectedVariantId);
                this.selectedVariantId = savedVariant?.id ?? savedProduct.variants?.[0]?.id ?? null;
                const activeVariant = savedVariant ?? savedProduct.variants?.[0];
                this.selectedPlanId = activeVariant?.plans?.some((plan) => plan.id === draft.selectedPlanId)
                    ? draft.selectedPlanId
                    : activeVariant?.plans?.[0]?.id ?? null;
            }

            this.step = Math.min(7, Math.max(1, Number(draft.step) || 1));
            this.people = Math.min(3, Math.max(1, Number(draft.people) || 1));
            this.dosesPerDay = Math.min(6, Math.max(1, Number(draft.dosesPerDay) || 1));
            this.deliveryFrequency = this.frequencies.includes(Number(draft.deliveryFrequency)) ? Number(draft.deliveryFrequency) : 30;
            this.communityOptIn = Boolean(draft.communityOptIn);
            this.notifyPodcasts = this.communityOptIn && Boolean(draft.notifyPodcasts);
            this.notifyTalks = this.communityOptIn && Boolean(draft.notifyTalks);
            this.useSavedCustomer = Boolean(this.customerProfile) && draft.useSavedCustomer !== false;
        } catch {
            sessionStorage.removeItem('promarine-subscription-draft');
        }
    },

    saveDraft() {
        try {
            sessionStorage.setItem('promarine-subscription-draft', JSON.stringify({
                step: this.step,
                selectedProductId: this.selectedProductId,
                selectedVariantId: this.selectedVariantId,
                selectedPlanId: this.selectedPlanId,
                people: this.people,
                dosesPerDay: this.dosesPerDay,
                deliveryFrequency: this.deliveryFrequency,
                communityOptIn: this.communityOptIn,
                notifyPodcasts: this.notifyPodcasts,
                notifyTalks: this.notifyTalks,
                useSavedCustomer: this.useSavedCustomer,
            }));
        } catch {
            // La experiencia sigue funcionando aunque el navegador bloquee el almacenamiento.
        }
    },

    tap() {
        if ('vibrate' in navigator && window.matchMedia('(pointer: coarse)').matches) {
            navigator.vibrate(8);
        }
    },

    openConsentInfo(key, trigger = null) {
        if (!this.consentDefinitions[key]) return;
        this.tap();
        this.activeConsentKey = key;
        this.consentReturnFocus = trigger;
        this.consentModalOpen = true;
        this.$nextTick(() => document.querySelector('.pm-consent-modal__close')?.focus());
    },

    closeConsentInfo() {
        this.consentModalOpen = false;
        this.$nextTick(() => this.consentReturnFocus?.focus());
    },

    syncCalendarControls() {
        const calendar = this.$refs.calendar;
        if (!calendar) return;

        const maxScroll = Math.max(0, calendar.scrollWidth - calendar.clientWidth);
        this.calendarAtStart = calendar.scrollLeft <= 2;
        this.calendarAtEnd = maxScroll <= 2 || calendar.scrollLeft >= maxScroll - 2;
    },

    scrollCalendar(direction) {
        const calendar = this.$refs.calendar;
        if (!calendar) return;

        this.tap();
        const card = calendar.querySelector('.pm-calendar-card');
        const gap = Number.parseFloat(window.getComputedStyle(calendar).columnGap || window.getComputedStyle(calendar).gap) || 11;
        const distance = card ? card.getBoundingClientRect().width + gap : Math.max(260, calendar.clientWidth * 0.8);
        calendar.scrollBy({ left: direction * distance, behavior: 'smooth' });
        window.setTimeout(() => this.syncCalendarControls(), 360);
    },

    startCalendarDrag(event) {
        if (event.pointerType === 'mouse' && event.button !== 0) return;

        const calendar = this.$refs.calendar;
        if (!calendar) return;

        this.calendarDragging = true;
        this.calendarDragStartX = event.clientX;
        this.calendarDragStartLeft = calendar.scrollLeft;
        calendar.setPointerCapture?.(event.pointerId);
    },

    moveCalendarDrag(event) {
        if (!this.calendarDragging) return;

        const calendar = this.$refs.calendar;
        if (!calendar) return;

        const distance = event.clientX - this.calendarDragStartX;
        if (Math.abs(distance) > 3) event.preventDefault();
        calendar.scrollLeft = this.calendarDragStartLeft - distance;
        this.syncCalendarControls();
    },

    endCalendarDrag(event) {
        if (!this.calendarDragging) return;

        this.calendarDragging = false;
        this.$refs.calendar?.releasePointerCapture?.(event.pointerId);
        this.syncCalendarControls();
    },

    async installApp() {
        if (!this.installPrompt) return;
        this.tap();
        await this.installPrompt.prompt();
        await this.installPrompt.userChoice;
        this.installPrompt = null;
        this.installAvailable = false;
    },

    syncScrollLock() {
        const shouldLock = this.wizardActive && window.matchMedia('(max-width: 640px)').matches;
        document.documentElement.classList.toggle('pm-wizard-locked', shouldLock);
        document.body.classList.toggle('pm-wizard-locked', shouldLock);
    },

    startWizard(targetStep = null) {
        if (targetStep) this.step = targetStep;
        this.tap();
        this.wizardActive = true;
        this.summaryOpen = false;
        this.syncScrollLock();

        window.requestAnimationFrame(() => {
            const currentHeading = [...document.querySelectorAll('.pm-wizard__stage section h3')]
                .find((heading) => heading.offsetParent !== null);
            if (currentHeading) {
                currentHeading.setAttribute('tabindex', '-1');
                currentHeading.focus({ preventScroll: true });
            }
        });
    },

    closeWizard() {
        this.summaryOpen = false;
        this.wizardActive = false;
        this.syncScrollLock();
        window.requestAnimationFrame(() => document.querySelector('#elegir')?.scrollIntoView({ block: 'start' }));
    },

    chooseProduct(productId) {
        this.tap();
        this.wizardActive = true;
        this.syncScrollLock();
        this.selectedProductId = productId;
        this.selectedVariantId = this.product?.variants?.[0]?.id ?? null;
        this.selectedPlanId = this.variant?.plans?.[0]?.id ?? null;
    },

    chooseVariant(variantId) {
        this.tap();
        this.selectedVariantId = variantId;
        this.selectedPlanId = this.variant?.plans?.[0]?.id ?? null;
    },

    next(root) {
        if (!this.wizardActive) {
            this.wizardActive = true;
            this.syncScrollLock();
        }

        if (this.step === 6) {
            const invalidField = root.querySelector('.pm-wizard__stage section[style*="display: none"]')
                ? [...root.querySelectorAll('.pm-wizard__stage input[required], .pm-wizard__stage select[required]')]
                    .find((field) => field.offsetParent !== null && !field.checkValidity())
                : null;

            if (invalidField) {
                invalidField.reportValidity();
                return;
            }
        }

        if (this.step < 7) {
            this.tap();
            this.navigatingForward = true;
            this.step += 1;
            if (this.step === 7) {
                this.$nextTick(() => this.syncCalendarControls());
            }
            this.scrollToWizard();
        }
    },

    back() {
        if (this.step > 1) {
            this.tap();
            this.navigatingForward = false;
            this.step -= 1;
            this.scrollToWizard();
        }
    },

    scrollToWizard() {
        window.requestAnimationFrame(() => {
            const wizard = document.querySelector('.pm-wizard');
            const stage = wizard?.querySelector('.pm-wizard__stage');

            if (this.wizardActive && window.matchMedia('(max-width: 640px)').matches) {
                stage?.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                wizard?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            const currentHeading = [...(wizard?.querySelectorAll('.pm-wizard__stage section h3') ?? [])]
                .find((heading) => heading.offsetParent !== null);

            if (currentHeading) {
                currentHeading.setAttribute('tabindex', '-1');
                currentHeading.focus({ preventScroll: true });
            }
        });
    },

    money(value) {
        return `$ ${Number(value || 0).toLocaleString('es-AR')}`;
    },

    formatNumber(value) {
        return Number(value || 0).toLocaleString('es-AR', { maximumFractionDigits: 2 });
    },

    formatCalendarDate(value) {
        return value.toLocaleDateString('es-AR', { day: '2-digit', month: 'short', year: 'numeric' });
    },

    frequencyCopy(frequency) {
        return {
            15: 'Para consumos intensivos',
            30: 'Ritmo mensual',
            45: 'Más espaciado',
            60: 'Cada dos meses',
        }[frequency];
    },
}));

Alpine.start();

if ('serviceWorker' in navigator && (window.isSecureContext || window.location.hostname === 'localhost')) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}

const urchin = document.querySelector('[data-scroll-urchin]');
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

if (urchin && !reduceMotion.matches) {
    let latestScroll = window.scrollY;
    let ticking = false;

    const renderUrchin = () => {
        urchin.style.setProperty('--urchin-angle', `${latestScroll * 0.32}deg`);
        ticking = false;
    };

    window.addEventListener('scroll', () => {
        latestScroll = window.scrollY;

        if (!ticking) {
            window.requestAnimationFrame(renderUrchin);
            ticking = true;
        }
    }, { passive: true });
}
