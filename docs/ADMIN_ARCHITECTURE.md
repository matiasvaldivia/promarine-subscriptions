# Arquitectura del Panel Administrativo Promarine

> **Versión**: 1.0 — Agosto 2026  
> **Estado**: MOCK · LOCAL — No conecta a Shopify real  
> **Entorno**: `SHOPIFY_MODE=mock` · `APP_ENV=local`

---

## 1. Visión general

El panel administrativo es una **capa interna separada** del flujo público de suscripciones. No modifica ni consulta Shopify real en este entorno. Todo opera a través de un **adaptador mock persistente** que registra los resultados en la base de datos local.

```
Browser → [Admin Routes /admin/*]
              ↓ auth middleware (EnsureAdminRole)
          [Admin Controllers] (14)
              ↓
          [Domain Services] (7)
              ↓
          [Models / DB] ← → [MockShopifyGateway (is_mock=true)]
                                   ↓
                            ShopifySyncRun + ShopifySyncItem
                            (persistido, nunca toca Shopify real)
```

---

## 2. Estructura de directorios

```
app/
├── Http/
│   ├── Controllers/Admin/          ← 14 controladores
│   │   ├── DashboardController.php
│   │   ├── CustomerAdminController.php
│   │   ├── OrderAdminController.php
│   │   ├── SubscriptionAdminController.php
│   │   ├── InventoryController.php
│   │   ├── CartMatrixController.php
│   │   ├── ShopifySyncController.php
│   │   ├── PaymentAdminController.php
│   │   ├── FulfillmentAdminController.php
│   │   ├── IgsController.php
│   │   ├── IntegrationEventController.php
│   │   ├── AuditLogController.php
│   │   ├── ProductAdminController.php
│   │   └── UserAdminController.php
│   ├── Middleware/
│   │   └── EnsureAdminRole.php     ← verifica rol y permisos
│   └── Requests/Admin/             ← 4 Form Requests con validación
├── Services/
│   ├── OrderStateMachine.php       ← 14 estados, transiciones explícitas
│   ├── OrderService.php
│   ├── SubscriptionService.php
│   ├── InventoryService.php
│   ├── CartMatrixService.php
│   ├── ShopifySyncService.php
│   ├── AuditService.php
│   ├── MockShopifyGateway.php      ← adaptador mock persistente
│   └── ShopifyGatewayInterface.php ← contrato para producción futura
resources/views/admin/              ← 17 vistas Blade
routes/web.php                      ← 50 rutas admin registradas
```

---

## 3. Sistema de roles y permisos

| Rol | Accesos |
|---|---|
| `super_admin` | Todo, incluyendo gestión de usuarios |
| `decision_owner` | Clientes, pedidos, suscripciones, inventario, matriz, sync, IGS, auditoría |
| `operations` | Pedidos, fulfillments, inventario |
| `customer_service` | Clientes, suscripciones, pagos |
| `analytics` | Solo lectura en todos los módulos |
| `developer` | Todo + configuración técnica |

**Tamara** tiene rol `decision_owner`. **No puede** gestionar usuarios (solo `super_admin`).

---

## 4. Controladores y responsabilidades

### DashboardController
- KPIs en tiempo real desde DB: clientes, suscripciones, pedidos, stock
- Alertas de acción requerida (orders con estado crítico)
- Últimos eventos de integración

### CustomerAdminController
- CRUD completo con paginación y filtros
- Integra: `shopify_customer_id`, `mercadopago_customer_id`, `igs_customer_id`
- Vistas de detalle con historial de suscripciones

### OrderAdminController
- Lista con filtro por estado
- Detalle con timeline de transiciones
- `transition()`: aplica cambios de estado via `OrderStateMachine`

### SubscriptionAdminController
- Lista con filtro por estado
- Acciones: `pause()`, `resume()`, `cancel()` — delegadas a `SubscriptionService`
- Muestra historial de pagos

### InventoryController
- Resumen: in_stock / low_stock / out_of_stock
- Ajuste inline por variante y ubicación
- Sync mock a Shopify

### CartMatrixController
- Tabla de 24 combinaciones con edición inline (Alpine.js)
- `update()`: recalcula `subscription_price` via `CartMatrixService`

### ShopifySyncController
- Formulario para ejecutar sync por entidad y dirección
- Historial de runs con estado, contadores y duración
- Detalle de ítems procesados / fallidos

---

## 5. Flujo de autenticación admin

```
GET /login → AuthController@show
POST /login → AuthController@login
  → verifica User::where('email') + Hash::check
  → session()->regenerate()
  → redirect('/admin')

Middleware 'auth' protege todo /admin/*
EnsureAdminRole verifica $user->hasPermission($permiso)
```

---

## 6. Convenciones

- **Blade**: solo presentación. Sin lógica de negocio.
- **Controladores**: delegan a Services. Sin consultas SQL directas.
- **Servicios**: contienen toda la lógica de negocio.
- **Gateway**: `MockShopifyGateway` implementa `ShopifyGatewayInterface`.  
  En producción, se swapea por `ShopifyProductionGateway` sin modificar los servicios.
- **Auditoría**: `AuditService::log()` se llama en toda acción administrativa.

---

## 7. Variables de entorno relevantes

```env
SHOPIFY_MODE=mock          # Nunca tocar Shopify real
APP_ENV=local
MERCADOPAGO_SANDBOX=true   # Sandbox activo
```

---

## 8. Acceso al panel

- URL: `http://localhost:8080/admin`  
- Usuario: `tamara@promarine.com.ar`  
- Contraseña: `promarine2024`  
- Badge visible: `MOCK · LOCAL — NO MODIFICA SHOPIFY REAL`
