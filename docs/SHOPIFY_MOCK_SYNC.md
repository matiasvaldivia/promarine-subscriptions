# Shopify Mock Sync — Documentación técnica

> **CRÍTICO**: En entorno `SHOPIFY_MODE=mock`, **ninguna operación toca Shopify real**.  
> Toda sincronización es simulada y persiste en la base de datos local.

---

## 1. Arquitectura del gateway

```
ShopifyGatewayInterface  (contrato)
       ↑
MockShopifyGateway       (implementación actual — is_mock=true)
       ↑ (futuro)
ShopifyProductionGateway (implementación producción — is_mock=false)
```

El swap se realiza en `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php
$this->app->singleton(ShopifyGatewayInterface::class, function () {
    return config('services.shopify.mode') === 'mock'
        ? new MockShopifyGateway()
        : new ShopifyProductionGateway();
});
```

---

## 2. MockShopifyGateway — Métodos implementados

| Método | Descripción |
|---|---|
| `createOrder(array $payload)` | Simula creación. Genera `shopify_order_id` falso. Idempotencia por `payload_hash`. |
| `updateOrder(string $shopifyId, array $payload)` | Simula actualización. Registra en sync items. |
| `cancelOrder(string $shopifyId)` | Simula cancelación. |
| `getInventoryLevel(string $variantId)` | Retorna datos mock del nivel de inventario. |
| `updateInventoryLevel(string $variantId, int $qty)` | Simula ajuste de inventario. |
| `syncProduct(array $payload)` | Simula sincronización de producto. |
| `getProduct(string $shopifyId)` | Retorna producto mock. |
| `createFulfillment(array $payload)` | Simula creación de fulfillment. |
| `updateFulfillment(string $shopifyId, array $data)` | Simula actualización de fulfillment. |

---

## 3. Idempotencia

Cada llamada a `createOrder` calcula:

```php
$hash = hash('sha256', json_encode($payload));
```

Si el `payload_hash` ya existe en `shopify_sync_items`, retorna el `external_id` existente sin duplicar.

---

## 4. Tablas de persistencia

### `shopify_sync_runs`
Cada ejecución manual o automática crea un run:

```sql
id              BIGINT PRIMARY KEY
uuid            VARCHAR(36) UNIQUE
entity_type     ENUM('inventory','orders','products')
direction       ENUM('shopify_to_local','local_to_shopify')
status          ENUM('pending','running','completed','completed_with_errors','failed')
records_read    INT DEFAULT 0
records_created INT DEFAULT 0
records_updated INT DEFAULT 0
records_failed  INT DEFAULT 0
error_message   TEXT
started_at      TIMESTAMP
completed_at    TIMESTAMP
is_mock         TINYINT(1) DEFAULT 1    -- siempre 1 en este entorno
environment     VARCHAR(20) DEFAULT 'local'
```

### `shopify_sync_items`
Cada registro individual procesado:

```sql
id              BIGINT PRIMARY KEY
run_id          BIGINT UNSIGNED FK shopify_sync_runs.id
entity_type     VARCHAR(50)
local_id        VARCHAR(100)
external_id     VARCHAR(100)   -- shopify ID generado
operation       ENUM('create','update','delete','skip')
status          ENUM('pending','processed','failed','skipped')
payload_hash    VARCHAR(64)    -- SHA-256 del payload (idempotencia)
last_error      TEXT
attempts        INT DEFAULT 0
processed_at    TIMESTAMP
```

---

## 5. Flujo de sincronización

```
POST /admin/integrations/shopify/run
  → ShopifySyncController@run
  → ShopifySyncService@runSync($entityType, $direction)
    → ShopifySyncRun::create([status=>'running', is_mock=>true])
    → foreach($items as $item):
        MockShopifyGateway::syncProduct/updateInventoryLevel/createOrder()
          → calcula payload_hash
          → busca duplicado en shopify_sync_items
          → si no existe: inserta + marca processed
          → si existe: marca skipped (idempotencia)
    → ShopifySyncRun::update([status=>'completed'])
```

---

## 6. Variables de entorno

```env
SHOPIFY_STORE_DOMAIN=promarine-dev.myshopify.com   # solo referencia, no se conecta
SHOPIFY_ACCESS_TOKEN=mock_token_no_real
SHOPIFY_MODE=mock                                   # activa MockShopifyGateway
```

---

## 7. Upgrade a producción

Cuando se decida activar Shopify real:

1. Crear `app/Services/ShopifyProductionGateway.php` que implemente `ShopifyGatewayInterface`.
2. Cambiar `SHOPIFY_MODE=production` en `.env`.
3. Los servicios de dominio y controladores no necesitan cambios.
4. El badge `MOCK · LOCAL` desaparecerá automáticamente.

---

## 8. Datos de prueba (seeder)

```
3 sync runs:
  Run 1: inventory / shopify_to_local / completed     (5 ítems)
  Run 2: orders    / local_to_shopify / completed_with_errors (6 ítems, 1 fallido)
  Run 3: products  / local_to_shopify / failed        (3 ítems)
```
