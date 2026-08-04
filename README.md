# Promarine Suscripciones — demo local

Aplicación Laravel 12 mobile-first para validar la propuesta de suscripciones de Promarine. Persiste decisiones y simulaciones en MySQL, pero **no cobra, no crea pedidos reales y no envía ventas a IGS**.

## Arquitectura

```text
Nginx :8080 → PHP 8.3 / Laravel → MySQL 8.4
                         ├─ MercadoPagoGatewayInterface → MockMercadoPagoGateway
                         ├─ ShopifyGatewayInterface     → MockShopifyGateway
                         └─ IGSGatewayInterface         → MockIGSGateway
Mailpit :8025
```

Blade + Alpine.js resuelven la interacción; Tailwind CSS 4 + Vite construyen los estilos. El motor `MockSubscriptionFlow` aplica transacciones e idempotencia: sólo un pago `approved` puede crear un pedido y una venta IGS mock.

## Inicio

```bash
git clone <repo>
cd promarine-subscriptions
cp .env.example .env
```

Complete `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `TAMARA_USERNAME` y `TAMARA_PASSWORD` con valores locales. Después:

```bash
make install
```

En Windows sin `make`, ejecute los comandos equivalentes definidos en `Makefile`.

- Landing: http://localhost:8080
- Login privado: http://localhost:8080/admin/login
- Dashboard: http://localhost:8080/admin
- Mailpit: http://localhost:8025

## Operación

```bash
make up                 # inicia servicios
make down               # detiene servicios, conserva MySQL
make fresh              # recrea y siembra la base local
make test               # pruebas automatizadas
make logs               # logs de contenedores
make shell              # shell del contenedor PHP
make import-promarine   # refresca recursos públicos y manifest
```

La importación lee únicamente endpoints públicos, guarda copias locales en `public/assets/promarine` y registra URL, hash SHA-256 y procedencia en `storage/app/promarine-imports/manifest.json`.

## Datos reales, importados y simulados

- **Persistidos realmente en MySQL:** usuarios administrativos, respuestas e historial, políticas/versiones, catálogo de propuesta, suscripciones/pagos/pedidos/eventos mock y auditoría.
- **Importados:** nombres, variantes, descripciones e imágenes públicas del storefront; el manifiesto conserva su origen.
- **Simulados:** precios de suscripción, stock, tokenización, cobros, pedidos Shopify, ventas/comisiones IGS, cancelaciones y refunds.
- **No conectados:** Shopify Admin, Subscription Contracts, Mercado Pago recurrente e IGS productivo.

## Seguridad

- No commitear `.env` ni compartir credenciales.
- Rotar cualquier credencial que haya sido expuesta por otro medio.
- El seeder aborta si faltan `TAMARA_USERNAME` o `TAMARA_PASSWORD`; la contraseña se almacena con el cast `hashed` de Laravel.
- No activar modos reales desde esta demo, no guardar tarjeta/CVV y no marcar pedidos reales como pagados desde un mock.
- Login con CSRF, throttle, regeneración de sesión, bloqueo temporal y auditoría. Las páginas `/admin` llevan `noindex,nofollow`.

## Variables de entorno

Todas están documentadas con valores ficticios o vacíos en `.env.example`. Los adaptadores seleccionan mock mediante:

```dotenv
MERCADOPAGO_MODE=mock
SHOPIFY_MODE=mock
IGS_MODE=mock
```

No se implementan llamadas reales aunque existan variables de credenciales.

## Criterios críticos cubiertos

- El checkout no contiene campos de número de tarjeta ni CVV.
- Un pago rechazado nunca crea un pedido.
- Una clave idempotente repetida nunca crea dos pagos/pedidos.
- Todo registro de integración incluye `is_mock=true` y `environment=local`.
- Entrevista guardable, versionada por entradas de historial y exportable a Markdown, JSON y CSV.
