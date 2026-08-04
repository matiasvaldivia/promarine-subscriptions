# Matriz Comercial (Cart Matrix) — Documentación técnica

> **Definición**: 4 productos × 2 variantes × 3 planes = **24 combinaciones**  
> **Estado actual**: DEMO — Precios PENDIENTE DE DEFINICIÓN DEFINITIVA por Tamara  
> **Tabla**: `product_subscription_matrix`

---

## 1. Dimensiones de la matriz

| Dimensión | Valores actuales (DEMO) |
|---|---|
| **Productos** (4) | Erizo 150g, Erizo 300g, Cochayuyo 200g, Cochayuyo 400g |
| **Variantes por producto** (2) | Tamaño S / Tamaño L (DEMO — pendiente definición real) |
| **Planes** (3) | Mensual, Bimestral, Trimestral |

---

## 2. Tabla: `product_subscription_matrix`

```sql
id                   BIGINT UNSIGNED PRIMARY KEY
product_id           BIGINT UNSIGNED FK mock_products.id
variant_id           BIGINT UNSIGNED FK mock_product_variants.id
plan_id              BIGINT UNSIGNED FK subscription_plans.id
sku                  VARCHAR(100)
base_price           DECIMAL(10,2) NOT NULL   -- precio sin descuento
discount_type        ENUM('percentage','fixed') DEFAULT 'percentage'
discount_value       DECIMAL(5,2) DEFAULT 0.00
subscription_price   DECIMAL(10,2) NOT NULL   -- = base_price * (1 - discount/100)
cycle_days           INT NOT NULL             -- 30, 60, 90
status               ENUM('active','inactive','draft') DEFAULT 'active'
sort_order           INT DEFAULT 0
notes                TEXT
shopify_variant_id   VARCHAR(100)             -- ID externo (mock)
UNIQUE KEY (product_id, variant_id, plan_id)
```

---

## 3. Cálculo del precio de suscripción

```php
// CartMatrixService::updateRow()
if ($data['discount_type'] === 'percentage') {
    $subscriptionPrice = $row->base_price * (1 - $data['discount_value'] / 100);
} else {
    $subscriptionPrice = $row->base_price - $data['discount_value'];
}

if ($subscriptionPrice < 0) {
    throw new \DomainException('El precio de suscripción no puede ser negativo.');
}
```

---

## 4. CartMatrixService — API

```php
// Todas las filas (admin)
$service->allRows(): Collection

// Solo filas activas (wizard público)
$service->activeRows(): Collection

// Filtrar por producto
$service->forProduct(int $productId): Collection

// Filtrar por plan
$service->forPlan(int $planId): Collection

// Actualizar precio/descuento (no afecta historial)
$service->updateRow(int $rowId, array $data): void

// Obtener combinación específica
$service->getRow(int $productId, int $variantId, int $planId): ?Model
```

---

## 5. Integridad histórica

La edición de una fila **no modifica** pedidos ni suscripciones previas.  
Los precios históricos quedan registrados en `mock_orders.unit_price` y `mock_subscriptions.price_at_subscription`.

---

## 6. Filas inactivas

Las filas con `status = 'inactive'` o `'draft'`:
- Son visibles en el panel administrativo.
- **No** aparecen en el wizard público.
- **No** pueden ser seleccionadas en nuevas suscripciones.

---

## 7. Planes actuales (DEMO)

| Plan | `cycle_days` | Descuento demo |
|---|---|---|
| Mensual | 30 | 5% |
| Bimestral | 60 | 10% |
| Trimestral | 90 | 15% |

> ⚠️ **PENDIENTE**: Los descuentos definitivos deben ser aprobados por Tamara.  
> Hasta entonces, todos los precios están marcados como DEMO.

---

## 8. Edición desde el panel

1. Admin accede a `/admin/cart-matrix`.
2. Hace clic en "Editar" en la fila correspondiente.
3. Modifica `discount_value` y `discount_type`.
4. El controlador llama `CartMatrixService::updateRow()`.
5. `subscription_price` se recalcula automáticamente.
6. Se registra una nota de auditoría via `AuditService::log()`.

---

## 9. Relación con el wizard público

```
/Plan-de-subscription
  → MembershipController@show
  → CartMatrixService::activeRows()
  → filtra por selección del usuario (producto + plan)
  → muestra precio final
```

---

## 10. Tests

```bash
docker compose exec -T app php artisan test --filter=CartMatrixTest
```

Valida:
- 24 combinaciones exactas
- Recálculo de precios
- Precio negativo lanza excepción
- Filas inactivas excluidas de `activeRows()`
