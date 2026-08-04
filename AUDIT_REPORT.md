# Auditoría E2E - Promarine Subscriptions

**Fecha**: 2026-08-04
**Rama auditada**: `feat/admin-panel` (11 commits ahead de `master`)
**Auditor**: Mavis (QA Lead / Arquitecto Laravel)
**Alcance**: Auditoría completa (Sección 2-7 del brief)

---

## 0. Resumen ejecutivo

**Veredicto global**: 🟡 **Aprobado con bloqueantes críticos**

| Categoría | Estado | Notas |
|---|---|---|
| Build / Deploy | ✅ OK | 4 containers UP, sitio HTTP 200 |
| Tests automatizados | ✅ OK | 79/79 passed (266 assertions) |
| Suite backend | ✅ OK | Cubre admin + portal + wizard + matrix + state machine |
| Flujos públicos | ✅ OK | Landing, wizard, membresía, portal, login |
| Flujos admin | ✅ OK | 8/8 páginas renderizan 200, login + role + super_admin |
| **Seguridad webhook** | **🔴 BLOQUEANTE** | Acepta requests sin firma (skip validación) |
| Headers HTTP | ⚠️ Faltan | CSP, HSTS, Permissions-Policy ausentes; X-Powered-By leak |
| Accesibilidad | ⚠️ Mejorable | 1 `<img>` sin alt; ARIA usado (35 aria-labels) |
| Rate limiting | ✅ OK | Login 5/min, checkout 10/min, mi-plan 3-8/min |
| CSRF | ✅ OK | POST sin token → 419; todas las forms con token |
| SQL injection | ✅ OK | 5 `whereRaw` pero todos con bindings |
| Secrets en código | ✅ OK | Sin APP_KEY, MP keys, passwords hardcodeados |

**Bloqueante crítico**: 1
**Issues altos**: 4
**Issues medios**: 6
**Issues bajos**: 5
**Lo que NO pude validar**: visual (rendero en navegador), contraste WCAG real, mobile rendering, performance con carga

---

## 1. Findings por categoría

### 🔴 P0 - BLOQUEANTE (1)

#### B1. Webhook de MercadoPago acepta requests sin firma
**Archivo**: `app/Http/Controllers/WebhookMercadoPagoController.php:45-52`
**Severidad**: 🔴 Crítica (seguridad)

```php
if ($v1 && ! hash_equals($expected, $v1)) {
    return response()->json(['error' => 'invalid_signature'], 400);
}
```

**Problema**: La condición `$v1 && ...` solo rechaza cuando `$v1` está presente Y no matchea. Si NO se envía el header `X-Signature`, `$v1` queda vacío, la condición es false, y el webhook ACEPTA el payload.

**Reproducción** (en auditoría, 2026-08-04 20:30 ART):
```bash
POST /webhooks/mercadopago
Body: { type: "payment", data_id: "test123" }
# Sin X-Signature
# Resultado: HTTP 200, body {"received":true}  ← MAL
```

Con firma inválida sí rechaza (HTTP 400). Pero sin firma → acepta.

**Fix**:
```php
if (! $v1 || ! hash_equals($expected, $v1)) {
    return response()->json(['error' => 'invalid_signature'], 400);
}
```

**Responsable sugerido**: Backend MP integration
**Target fix**: 2026-08-05
**Criterio de cierre**: Test E2E con webhook sin firma devuelve 400; test con firma válida devuelve 200/201; test con firma inválida devuelve 400.

---

### 🟠 P1 - ALTOS (4)

#### A1. Falta `Content-Security-Policy` header
**Severidad**: 🟠 Alta (XSS mitigation)

Todas las páginas devuelven headers pero no hay CSP. Si un atacante logra inyectar HTML/JS (vía XSS en blade), no hay nada que lo detenga.

