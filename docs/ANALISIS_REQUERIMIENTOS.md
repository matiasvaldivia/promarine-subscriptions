# Análisis de Requerimientos — Promarine Suscripciones
**Versión 1.0 · 2026-08-20 · Generado desde el código fuente**

---

## 1. Visión General del Sistema

**Promarine Suscripciones** es una tienda de suscripciones de productos de nutrición marina que opera en modo **simulado / demo**. Su propósito:

1. Presentar el catálogo de productos a potenciales clientes.
2. Guiar al usuario a través de un wizard de 7 pasos para seleccionar producto, plan y presentación.
3. Procesar el checkout con integración a **MercadoPago** (con fallback a flujo mock).
4. Proveer un **portal de cliente** donde el suscriptor puede ver el calendario de entregas.
5. Ofrecer un **panel de administración** completo para gestión operativa.

> **Nota arquitectural**: El sistema diferencia explícitamente entre entidades "mock" (simuladas para demo) y entidades reales. El paso a producción real implica reemplazar los gateways mock por los reales sin tocar el código de negocio.

---

## 2. Actores del Sistema

| Actor | Descripción | Acceso |
|---|---|---|
| **Visitante** | Usuario anónimo que llega a la landing | `/` |
| **Prospecto** | Completa el wizard y el checkout | `/checkout/*` |
| **Cliente** | Tiene una suscripción activa | `/mi-plan/*` |
| **Admin** | Gestiona la operación interna | `/admin/*` |
| **Super Admin** | Admin con permisos de gestión de usuarios | `/admin/users` |
| **Sistema (MP)** | Webhook de MercadoPago | `POST /webhooks/mercadopago` |

---

## 3. Módulos Funcionales

### 3.1 Landing & Wizard de Compra

**Ruta**: `GET /`

**Descripción**: Página principal que presenta los productos y lanza el wizard de 7 pasos.

**Flujo del wizard** (Alpine.js + Blade):

```
Paso 1: Selección de producto
        └─ 4 productos: Marine Epic, Marine Fusion, Echa Marine, Marine Pulse

Paso 2: Selección de presentación
        └─ Botella | Monodosis

Paso 3: Selección de plan
        └─ Suscripción flexible (1 ciclo, 10% dto)
        └─ Plan 3 meses (12% dto)
        └─ Plan 6 meses (15% dto)

Paso 4: Personalización
        └─ Cantidad de personas
        └─ Dosis por día
        └─ Frecuencia de entrega (15/30/45/60 días)

Paso 5: Datos personales
        └─ Nombre, email, teléfono
        └─ Auto-relleno si cliente verificado via portal

Paso 6: Dirección de entrega
        └─ Provincia, localidad, CP, dirección, apartamento, referencia
        └─ Opción "usar mis datos guardados" para cliente portal

Paso 7: Confirmación + consentimientos
        └─ consent_recurring: cobro recurrente
        └─ consent_terms: términos y condiciones
        └─ consent_order: pedido se genera post-pago
        └─ consent_policy: política de cancelación
        └─ Preferencias de comunidad (podcasts, charlas)
        └─ Código de influencer (opcional)
```

**Features adicionales de la landing**:
- Tema oscuro/claro con `pm-theme-image` (imágenes adaptativas)
- "Configuración actual" flotante que se actualiza en tiempo real
- Detección de cliente existente via sesión del portal → pre-fill del wizard
- Modo "recompra" (`?recomprar=1`) para clientes del portal

---

### 3.2 Checkout & Pago

**Rutas**:
- `POST /checkout/simulate` — Crea la suscripción y redirige a MP o flujo mock
- `GET /checkout/simulate/{uuid}/payment` — Pantalla de pago mock
- `POST /checkout/simulate/{uuid}/process` — Procesa el pago mock

**Flujo de pago**:

```
1. CheckoutController::store()
   ├─ Valida 20+ campos del formulario
   ├─ Reutiliza cliente portal o crea MockCustomer nuevo
   ├─ Llama a MercadoPagoGatewayInterface::createSubscription()
   │   ├─ Modo REAL → devuelve init_point → redirect a MP sandbox
   │   └─ Modo MOCK → flujo interno
   ├─ Crea MockSubscription con estado 'pending'
   └─ Redirige a /checkout/.../payment

2. CheckoutController::process()
   ├─ MockSubscriptionFlow::processPayment()
   ├─ Genera MockPayment + MockOrder
   ├─ Envía email de confirmación (MockPurchaseConfirmed)
   └─ Vista de confirmación con datos de pedido
```

