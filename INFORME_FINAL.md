# Informe de implementación — Promarine Suscripciones

Fecha: 2026-08-03  
Alcance: demo local, persistente y navegable; ninguna integración productiva fue activada.

## Estado entregado

| Área | Estado | Fuente / naturaleza |
|---|---|---|
| Landing mobile-first | Implementada | UI local |
| Catálogo de cuatro productos | Implementado | storefront público + propuesta mock |
| Recursos gráficos | Descargados localmente | público, con SHA-256 y manifiesto |
| Checkout | Implementado | 100% simulado, sin tarjeta |
| Login Tamara | Implementado | credenciales de entorno, hash Laravel |
| Entrevista | Implementada | persistencia MySQL e historial |
| Informe/exportaciones | Implementado | HTML, Markdown, JSON, CSV |
| Políticas | Borradores sembrados | pendientes de aprobación |
| Simulador | Implementado | eventos sanitizados e idempotentes |
| Mercado Pago | Mock | no conectado |
| Shopify | Mock | no crea pedidos reales |
| IGS | Mock | no envía ventas reales |

## Tablas

`users`, `roles`, `role_user`, `products`, `product_variants`, `subscription_plans`, `landing_settings`, `policy_categories`, `policies`, `policy_versions`, `interview_sections`, `interview_questions`, `interview_options`, `interview_answers`, `decision_records`, `mock_customers`, `mock_subscriptions`, `mock_payments`, `mock_orders`, `mock_order_items`, `mock_igs_events`, `integration_events`, `asset_imports`, `audit_logs`, `attachments`, `policy_acceptances`, más tablas técnicas de sesiones, caché y colas de Laravel.

## Investigación pública

El endpoint local de Firecrawl fue validado correctamente. El scrape puntual recibió HTTP 429; se dejó evidencia y se continuó mediante endpoints públicos de producto, sin login ni carrito. Se importaron Marine Epic, Marine Fusion, Echa Marine y Marine Pulse. Las imágenes se sirven localmente y el manifiesto registra fuente y hash.

## Reglas verificables

1. El pago rechazado termina en `payment_rejected` y no ejecuta Shopify.
2. El pago aprobado crea un único pedido mock y luego un evento IGS mock.
3. `idempotency_key` es única y el flujo reutiliza el resultado existente.
4. Los payloads administrativos no contienen secretos ni datos completos de tarjeta.

## Pendientes y riesgos

- Políticas comerciales, legales, precio, envío, reintentos, cancelación y atribución IGS requieren decisiones de Tamara.
- La compatibilidad real de Mercado Pago con recurrencia/Shopify Subscription Contracts no se presume; requiere verificación administrativa y sandbox autorizados.
- IGS necesita un contrato explícito para renovaciones, refunds, atribución original e idempotencia antes de producción.
- No publicar esta demo sin endurecer cookies/TLS, gestión de archivos y observabilidad para el entorno objetivo.

## Incidencia de validación

Durante la primera instalación, la compatibilidad temporal con las claves heredadas de `.env` propagó `host_bd` al contenedor de aplicación y Artisan alcanzó esa base externa. Se detuvo esa ruta, no se intentó borrar ni revertir información remota, y Docker quedó corregido para que la aplicación use exclusivamente el host de servicio `mysql`. La comprobación final informa `host=mysql`; los tests se ejecutan contra SQLite en memoria y ya no modifican MySQL local.

La base externa puede conservar tablas creadas durante esa primera ejecución. Su inspección o reversión requiere autorización explícita y un alcance de respaldo, porque sería una operación destructiva fuera de la demo local.

## URLs locales

- Landing: http://localhost:8080
- Login: http://localhost:8080/admin/login
- Dashboard: http://localhost:8080/admin
- Entrevista: http://localhost:8080/admin/interview
- Simulador: http://localhost:8080/admin/simulator

## Evidencia visual

- `storage/app/promarine-imports/captures/landing-desktop.png`
- `storage/app/promarine-imports/captures/landing-mobile-390x844.png`
