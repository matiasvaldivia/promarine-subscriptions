# Máquina de Estados de Pedidos

> **INVARIANTE**: Ningún pedido puede retroceder a un estado anterior.  
> **INVARIANTE**: Un pedido entregado o cancelado es terminal.  
> **INVARIANTE**: Un pago rechazado nunca produce un pedido transmitido.

---

## Estados

| Estado | Descripción |
|---|---|
| `pending` | Pedido creado, esperando confirmación de pago |
| `payment_pending` | Pago iniciado, esperando confirmación del proveedor |
| `authorized` | Pre-autorizado, esperando captura |
| `payment_approved` | Pago confirmado, listo para transmitir a Shopify |
| `payment_rejected` | Pago rechazado — **estado bloqueante** |
| `transmitted` | Transmitido a Shopify exitosamente |
| `sync_error` | Error en la transmisión a Shopify — reintentar |
| `in_preparation` | En preparación en depósito |
| `shipped` | Despachado con tracking |
| `delivered` | Entregado al cliente — **estado terminal positivo** |
| `cancelled` | Cancelado — **estado terminal** |
| `refunded` | Reembolsado — **estado terminal** |

---

## Grafo de transiciones

```
pending
  → payment_pending
  → payment_approved
  → payment_rejected
  → cancelled

payment_pending
  → payment_approved
  → payment_rejected

authorized
  → payment_approved
  → payment_rejected
  → cancelled

payment_approved
  → transmitted
  → sync_error
  → cancelled

payment_rejected
  → pending              ← retry del pago
  (NUNCA → transmitted)

transmitted
  → in_preparation
  → sync_error
  → shipped
  → cancelled

sync_error
  → transmitted          ← reintento
  → cancelled

in_preparation
  → shipped
  → cancelled

shipped
  → delivered
  → cancelled

delivered
  (SIN transiciones — TERMINAL)

cancelled
  (SIN transiciones — TERMINAL)

refunded
  (SIN transiciones — TERMINAL)
```

---

## Implementación: `OrderStateMachine`

```php
// app/Services/OrderStateMachine.php

public function guardTransition(string $from, string $to): void
{
    $allowed = $this->allowedTransitions($from);

    if (!in_array($to, $allowed)) {
        throw new \DomainException(
            "Transición inválida: '$from' → '$to'. " .
            "Permitidas: " . implode(', ', $allowed)
        );
    }
}

public function allowedTransitions(string $status): array
{
    return self::TRANSITIONS[$status] ?? throw new \DomainException(
        "Estado desconocido: '$status'"
    );
}
```

---

## Historial de transiciones

Cada transición queda registrada en `order_status_history`:

```sql
CREATE TABLE order_status_history (
    id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_id       BIGINT UNSIGNED NOT NULL,
    from_status    VARCHAR(50),
    to_status      VARCHAR(50) NOT NULL,
    reason         TEXT,
    changed_by     BIGINT UNSIGNED,   -- FK users.id (null = sistema)
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES mock_orders(id)
);
```

---

## Reglas de negocio críticas

1. **`payment_rejected` → `transmitted`**: `DomainException` siempre.
2. **Terminal positivo** (`delivered`): sin transiciones.
3. **Terminal negativo** (`cancelled`, `refunded`): sin transiciones.
4. **`sync_error`** puede reintentar solo hacia `transmitted` o `cancelled`.
5. **Transición HTTP**: el controlador captura `DomainException`, redirige con error y no cambia el estado.

---

## Tests asociados

```bash
docker compose exec -T app php artisan test --filter=OrderStateMachineTest
docker compose exec -T app php artisan test --filter=OrderBusinessRulesTest
```

Archivo: `tests/Unit/OrderStateMachineTest.php`  
Archivo: `tests/Feature/OrderBusinessRulesTest.php`
