# 🌊 Promarine Suscripciones — Plataforma E-commerce & Motor de Suscripciones

Plataforma empresarial mobile-first desarrollada en **Laravel 12 (PHP 8.3)**, **Tailwind CSS v4** y **Alpine.js** para la gestión integral del programa *"Suscríbete y Ahorra"* de Promarine.

Permite la simulación y automatización end-to-end de planes de suscripción marina, integración multi-gateway con **Mercado Pago**, **Shopify** e **IGS**, administración de catálogo de productos/variantes/frecuencias, portal privado de cliente y panel de control administrativo.

---

## 📐 Arquitectura del Sistema

```mermaid
graph TD
    Client[📱 Cliente / Browser] -->|HTTP / Port 8080| Nginx[🌐 Nginx 1.27]
    Nginx -->|FastCGI| App[⚡ PHP-FPM 8.3 / Laravel 12]
    
    subgraph Core Application Engine
        App --> Auth[🔐 Auth & Security Middleware]
        App --> Wizard[🧙‍♂️ 7-Step Subscription Wizard]
        App --> Admin[🛠️ Admin Dashboard / RBAC]
        App --> StateEngine[🔄 Order & Subscription State Machine]
    end

    subgraph Storage & Services
        App -->|Eloquent ORM| MySQL[(🗄️ MySQL 8.4)]
        App -->|SMTP / Port 8025| Mailpit[✉️ Mailpit Sandbox]
    end

    subgraph Integration Gateways (Mockable)
        StateEngine --> MPGateway[💳 MercadoPagoGatewayInterface]
        StateEngine --> ShopifyGateway[🛍️ ShopifyGatewayInterface]
        StateEngine --> IGSGateway[📊 IGSGatewayInterface]
    end

    MPGateway -.->|Mock / Live API| MP[Mercado Pago]
    ShopifyGateway -.->|Mock / Live REST| Shopify[Shopify Storefront]
    IGSGateway -.->|Mock / Live API| IGS[IGS Systems]
```

---

## 🔒 Seguridad & Hardening (Auditoría E2E Aprobada)

El sistema cuenta con un esquema de protección de nivel bancario/empresarial:

- **Validación Estricta de Webhooks (Mercado Pago)**: Firma **HMAC SHA-256** obligatoria via header `X-Signature`. Las solicitudes sin firma o con firma inválida son rechazadas inmediatamente con **HTTP 400 Bad Request**.
- **Middleware `AddSecurityHeaders`**:
  - `Content-Security-Policy`: Restricción estricta de fuentes de script, estilos, frames e imágenes (`self`, `'unsafe-inline'`, `'unsafe-eval'`, dominios autorizados de Mercado Pago).
  - `Strict-Transport-Security (HSTS)`: Activado automáticamente bajo conexiones HTTPS (`max-age=31536000; includeSubDomains`).
  - `Permissions-Policy`: Bloqueo preventivo de sensores y APIs sensibles (`camera=()`, `microphone=()`, `geolocation=()`, `payment=()`).
- **Ocultamiento de Fingerprinting**: Remoción explícita del encabezado `X-Powered-By` en el arranque de la aplicación.
- **Protección Administrativa**: Autenticación con throttling, regeneración de sesión contra Session Fixation, protección CSRF en todos los formularios y directivas `noindex, nofollow` en rutas `/admin`.
- **Zero PCI Scope**: El checkout no recopila ni almacena datos de tarjeta de crédito/débito ni códigos CVV en servidores propios.

---

## 🚀 Requisitos Previos e Instalación

### Requisitos
- **Docker Desktop** (con soporte para Docker Compose v2)
- **Git**
- **Make** (opcional, simplifica comandos de consola)

### Paso a paso

1. **Clonar el repositorio:**
   ```bash
   git clone git@github.com:matiasvaldivia/promarine-subscriptions.git
   cd promarine-subscriptions
   ```

2. **Configurar el archivo de entorno:**
   ```bash
   cp .env.example .env
   ```
   *Asegúrese de definir contraseñas locales para MySQL (`DB_PASSWORD`, `DB_ROOT_PASSWORD`) y las credenciales de administración (`TAMARA_USERNAME`, `TAMARA_PASSWORD`).*

3. **Desplegar e inicializar la aplicación:**
   ```bash
   make install
   # O alternativamente: docker compose up -d && docker compose exec app php artisan migrate --seed
   ```

4. **Acceso a Servicios Locales:**
   - 🌐 **Storefront / Landing**: [http://localhost:8080](http://localhost:8080)
   - 🔐 **Login Administrativo**: [http://localhost:8080/admin/login](http://localhost:8080/admin/login)
   - 📊 **Panel de Control**: [http://localhost:8080/admin](http://localhost:8080/admin)
   - ✉️ **Mailpit (Sandbox de correo)**: [http://localhost:8025](http://localhost:8025)

---

## 🛠️ Comandos de Operación (`Makefile`)

| Comando | Descripción |
| :--- | :--- |
| `make up` | Inicia todos los contenedores en segundo plano. |
| `make down` | Detiene los contenedores preservando la base de datos MySQL. |
| `make fresh` | Recrea las tablas de MySQL y ejecuta los seeders de catálogo y usuarios. |
| `make test` | Ejecuta la suite de pruebas automatizadas (PHPUnit / Pest). |
| `make logs` | Muestra los logs en tiempo real de Nginx y PHP-FPM. |
| `make shell` | Abre una terminal interactiva dentro del contenedor PHP. |
| `make import-promarine` | Refresca recursos públicos y sincroniza el manifiesto de imágenes. |

---

## ⚙️ Configuración de Modos de Integración (`.env`)

Los adaptadores de integración operan bajo una arquitectura desacoplada basada en Interfaces (`GatewayInterface`). Se controlan mediante variables de modo:

```dotenv
# Modos de Adaptadores (mock / live)
MERCADOPAGO_MODE=mock
SHOPIFY_MODE=mock
IGS_MODE=mock
```

- En modo `mock`, todas las llamadas simulan aprobaciones/rechazos de forma idéntica al entorno real sin impactar servicios ni cobrar dinero.
- Cada registro persistido en la base de datos incluye la bandera `is_mock=true` y `environment=local`.

---

## 🧪 Suite de Pruebas & Cobertura

El proyecto cuenta con una suite completa de pruebas unitarias y de integración:

```bash
docker compose exec app php artisan test
```

### Estado de la Auditoría E2E:
- ✅ **79/79 Tests Aprobados** (266 Aserciones).
- 🛡️ **P0 (Crítico)**: Requisito de firma en Webhook MP implementado y verificado.
- 🛡️ **P1 (Alto)**: Middleware de cabeceras de seguridad CSP, HSTS y Permissions-Policy activo.
- 🎨 **Build de Frontend**: Compilación exitosa via Vite (`npm run build`).

---

## 🚦 Estado del Proyecto & Roadmap a Producción

Actualmente el sistema se encuentra:
- ✅ **Listo para Demo Interna**
- ⚠️ **Listo para Homologación**
- ❌ **No listo para Producción Comercial** (Requiere ajustes de accesibilidad P2/P3, backups automáticos, SSL end-to-end y credenciales productivas de MP/Shopify/IGS).

---

## 📄 Licencia

Desarrollado exclusivamente para **Promarine**. Todos los derechos reservados.