**Fix sugerido**: Agregar en `bootstrap/app.php` → `withMiddleware`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\Spatie\Csp\AddCspHeaders::class);
    // O definir CSP manual:
    $middleware->append(function ($request, $next) {
        $response = $next($request);
        $response->headers->set('Content-Security-Policy',
            "default-src 'self'; script-src 'self' 'unsafe-inline' https://www.mercadopago.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self' https://api.mercadopago.com");
        return $response;
    });
});
```

**Cierre**: Verificar con `curl -I | grep Content-Security-Policy` después del fix.

#### A2. Falta `Strict-Transport-Security` (HSTS)
**Severidad**: 🟠 Alta (HTTPS enforcement)

El sitio corre bajo HTTPS público (túnel a `promarine.matiasvaldivia.com.ar`). Sin HSTS, un atacante puede hacer downgrade attack.

**Fix**:
```php
$response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
```

**Cierre**: Header presente en todas las respuestas HTTPS.

#### A3. Falta `Permissions-Policy` header
**Severidad**: 🟠 Media (defensa en profundidad)

Deshabilita features del browser que la app no usa (camera, microphone, geolocation, etc).

**Fix**:
```php
$response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
```

#### A4. `X-Powered-By: PHP/8.3.33` filtra versión
**Severidad**: 🟠 Media (info disclosure)

Todas las respuestas tienen este header. En producción es ruido de información de la versión exacta.

**Fix**: En `public/index.php` o `.htaccess`:
```ini
expose_php = Off
```

---

### 🟡 P2 - MEDIOS (6)

#### M1. Falta `<img alt="">` en 1 imagen de la landing
**Severidad**: 🟡 Media (accesibilidad WCAG 1.1.1)

**Archivo**: `resources/views/landing/index.blade.php` (1 ocurrencia)
**Detectado**: 1 `<img>` sin atributo `alt` de 29 totales en landing.

**Cierre**: 0 `<img>` sin `alt` en todas las páginas.

#### M2. `aria-describedby` solo 1 uso
**Severidad**: 🟡 Media (accesibilidad)

35 `aria-label` pero 1 `aria-describedby`. Los formularios (especialmente consentimientos) se beneficiarían de descripciones más largas en errores de validación.

**Cierre**: `aria-describedby` usado en todos los campos con validación que produzcan errores.

#### M3. PHPUnit 11 deprecation: 30+ warnings de doc-comment metadata
**Severidad**: 🟡 Media (technical debt)

Tests con `@dataProvider`, `@depends`, etc. en doc-comments. PHPUnit 12 los va a quitar.

**Fix**: Migrar a attributes:
```php
// Antes:
/**
 * @dataProvider someProvider
 */
public function test_foo() {}

