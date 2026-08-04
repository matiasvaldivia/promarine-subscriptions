# Modelo de Inventario — Documentación técnica

> **Estado**: MOCK · LOCAL  
> **Invariante**: El stock disponible nunca es negativo, salvo variantes con `allow_backorder = true`.

---

## 1. Modelo conceptual

```
MockInventoryLocation (dónde está el stock: depósito, virtual)
    ↓  1:N
MockInventoryLevel (cuánto stock hay de cada variante en cada ubicación)
    ├── on_hand_quantity       (físico total)
    ├── reserved_quantity      (comprometido para pedidos activos)
    ├── committed_quantity     (confirmado para envío)
    └── available_quantity     (= on_hand - reserved - committed)
    ↓  1:N
MockInventoryMovement (registro inmutable de cada cambio)
```

---

## 2. Tablas

### `inventory_locations`

```sql
id            BIGINT UNSIGNED PRIMARY KEY
name          VARCHAR(100)
address       VARCHAR(255)
is_primary    TINYINT(1) DEFAULT 0
is_mock       TINYINT(1) DEFAULT 1
status        ENUM('active','inactive') DEFAULT 'active'
```

### `inventory_levels`

```sql
id                   BIGINT UNSIGNED PRIMARY KEY
location_id          BIGINT UNSIGNED FK inventory_locations.id
variant_id           BIGINT UNSIGNED FK mock_product_variants.id
sku                  VARCHAR(100)
available_quantity   INT NOT NULL DEFAULT 0
reserved_quantity    INT NOT NULL DEFAULT 0
committed_quantity   INT NOT NULL DEFAULT 0
on_hand_quantity     INT NOT NULL DEFAULT 0
low_stock_threshold  INT NOT NULL DEFAULT 5
UNIQUE KEY (location_id, variant_id)
```

### `inventory_reservations`

```sql
id               BIGINT UNSIGNED PRIMARY KEY
level_id         BIGINT UNSIGNED FK inventory_levels.id
subscription_id  CHAR(36)
quantity         INT NOT NULL
status           ENUM('active','released','fulfilled','cancelled') DEFAULT 'active'
reserved_until   TIMESTAMP
```

### `inventory_movements`

```sql
id                   BIGINT UNSIGNED PRIMARY KEY
inventory_level_id   BIGINT UNSIGNED FK inventory_levels.id
type                 ENUM('adjustment','reservation','release','fulfillment','sync','correction')
delta                INT NOT NULL       -- positivo o negativo
quantity_before      INT NOT NULL
quantity_after       INT NOT NULL
reason               VARCHAR(255)
reference_type       VARCHAR(50)        -- 'order', 'subscription', 'manual'
reference_id         VARCHAR(100)
performed_by         BIGINT UNSIGNED FK users.id
```

---

## 3. InventoryService — API

```php
// Disponibilidad
$qty = $service->availableStock(int $variantId): int

// Ajuste manual (positivo o negativo)
$service->adjust(MockInventoryLevel $level, int $delta, string $reason, int $userId): void

// Reserva para suscripción activa
$service->reserve(int $variantId, int $qty, string $subscriptionUuid): void

// Libera reserva (cancelación, pausar)
$service->release(int $variantId, int $qty, string $subscriptionUuid): void

// Cumple reserva (fulfillment exitoso)
$service->fulfill(int $variantId, int $qty, string $subscriptionUuid): void

// Resumen para dashboard
$service->summary(): array  // ['in_stock'=>int, 'low_stock'=>int, 'out_of_stock'=>int]

// Sincronización mock con Shopify
$service->syncWithShopify(MockInventoryLevel $level): void
```

---

## 4. Regla de stock negativo

```php
// InventoryService::adjust()
if ($level->available_quantity + $delta < 0) {
    if (!$level->variant->allow_backorder) {
        throw new \DomainException(
            "Stock insuficiente. Disponible: {$level->available_quantity}, Solicitado: {$delta}"
        );
    }
}
```

Las variantes con `allow_backorder = true` aceptan ajustes negativos (sirven para pre-pedidos).

---

## 5. Umbrales de alerta

| Condición | Estado | Acción en dashboard |
|---|---|---|
| `available_quantity > low_stock_threshold` | in_stock | ✅ Verde |
| `available_quantity > 0 AND ≤ threshold` | low_stock | ⚠️ Amarillo |
| `available_quantity == 0` | out_of_stock | 🔴 Rojo |

---

## 6. Datos de prueba (seeder)

```
1 ubicación: "Depósito Central Promarine" (is_primary=true)
8 niveles de stock (2 variantes × 4 productos):
  - cada variante con: on_hand=50, reserved=0, available=50
  - low_stock_threshold=5
```

---

## 7. Sincronización con Shopify

En modo mock, `syncWithShopify()` invoca `MockShopifyGateway::updateInventoryLevel()`.  
Genera un item en el sync run activo sin conectarse a Shopify real.