**Validaciones del checkout**:
- Throttle: 10 requests/minuto por IP
- 4 consentimientos obligatorios (`accepted`)
- Frecuencia: solo 15, 30, 45 ó 60 días
- Personas: 1–10 | Dosis: 0.25–10 por día

---

### 3.3 Webhook MercadoPago

**Ruta**: `POST /webhooks/mercadopago` (sin CSRF)

**Descripción**: Recibe notificaciones de MP sobre cambios de estado en suscripciones. Autenticación por firma HMAC.

---

### 3.4 Portal del Cliente

**Rutas**: `/mi-plan/*`

**Autenticación**: Sin contraseña. Acceso por **código OTP de 6 dígitos** enviado al email.

**Flujo de acceso**:
```
1. GET  /mi-plan/          → Ingreso de email
2. POST /mi-plan/codigo    → Valida email, genera código hasheado, envía mail
                             (throttle: 3/minuto)
3. GET  /mi-plan/verificar → Formulario de código
4. POST /mi-plan/verificar → Valida (max 5 intentos, expira 10 min)
5. GET  /mi-plan/calendario → Dashboard del cliente
```

**Dashboard del cliente**:
- Datos del plan activo (producto + presentación + monto)
- **Calendario de entregas** — próximos 6 ciclos con:
  - Fecha de cobro
  - Ventana de entrega estimada (cobro +3 a +7 días)
  - Estado del ciclo (pagado / próximo / futuro)
- Preferencias de comunidad (podcasts, charlas)
- Botón "Recomprar" → landing con datos pre-llenados

---

### 3.5 Membership

**Rutas**: `GET/POST /Plan-de-subscription`

Formulario de captación de interés para membresía. Genera `MembershipSubscription` y envía email `MembershipRequested`.

---

### 3.6 Panel de Administración

**Acceso**: `/login` (auth + role middleware)

**Roles**: `admin` y `super_admin`

| Módulo | Ruta | Descripción |
|---|---|---|
| Dashboard | `/admin/` | Métricas generales |
| Clientes | `/admin/customers` | CRUD de MockCustomer |
| Usuarios | `/admin/users` | Solo super_admin — CRUD + toggle status |
| Productos | `/admin/products` | Listado y edición de Product + Variants |
| Matriz comercial | `/admin/cart-matrix` | 24 combinaciones producto×plan×presentación |
| Inventario | `/admin/inventory` | Niveles, sync y ajuste manual |
| Pedidos | `/admin/orders` | Listado, detalle y transición de estados |
| Fulfillments | `/admin/fulfillments` | Gestión de despachos |
| Suscripciones | `/admin/subscriptions` | Ver, pausar, reanudar, cancelar |
| Pagos | `/admin/payments` | Listado y detalle de MockPayment |
| Shopify Sync | `/admin/integrations/shopify` | Runs de sincronización con Shopify |
| Eventos | `/admin/integration-events` | Log de eventos de integración |
| IGS | `/admin/igs` | Panel de comisiones de influencers |
| Auditoría | `/admin/audit-logs` | Log de acciones admin |

---

## 4. Modelos de Datos Clave

```
Product (4 activos)
├─ id, name, slug, short_description, image_path
├─ reference_price, subscription_price, saving_percent
├─ enabled, featured, is_mock
└─ hasMany ProductVariant

ProductVariant (2 por producto: botella | monodosis)
├─ id, product_id, name, type, sku, presentation
├─ price, units_per_package, unit_measure
├─ recommended_daily_dose, estimated_days
├─ simulated_stock, enabled
└─ hasMany SubscriptionPlan

SubscriptionPlan (3 por variante = 24 planes totales)
├─ id, product_variant_id, name
├─ amount, currency (ARS)
├─ frequency (30), minimum_cycles (1|3|6)
├─ discount_value (10%|12%|15%)
├─ can_pause, can_cancel, enabled
└─ hasMany MockSubscription

MockCustomer
├─ uuid, name, email, phone
├─ province, locality, postal_code
├─ address, address_number, apartment, address_reference
└─ hasMany MockSubscription

MockSubscription
├─ uuid, customer_id, subscription_plan_id
├─ provider, provider_subscription_id
├─ status (ver sección 5)
├─ amount, frequency, next_billing_at
├─ influencer_code
├─ metadata_json {source, product, people, doses, community_preferences}
└─ hasMany MockPayment, MockOrder

MockOrder
├─ internal_status (ver sección 6)
├─ transmitted_at, confirmed_at, dispatched_at, delivered_at
└─ hasMany OrderStatusHistory
```

