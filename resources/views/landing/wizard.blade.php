<section id="elegir" class="pm-section pm-wizard-section" :class="wizardActive ? 'is-active' : ''" @keydown.escape.window="consentModalOpen ? closeConsentInfo() : (summaryOpen ? summaryOpen = false : closeWizard())">
    <div class="pm-container">
        <header class="pm-section__header pm-section__header--split">
            <div><span>Asesor de suscripción · demo</span><h2>Armá tu plan paso a paso</h2><p>Elegí cómo consumís y te ayudamos a calcular duración, frecuencia y ahorro sin inventar datos pendientes.</p></div>
            <span class="pm-demo-pill">No genera cobros reales</span>
        </header>

        <div class="pm-wizard">
            <div class="pm-wizard__progress" aria-label="Progreso del formulario" aria-live="polite">
                <div class="pm-wizard__progress-copy">
                    <div><strong x-text="`Paso ${step} de 7`"></strong><span x-text="stepTitles[step - 1]"></span></div>
                </div>
                <div class="pm-wizard__progress-bar"><span :style="`width:${(step / 7) * 100}%`"></span></div>
                <div class="pm-wizard__steps" aria-hidden="true"><template x-for="number in 7" :key="number"><span :class="number <= step ? 'is-complete' : ''" x-text="String(number).padStart(2, '0')"></span></template></div>
            </div>

            <form method="post" action="{{ route('checkout.simulate') }}" class="pm-wizard__form">
                @csrf
                <input type="hidden" name="plan_id" :value="selectedPlanId">
                <input type="hidden" name="people" :value="people">
                <input type="hidden" name="doses_per_day" :value="dosesPerDay">
                <input type="hidden" name="delivery_frequency" :value="deliveryFrequency">
                @if($customerProfile)
                    <input type="hidden" name="use_saved_customer" :value="useSavedCustomer ? 1 : 0">
                @endif

                <div class="pm-wizard__layout">
                    <div class="pm-wizard__stage" :class="navigatingForward ? 'is-forward' : 'is-backward'">
                        <section x-show="step === 1" x-transition:enter="pm-step-enter" x-transition:enter-start="pm-step-enter-start" x-transition:enter-end="pm-step-enter-end" x-transition:leave="pm-step-leave" x-transition:leave-start="pm-step-leave-start" x-transition:leave-end="pm-step-leave-end">
                            <div class="pm-wizard__question"><span>01 · Producto</span><h3>¿Qué producto querés?</h3><p>Mostramos solamente productos activos del catálogo local.</p></div>
                            <div class="pm-choice-grid pm-choice-grid--products">
                                <template x-for="product in products" :key="product.id">
                                    <button type="button" class="pm-choice-card pm-choice-card--product" :class="selectedProductId === product.id ? 'is-selected' : ''" @click="chooseProduct(product.id)">
                                        <img :src="product.image" :alt="product.name" width="480" height="600" loading="lazy" decoding="async">
                                        <div><strong x-text="product.name"></strong><p x-text="product.description"></p><small x-text="`${product.variants.length} ${product.variants.length === 1 ? 'presentación disponible' : 'presentaciones disponibles'}`"></small><b x-text="money(product.base_price)"></b></div>
                                        <span class="pm-choice-card__check">✓</span>
                                    </button>
                                </template>
                            </div>
                            <p class="pm-mobile-swipe-hint" aria-hidden="true">Deslizá para ver los 4 productos →</p>
                        </section>

                        <section x-show="step === 2" x-transition:enter="pm-step-enter" x-transition:enter-start="pm-step-enter-start" x-transition:enter-end="pm-step-enter-end" x-transition:leave="pm-step-leave" x-transition:leave-start="pm-step-leave-start" x-transition:leave-end="pm-step-leave-end" x-cloak>
                            <div class="pm-wizard__question"><span>02 · Presentación</span><h3>¿Cómo preferís tomarlo?</h3><p>Solo aparecen las presentaciones registradas para <b x-text="product?.name"></b>.</p></div>
                            <div class="pm-choice-grid">
                                <template x-for="variant in variants" :key="variant.id">
                                    <button type="button" class="pm-choice-card pm-choice-card--presentation" :class="selectedVariantId === variant.id ? 'is-selected' : ''" @click="chooseVariant(variant.id)">
                                        <img :src="variant.image" alt="" width="480" height="480" loading="lazy" decoding="async">
                                        <div><strong x-text="variant.name"></strong><p><span x-text="variant.units_per_package ? `${formatNumber(variant.units_per_package)} ${variant.unit_measure || 'unidades'}` : 'Contenido por confirmar'"></span><br><span x-text="variant.estimated_days ? `${variant.estimated_days} días estimados` : 'Duración por confirmar'"></span></p><small x-text="variant.stock > 0 ? 'Disponible en demo' : 'Sin stock simulado'"></small></div>
                                        <span class="pm-choice-card__check">✓</span>
                                    </button>
                                </template>
                            </div>
                            <div class="pm-data-warning">Tamara debe validar cantidad de porciones, dosis recomendada, duración real, peso e imagen final de cada presentación.</div>
                        </section>

                        <section x-show="step === 3" x-transition:enter="pm-step-enter" x-transition:enter-start="pm-step-enter-start" x-transition:enter-end="pm-step-enter-end" x-transition:leave="pm-step-leave" x-transition:leave-start="pm-step-leave-start" x-transition:leave-end="pm-step-leave-end" x-cloak>
                            <div class="pm-wizard__question"><span>03 · Consumo</span><h3>¿Cuánto van a consumir?</h3><p>Con datos validados, esta combinación determina cuánto dura cada envase.</p></div>
                            <fieldset class="pm-option-group"><legend>¿Cuántas personas?</legend><div class="pm-segmented"><template x-for="option in [1,2,3]" :key="option"><button type="button" :class="people === option ? 'is-selected' : ''" @click="people = option" x-text="`${option} ${option === 1 ? 'persona' : 'personas'}`"></button></template></div></fieldset>
                            <label class="pm-field pm-field--wizard"><span>¿Cuántas dosis por día por persona?</span><select x-model.number="dosesPerDay"><option value="0.5">½ dosis</option><option value="1">1 dosis</option><option value="2">2 dosis</option><option value="3">3 dosis</option></select></label>
                            <div class="pm-calculation-card"><span>Duración estimada</span><strong x-text="durationDays ? `${durationDays} días` : 'Pendiente de validación'"></strong><p x-text="durationDays ? `${formatNumber(variant.units_per_package)} dosis ÷ ${people * dosesPerDay} dosis diarias totales.` : 'No calculamos una duración hasta completar contenido y dosis reales en el dashboard.'"></p></div>
                        </section>

                        <section x-show="step === 4" x-transition:enter="pm-step-enter" x-transition:enter-start="pm-step-enter-start" x-transition:enter-end="pm-step-enter-end" x-transition:leave="pm-step-leave" x-transition:leave-start="pm-step-leave-start" x-transition:leave-end="pm-step-leave-end" x-cloak>
                            <div class="pm-wizard__question"><span>04 · Frecuencia</span><h3>¿Cada cuánto querés recibirlo?</h3><p x-text="durationDays ? `Tu presentación dura aproximadamente ${durationDays} días.` : 'La recomendación automática está pendiente porque faltan datos de dosificación.'"></p></div>
                            <div class="pm-frequency-grid"><template x-for="frequency in frequencies" :key="frequency"><button type="button" :class="deliveryFrequency === frequency ? 'is-selected' : ''" @click="deliveryFrequency = frequency"><small x-show="durationDays && recommendedFrequency === frequency">Recomendado</small><strong x-text="`Cada ${frequency} días`"></strong><span x-text="frequencyCopy(frequency)"></span></button></template></div>
                            <div class="pm-management-list"><span>Omitir próxima entrega</span><span>Pausar</span><span>Cambiar fecha</span><span>Cambiar producto</span><span>Cambiar cantidad</span></div>
                        </section>

                        <section x-show="step === 5" x-transition:enter="pm-step-enter" x-transition:enter-start="pm-step-enter-start" x-transition:enter-end="pm-step-enter-end" x-transition:leave="pm-step-leave" x-transition:leave-start="pm-step-leave-start" x-transition:leave-end="pm-step-leave-end" x-cloak>
                            <div class="pm-wizard__question"><span>05 · Plan</span><h3>Elegí tu nivel de suscripción</h3><p>Matriz preliminar administrable. Los porcentajes requieren aprobación de Tamara.</p></div>
                            <div class="pm-plan-grid"><template x-for="planOption in plans" :key="planOption.id"><button type="button" class="pm-plan-card" :class="selectedPlanId === planOption.id ? 'is-selected' : ''" @click="selectedPlanId = planOption.id"><span x-show="planOption.minimum_cycles === 1">Más flexible</span><h4 x-text="planOption.name"></h4><strong x-text="`${formatNumber(planOption.discount)}%`"></strong><p x-text="planOption.minimum_cycles === 1 ? 'Cancelable · sin compromiso definido' : `${planOption.minimum_cycles} entregas mínimas preliminares`"></p><b x-text="`${money(planOption.amount)} por ciclo`"></b><small x-text="`${planOption.can_pause ? 'Permite pausa' : 'Sin pausa'} · ${planOption.can_cancel ? 'Permite cancelar' : 'Compromiso preliminar'}`"></small></button></template></div>
                            <div class="pm-data-warning">No se aplican códigos promocionales en el cálculo hasta definir si acumulan, reemplazan o solo atribuyen comisión.</div>
                        </section>

                        <section x-show="step === 6" x-transition:enter="pm-step-enter" x-transition:enter-start="pm-step-enter-start" x-transition:enter-end="pm-step-enter-end" x-transition:leave="pm-step-leave" x-transition:leave-start="pm-step-leave-start" x-transition:leave-end="pm-step-leave-end" x-cloak>
                            <div class="pm-wizard__question"><span>06 · Dirección y envío</span><h3>{{ $customerProfile ? 'Confirmá dónde lo recibís' : '¿Dónde querés recibirlo?' }}</h3><p>{{ $customerProfile ? 'Ya guardamos estos datos desde tu acceso verificado. No necesitás cargarlos otra vez.' : 'El operador, costo y plazo permanecen por confirmar.' }}</p></div>

                            @if($customerProfile)
                                <div class="pm-saved-customer" x-show="useSavedCustomer">
                                    @foreach(['name', 'email', 'phone', 'province', 'locality', 'postal_code', 'address', 'address_number', 'apartment', 'address_reference'] as $profileField)
                                        <input type="hidden" name="{{ $profileField }}" value="{{ $customerProfile[$profileField] }}" :disabled="!useSavedCustomer">
                                    @endforeach
                                    <div class="pm-saved-customer__icon" aria-hidden="true">✓</div>
                                    <div class="pm-saved-customer__body">
                                        <span>DATOS GUARDADOS</span>
                                        <h4>{{ $customerProfile['name'] }}</h4>
                                        <p>{{ $customerProfile['address'] }} {{ $customerProfile['address_number'] }}{{ $customerProfile['apartment'] ? ', '.$customerProfile['apartment'] : '' }}<br>{{ $customerProfile['locality'] }}, {{ $customerProfile['province'] }} · CP {{ $customerProfile['postal_code'] }}</p>
                                        <small>{{ $customerProfile['email'] }} · {{ $customerProfile['phone'] }}</small>
                                    </div>
                                    <button type="button" @click="useSavedCustomer = false">Usar otra dirección</button>
                                </div>
                            @endif

                            <div class="pm-form-grid" @if($customerProfile) x-show="!useSavedCustomer" @endif>
                                <label class="pm-field"><span>Nombre completo</span><input name="name" autocomplete="name" placeholder="Tu nombre" value="{{ $customerProfile['name'] ?? '' }}" @if($customerProfile) :disabled="useSavedCustomer" @endif required></label>
                                <label class="pm-field"><span>Email{{ $customerProfile ? ' verificado' : '' }}</span><input type="email" name="email" autocomplete="email" inputmode="email" placeholder="tu@email.com" value="{{ $customerProfile['email'] ?? '' }}" @if($customerProfile) :disabled="useSavedCustomer" readonly @endif required></label>
                                <label class="pm-field"><span>Teléfono</span><input name="phone" autocomplete="tel" inputmode="tel" placeholder="Tu teléfono" value="{{ $customerProfile['phone'] ?? '' }}" @if($customerProfile) :disabled="useSavedCustomer" @endif required></label>
                                <label class="pm-field"><span>Provincia</span><select name="province" @if($customerProfile) :disabled="useSavedCustomer" @endif required><option value="">Seleccionar</option>@foreach(['Buenos Aires','CABA','Chubut','Córdoba','Otra'] as $province)<option @selected(($customerProfile['province'] ?? '') === $province)>{{ $province }}</option>@endforeach</select></label>
                                <label class="pm-field"><span>Localidad</span><input name="locality" autocomplete="address-level2" value="{{ $customerProfile['locality'] ?? '' }}" @if($customerProfile) :disabled="useSavedCustomer" @endif required></label>
                                <label class="pm-field"><span>Código postal</span><input name="postal_code" autocomplete="postal-code" inputmode="numeric" value="{{ $customerProfile['postal_code'] ?? '' }}" @if($customerProfile) :disabled="useSavedCustomer" @endif required></label>
                                <label class="pm-field"><span>Dirección</span><input name="address" autocomplete="street-address" value="{{ $customerProfile['address'] ?? '' }}" @if($customerProfile) :disabled="useSavedCustomer" @endif required></label>
                                <label class="pm-field"><span>Número</span><input name="address_number" inputmode="numeric" value="{{ $customerProfile['address_number'] ?? '' }}" @if($customerProfile) :disabled="useSavedCustomer" @endif required></label>
                                <label class="pm-field"><span>Piso/departamento</span><input name="apartment" value="{{ $customerProfile['apartment'] ?? '' }}" @if($customerProfile) :disabled="useSavedCustomer" @endif></label>
                                <label class="pm-field"><span>Referencia</span><input name="address_reference" value="{{ $customerProfile['address_reference'] ?? '' }}" @if($customerProfile) :disabled="useSavedCustomer" @endif></label>
                                <label class="pm-field pm-field--full"><span>Código de influencer o promoción</span><input name="influencer_code" placeholder="Opcional; atribución pendiente de reglas"></label>
                                @if($customerProfile)<button type="button" class="pm-use-saved-again" @click="useSavedCustomer = true">← Volver a usar mis datos guardados</button>@endif
                            </div>
                            <div class="pm-shipping-pending"><div><span>Modalidad</span><strong>Por confirmar</strong></div><div><span>Costo</span><strong>Por confirmar</strong></div><div><span>Tiempo estimado</span><strong>Por confirmar</strong></div></div>
                        </section>

                        <section x-show="step === 7" x-transition:enter="pm-step-enter" x-transition:enter-start="pm-step-enter-start" x-transition:enter-end="pm-step-enter-end" x-transition:leave="pm-step-leave" x-transition:leave-start="pm-step-leave-start" x-transition:leave-end="pm-step-leave-end" x-cloak>
                            <div class="pm-wizard__question"><span>07 · Calendario</span><h3>Tu calendario de pagos y entregas</h3><p>Revisá las próximas fechas de <b x-text="product?.name"></b> antes de continuar al pago.</p></div>

                            <div class="pm-calendar-overview">
                                <div><span>Presentación</span><strong x-text="variant?.name"></strong></div>
                                <div><span>Frecuencia</span><strong x-text="`Cada ${deliveryFrequency} días`"></strong></div>
                                <div><span>Importe por ciclo</span><strong x-text="money(total)"></strong></div>
                            </div>

                            <div class="pm-calendar-shell">
                                <div
                                    class="pm-calendar"
                                    :class="calendarDragging ? 'is-dragging' : ''"
                                    x-ref="calendar"
                                    aria-label="Próximos pagos y entregas"
                                    tabindex="0"
                                    @scroll.passive="syncCalendarControls()"
                                    @pointerdown="startCalendarDrag($event)"
                                    @pointermove="moveCalendarDrag($event)"
                                    @pointerup="endCalendarDrag($event)"
                                    @pointercancel="endCalendarDrag($event)"
                                    @keydown.left.prevent="scrollCalendar(-1)"
                                    @keydown.right.prevent="scrollCalendar(1)"
                                    @dragstart.prevent
                                >
                                    <template x-for="entry in calendarEntries" :key="entry.cycle">
                                        <article class="pm-calendar-card">
                                            <div class="pm-calendar-card__date"><strong x-text="entry.day"></strong><span x-text="entry.month"></span></div>
                                            <div class="pm-calendar-card__content">
                                                <small x-text="`Ciclo ${entry.cycle}`"></small>
                                                <h4><span aria-hidden="true">$</span> Cobro</h4>
                                                <p><b x-text="entry.billingLabel"></b> · <span x-text="money(total)"></span></p>
                                                <h4><span aria-hidden="true">◇</span> Entrega estimada</h4>
                                                <p x-text="entry.deliveryLabel"></p>
                                            </div>
                                        </article>
                                    </template>
                                </div>
                                <div class="pm-calendar-controls">
                                    <button type="button" class="pm-calendar-hint" @click="scrollCalendar(1)" :disabled="calendarAtEnd">
                                        <span x-text="calendarAtEnd ? 'Ya viste todos los ciclos' : 'Deslizá o tocá para ver los próximos ciclos'"></span>
                                        <span aria-hidden="true">→</span>
                                    </button>
                                    <div class="pm-calendar-arrows" aria-label="Controles del calendario">
                                        <button type="button" @click="scrollCalendar(-1)" :disabled="calendarAtStart" aria-label="Ver ciclo anterior">←</button>
                                        <button type="button" @click="scrollCalendar(1)" :disabled="calendarAtEnd" aria-label="Ver próximo ciclo">→</button>
                                    </div>
                                </div>
                            </div>
                            <div class="pm-data-warning">Calendario orientativo: la ventana de entrega considera entre 3 y 7 días después de cada cobro. Operador, costo y fecha definitiva se confirman antes del pago.</div>

                            <section class="pm-community-card" aria-labelledby="community-title">
                                <div class="pm-community-card__heading"><span aria-hidden="true">◉</span><div><small>Comunidad Promarine</small><h4 id="community-title">Seguí aprendiendo con nosotros</h4><p>Recibí contenido sobre nutrición marina, podcasts e invitaciones a charlas.</p></div></div>
                                <div class="pm-consent-row pm-consent-row--community">
                                    <label class="pm-community-master"><input type="checkbox" name="community_member" value="1" x-model="communityOptIn" @change="if (!communityOptIn) { notifyPodcasts = false; notifyTalks = false }"><span><b>Quiero ser parte de la comunidad Promarine</b><small>Es opcional y podés darte de baja cuando quieras.</small></span></label>
                                    <button type="button" class="pm-consent-info" @click="openConsentInfo('community', $event.currentTarget)" aria-label="Ver qué implica ser parte de la comunidad"><span aria-hidden="true">i</span> ¿Qué acepto?</button>
                                </div>
                                <div class="pm-community-options" :class="communityOptIn ? 'is-enabled' : ''">
                                    <div class="pm-consent-row pm-consent-row--compact"><label><input type="checkbox" name="notify_podcasts" value="1" x-model="notifyPodcasts" :disabled="!communityOptIn"><span>Notificarme nuevos podcasts</span></label><button type="button" class="pm-consent-info" @click="openConsentInfo('podcasts', $event.currentTarget)" aria-label="Ver qué implica recibir avisos de podcasts"><span aria-hidden="true">i</span></button></div>
                                    <div class="pm-consent-row pm-consent-row--compact"><label><input type="checkbox" name="notify_talks" value="1" x-model="notifyTalks" :disabled="!communityOptIn"><span>Invitarme a charlas y encuentros</span></label><button type="button" class="pm-consent-info" @click="openConsentInfo('talks', $event.currentTarget)" aria-label="Ver qué implica recibir invitaciones a charlas"><span aria-hidden="true">i</span></button></div>
                                </div>
                            </section>

                            <div class="pm-payment-note"><span class="pm-payment-note__icon" aria-hidden="true">▣</span><div><strong>Siguiente paso: pago simulado</strong><p>Mercado Pago procesa primero. Solo un pago aprobado genera el pedido Shopify y activa el primer ciclo del calendario.</p></div></div>
                            <div class="pm-consent-list">
                                <div class="pm-consent-row"><label><input type="checkbox" name="consent_recurring" value="1" required><span>Autorizo el cobro recurrente según este calendario.</span></label><button type="button" class="pm-consent-info" @click="openConsentInfo('recurring', $event.currentTarget)" aria-label="Ver detalle de la autorización recurrente"><span aria-hidden="true">i</span> ¿Qué acepto?</button></div>
                                <div class="pm-consent-row"><label><input type="checkbox" name="consent_terms" value="1" required><span>Acepto las condiciones de suscripción.</span></label><button type="button" class="pm-consent-info" @click="openConsentInfo('terms', $event.currentTarget)" aria-label="Ver detalle de las condiciones de suscripción"><span aria-hidden="true">i</span> ¿Qué acepto?</button></div>
                                <div class="pm-consent-row"><label><input type="checkbox" name="consent_order" value="1" required><span>Entiendo que cada pago aprobado genera un nuevo pedido.</span></label><button type="button" class="pm-consent-info" @click="openConsentInfo('order', $event.currentTarget)" aria-label="Ver detalle de la generación de pedidos"><span aria-hidden="true">i</span> ¿Qué acepto?</button></div>
                                <div class="pm-consent-row"><label><input type="checkbox" name="consent_policy" value="1" required><span>Acepto la política de cancelación y cambios.</span></label><button type="button" class="pm-consent-info" @click="openConsentInfo('cancellation', $event.currentTarget)" aria-label="Ver detalle de cancelaciones y cambios"><span aria-hidden="true">i</span> ¿Qué acepto?</button></div>
                            </div>
                        </section>

                        <div class="pm-wizard__actions">
                            <button type="button" class="pm-wizard__back" x-show="step > 1" @click="back()">← Atrás</button>
                            <span x-show="step === 1"></span>
                            <button type="button" class="pm-button" x-show="step < 7" @click="next($root)">Continuar <span aria-hidden="true">→</span></button>
                            <button type="submit" class="pm-button" x-show="step === 7">Continuar al pago <span aria-hidden="true">→</span></button>
                        </div>
                    </div>

                    <aside class="pm-wizard__summary" :class="summaryOpen ? 'is-open' : ''">
                        <button type="button" class="pm-wizard__summary-close" @click="summaryOpen = false" aria-label="Cerrar resumen">×</button>
                        <span class="pm-demo-pill">Configuración actual</span>
                        <img :src="variant?.image || product?.image" alt="" width="480" height="480" decoding="async">
                        <h3 x-text="product?.name || 'Elegí un producto'"></h3>
                        <dl><div><dt>Presentación</dt><dd x-text="variant?.name || '—'"></dd></div><div><dt>Duración</dt><dd x-text="durationDays ? `${durationDays} días` : 'Por confirmar'"></dd></div><div><dt>Entrega</dt><dd x-text="`Cada ${deliveryFrequency} días`"></dd></div><div><dt>Plan</dt><dd x-text="plan?.name || '—'"></dd></div><div><dt>Ahorro</dt><dd x-text="`${formatNumber(plan?.discount || 0)}% preliminar`"></dd></div><div class="pm-summary__total"><dt>Total</dt><dd x-text="money(total)"></dd></div></dl>
                        <p>Envío, dosis y duración real sujetos a configuración.</p>
                    </aside>
                </div>
            </form>

            <div class="pm-consent-modal" x-show="consentModalOpen" x-cloak x-transition.opacity @click.self="closeConsentInfo()" role="presentation">
                <section class="pm-consent-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="consent-modal-title" aria-describedby="consent-modal-description">
                    <button type="button" class="pm-consent-modal__close" @click="closeConsentInfo()" aria-label="Cerrar explicación">×</button>
                    <div class="pm-consent-modal__icon" aria-hidden="true">i</div>
                    <span class="pm-consent-modal__eyebrow" x-text="activeConsent?.eyebrow"></span>
                    <h3 id="consent-modal-title" x-text="activeConsent?.title"></h3>
                    <p id="consent-modal-description" x-text="activeConsent?.description"></p>
                    <ul><template x-for="point in (activeConsent?.points || [])" :key="point"><li><span aria-hidden="true">✓</span><span x-text="point"></span></li></template></ul>
                    <div class="pm-consent-modal__notice"><strong>Antes de marcar el checkbox</strong><p>Leé este detalle y confirmá únicamente si estás de acuerdo. El botón informativo no activa la opción automáticamente.</p></div>
                    <button type="button" class="pm-consent-modal__understood" @click="closeConsentInfo()">Entendido, volver <span aria-hidden="true">→</span></button>
                </section>
            </div>
        </div>
    </div>
</section>