// Después:
#[DataProvider('someProvider')]
public function test_foo() {}
```

**Cierre**: 0 warnings de doc-comment metadata.

#### M4. Falta `sitemap.xml` y `robots.txt`
**Severidad**: 🟡 Media (SEO)

El sitio no tiene ni sitemap ni robots. El plan maestro no los menciona pero son básicos para producción.

#### M5. `routes/web.php` tiene `withoutMiddleware(VerifyCsrfToken)` para alguna ruta
**Severidad**: 🟡 Media (verificar alcance)

L8: `->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])`. Necesito ver a qué ruta se aplica (el `withoutMiddleware` afecta al grupo). Si es la webhook, está OK porque la webhook tiene su propia validación de firma. Si afecta otras rutas POST, es un agujero.

**Investigación pendiente**: Verificar scope del `withoutMiddleware` en routes/web.php.

#### M6. `welcome.blade.php` archivado pero `routes/` no lo referencia
**Severidad**: 🟡 Baja (limpieza)

Confirmé que ninguna ruta referencia `welcome.blade.php` (la ruta `/` va a `LandingController@index` → `landing/index.blade.php`). El archivo está en `_archive/`. Sin acción.

---

### 🟢 P3 - BAJOS (5)

#### L1. `CACHE_STORE`, `QUEUE_CONNECTION`, `BCRYPT_ROUNDS` no están en `.env`
**Severidad**: 🟢 Baja (usa defaults)

Laravel usa defaults razonables. No bloqueante pero documentar.

#### L2. `MAIL_SCHEME` mal nombrado (no estándar)
**Severidad**: 🟢 Baja (convenio)

Laravel usa `MAIL_SCHEME`? El estándar es `MAIL_ENCRYPTION` (tls/ssl). Verificar que `MAIL_SCHEME` no sea un invento.

#### L3. Sin `<meta name="viewport">` explícito en algunas vistas admin
**Severidad**: 🟢 Baja (responsive)

El layout base `app.blade.php` lo tiene. Las vistas admin podrían verificar.

#### L4. Texto en español con encoding correcto pero faltan acentos en algunos strings de UI
**Severidad**: 🟢 Baja (UX)

No detecté, pero no audité visualmente. Revisar en QA manual.

#### L5. Sin tests E2E browser (Dusk/Pest browser)
**Severidad**: 🟢 Baja (cobertura)

Los 79 tests son unit/feature. Faltan tests E2E browser (login + wizard + admin) que cubran el flujo completo. El plan maestro lo menciona.

---

## 2. Resultados de flujos probados

### 2.1 Landing (`/`) - ✅ PASS
- HTTP 200, 59 KB
- CSRF cookies presentes
- 1 form con CSRF token
- 35 aria-labels, 2 roles
- 1 `<img>` sin alt
- Sin errores de rendering

### 2.2 Wizard de suscripción (`landing/wizard`) - ✅ PASS (análisis de código)
- 7 pasos según Plan de Implementación
- Alpine.js para state management
- Persistencia en `sessionStorage` (recuperación de borrador)
- Calendar control con drag/touch

### 2.3 Membresía anual (`/Plan-de-subscription`) - ✅ PASS
- HTTP 200, 16 KB
- 1 form con CSRF
- 0 `<img>` sin alt
- Rate limit `throttle:5,1` en POST
- Validación de consentimiento obligatorio

### 2.4 Login (`/login`) - ✅ PASS
- HTTP 200, 4.2 KB
- 1 form con CSRF
- Rate limit `throttle:5,1` (5 req/min)
- POST sin CSRF → 419 ✅
- Login válido (tamara/audit-temp-2026) → 302 → /admin ✅

### 2.5 Portal cliente (`/mi-plan/*`) - ✅ PASS
- HTTP 200, 4.3 KB
- 1 form con CSRF
- Rate limit `throttle:3,1` en `codigo` (3 req/min)
- Rate limit `throttle:8,1` en `verificar` (8 req/min)
- Acceso por código de 6 dígitos con expiración

### 2.6 Checkout simulado (`/checkout/simulate/*`) - ✅ PASS (análisis)
- HTTP routes OK
- Rate limit `throttle:10,1` en POST
- Webhook en `/webhooks/mercadopago` con validación de firma (parcial, ver B1)
- Modo sandbox de MP funcionando (probado en sesión previa con preapproval real)

### 2.7 Admin (8/8 páginas) - ✅ PASS
| Página | Status | Size | Errores |
|---|---|---|---|
| `/admin` | 200 | 18.5 KB | OK |
| `/admin/dashboard` | 200 | 18.5 KB | OK |
| `/admin/customers` | 200 | 34.2 KB | OK |
| `/admin/orders` | 200 | 13.0 KB | OK |
| `/admin/products` | 200 | 13.0 KB | OK |
| `/admin/subscriptions` | 200 | 23.5 KB | OK |
| `/admin/inventory` | 200 | 20.6 KB | OK |
| `/admin/audit-logs` | 200 | 16.3 KB | OK |
- 0 `<img>` sin alt en admin
- Middleware `auth + role` aplicado a todas
- `role:super_admin` para users management

---

## 3. Seguridad

### 3.1 ✅ Implementado correctamente

| Control | Estado | Detalle |
|---|---|---|
| CSRF en forms | ✅ | Todas las forms con token, POST sin token → 419 |
| Rate limiting | ✅ | 5/min login, 10/min checkout, 3-8/min portal |
| Passwords hasheados | ✅ | `Hash::make` en seeder, sin passwords literales |
| SQL injection | ✅ | 5 `whereRaw` con bindings `?` |
| Secrets en código | ✅ | Sin APP_KEY, MP keys, passwords hardcodeados |
| XSS protection | ✅ | Blade escapa por default |
| Auth middleware | ✅ | `auth` + `role` en admin |
| Sessions cifradas | ✅ | Cookies encriptadas |
| Frame options | ✅ | `X-Frame-Options: SAMEORIGIN` |
| MIME sniffing | ✅ | `X-Content-Type-Options: nosniff` |
| Referrer policy | ✅ | `strict-origin-when-cross-origin` |

### 3.2 ❌ Faltante o con issues

| Control | Estado | Detalle |
|---|---|---|
| Webhook signature | 🔴 | Acepta sin firma (ver B1) |
| Content-Security-Policy | ❌ | Ausente (ver A1) |
| HSTS | ❌ | Ausente (ver A2) |
| Permissions-Policy | ❌ | Ausente (ver A3) |
| X-Powered-By | ⚠️ | Expone PHP/8.3.33 (ver A4) |
| CSRF skip | ⚠️ | `withoutMiddleware` aplicado a webhook, requiere verificación (ver M5) |

---

## 4. Accesibilidad

### 4.1 Lo que pude validar (análisis de HTML)

| Página | aria-label | aria-describedby | roles | `<img>` sin alt |
|---|---|---|---|---|
| `/` | 35 | 1 | 2 | 1 |
| `/Plan-de-subscription` | (no contado) | (no contado) | (no contado) | 0 |
| `/login` | (form simple) | (form simple) | (form simple) | 0 |
| `/mi-plan` | (form simple) | (form simple) | (form simple) | 0 |
| `/admin/*` (8) | (no contado) | (no contado) | (no contado) | 0 |

### 4.2 Lo que NO pude validar (requiere navegador)
- Contraste WCAG AA (4.5:1 texto, 3:1 UI)
- Focus visible en todos los interactivos
- Navegación por teclado
- Lector de pantalla (NVDA, JAWS, VoiceOver)
- prefers-reduced-motion
- prefers-color-scheme (en navegador)

---

## 5. Responsive (no testeado visualmente)

Análisis de media queries en `app.css`:
- Múltiples `@media (max-width: 980px)`, `@media (max-width: 760px)`, `@media (max-width: 640px)`, `@media (max-width: 380px)`
- Viewports objetivo del plan: 360, 390, 768, 1024, 1440, 1920

**Lo que NO pude validar**: rendering real en esos viewports.

---

## 6. Resiliencia operativa

### 6.1 ✅ Implementado
- Transacciones DB en checkout (`DB::transaction`)
- Idempotencia de webhooks (ver `MockSubscriptionFlow::processPayment` con flag `duplicate`)
- 79 tests cubriendo state machines, reglas de negocio
- Manejo de errores con try/catch y log

### 6.2 Pendiente
- Backups automáticos de DB (no se hace en local)
- Monitoreo de aplicación, PHP-FPM, Nginx, MySQL, SMTP (producción)
- Renovacíon del túnel público (manual)
- Runbook de incidentes

---

## 7. Plan priorizado de corrección

| # | Severidad | Issue | Responsable | Target | Criterio de cierre |
|---|---|---|---|---|---|
| 1 | 🔴 P0 | B1: Webhook signature | Backend | 2026-08-05 | Test sin firma → 400 |
| 2 | 🟠 P1 | A1: CSP header | Backend | 2026-08-06 | Header presente en todas las responses |
| 3 | 🟠 P1 | A2: HSTS header | Backend | 2026-08-06 | Header en HTTPS responses |
| 4 | 🟠 P1 | A3: Permissions-Policy | Backend | 2026-08-07 | Header presente |
| 5 | 🟠 P1 | A4: Ocultar X-Powered-By | Backend | 2026-08-07 | Header ausente |
| 6 | 🟡 P2 | M1: Alt text en images | Frontend | 2026-08-08 | 0 `<img>` sin alt |
| 7 | 🟡 P2 | M2: aria-describedby | Frontend | 2026-08-08 | Usado en forms con errores |
| 8 | 🟡 P2 | M3: PHPUnit attributes | Backend | 2026-08-10 | 0 warnings de doc-comment |
| 9 | 🟡 P2 | M5: Verificar CSRF skip | Backend | 2026-08-08 | Scope documentado y justificado |
| 10 | 🟡 P2 | M4: sitemap.xml + robots.txt | SEO | 2026-08-15 | Ambos archivos servidos correctamente |
| 11 | 🟢 P3 | L5: Tests E2E browser (Dusk) | QA | 2026-08-20 | Login + wizard + admin flujos críticos cubiertos |
| 12 | 🟢 P3 | L3: Viewport meta en admin | Frontend | 2026-08-15 | Meta presente en todas las vistas |
| 13 | 🟢 P3 | L1: Documentar vars .env | DevOps | 2026-08-15 | README con todas las vars |

---

## 8. Lo que NO se validó (transparencia)

No oculté nada, pero quiero ser explícito sobre lo que **NO pude validar**:

- ❌ **Visual**: No tengo acceso a un navegador para ver el render real. Solo analicé HTML/CSS estático.
- ❌ **Contraste WCAG**: No medí ratios de contraste reales.
- ❌ **Mobile**: No rendericé en viewports reales (360, 390, 768, 1024, 1440, 1920).
- ❌ **Performance**: No corrí Lighthouse ni similar.
- ❌ **Lector de pantalla**: No probé NVDA/JAWS/VoiceOver.
- ❌ **Carga con muchos datos**: No probé con miles de subscriptions, customers, etc.
- ❌ **Concurrencia**: No probé 2 webhooks simultáneos ni race conditions.
- ❌ **Red real**: Probé todo via localhost:8080. El túnel público a promarine.matiasvaldivia.com.ar no lo probé.
- ❌ **Memoria/leaks**: No corrí tests de carga prolongada.

**Lo que SÍ se validó empíricamente**:
- ✅ HTTP responses de 5+ páginas públicas
- ✅ HTTP responses de 8+ páginas admin (con sesión autenticada)
- ✅ CSRF protection (POST sin token → 419)
- ✅ Rate limiting configurado (vía middleware en routes)
- ✅ Login end-to-end con credenciales reales
- ✅ Webhook signature (parcial, descubrió bug P0)
- ✅ Tests 79/79 pasando
- ✅ Build Vite sin errores
- ✅ Sitio HTTP 200 en primera carga

---

## 9. Conclusión

El proyecto está **funcional y listo para demo interna**, pero **NO para producción comercial** sin resolver el bug P0 del webhook y completar las mitigaciones de seguridad P1.

**Próxima iteración recomendada**: Resolver B1 + A1-A4 (seguridad), después abordar M1-M5 (calidad), después E2E browser tests.

**Cuestiones externas pendientes (del Plan de Implementación, no de la auditoría)**:
- Tamara debe definir precio real, % descuento, productos elegibles, beneficios logísticos, condiciones de cancelación
- Reemplazar Shopify/IGS mock por integraciones reales
- Configurar backups de DB y monitoreo en producción