---

## 5. Estados de Suscripción

```
                    ┌─────────┐
                    │ pending │
                    └────┬────┘
           ┌─────────────┴──────────┐
           ▼                        ▼
  payment_approved           payment_rejected
     │    ▲    │                    │
  pause  resume cancel           cancel
     │    │    │                    │
   paused─┘  cancelled ◄───────────┘
```

**Acciones admin disponibles**:
- `pause`: desde `payment_approved`, `authorized`, `active`
- `resume`: desde `paused`
- `cancel`: desde cualquier estado activo

---

## 6. State Machine de Pedidos

```
draft → pending_payment → payment_approved → ready_to_transmit
      → transmitting → transmitted → confirmed_by_shopify
      → preparing → ready_to_ship → shipped ──► delivered ✓
                                          └───► returned → cancelled ✓
```

Cada transición registra actor, timestamp y razón en `OrderStatusHistory`.

---

## 7. Seguridad

| Mecanismo | Implementación |
|---|---|
| CSRF | Laravel estándar (excepto webhook MP) |
| Rate limiting | Throttle checkout (10/1min), portal (3/1min), login (5/1min) |
| Auth admin | `auth` + `role` middleware |
| Auth portal | OTP 6 dígitos, bcrypt, expira 10 min, 5 intentos máx |
| Webhook MP | Validación por firma HMAC |
| Headers HTTP | `AddSecurityHeaders` middleware (CSP, HSTS, X-Frame, etc.) |
| Auditoría | `AuditService` en `audit_logs` |

---

## 8. Stack Tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.3, Laravel 12, Eloquent ORM |
| Frontend | Blade + Alpine.js + Vite 7 |
| CSS | Design system custom (tokens `pm-*`) |
| Base de datos | MySQL 8 (Hostinger `srv1818.hstgr.io`) |
| Email | SMTP (configurable vía `.env`) |
| Pagos | MercadoPago API |
| Analytics | Umami (self-hosted) |
| Infraestructura | Docker (PHP-FPM 8.3 + Nginx), VPS Contabo, NPM |
| CI/CD | GitHub Actions → SSH → node:22-alpine → artisan |

---

## 9. Gaps y Backlog Identificado

| # | Gap | Impacto | Evidencia en código |
|---|---|---|---|
| G1 | **Gateways en mock** — MP, Shopify e IGS usan implementaciones simuladas | No procesa pagos reales | `MockMercadoPagoGateway`, `MockShopifyGateway` |
| G2 | **`environment = 'local'`** hardcodeado en MockCustomer creado desde checkout | Datos de prod mal etiquetados | `CheckoutController.php` L.105 |
| G3 | **Inventario desconectado** — stock no se decrementa automáticamente al comprar | Sin gestión real de stock | `InventoryService` manual |
| G4 | **Sin autogestión del cliente** — solo admin puede pausar/cancelar suscripción | Cliente depende del admin para cambios | `SubscriptionService` solo en `/admin` |
| G5 | **Webhook MP incompleto** — no actualiza estados de pedido automáticamente | Ciclo de vida incompleto | `WebhookMercadoPagoController` |
| G6 | **Shopify sync manual** — admin debe disparar el run | Sin sincronización automática | `ShopifySyncController::run()` |
| G7 | **`db:seed` fuera del deploy** — se debe correr manualmente en primer deploy | Riesgo al resetear el entorno | Deployflow sin seed |
| G8 | **Sin paginación en landing** — todos los productos activos se cargan en memoria | Escala mal con +20 productos | `Product::where('enabled')->get()` |
