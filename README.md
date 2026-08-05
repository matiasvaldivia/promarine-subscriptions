# Promarine Suscripciones — Mobile-First E-Commerce & Subscription Engine

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Docker](https://img.shields.io/badge/Docker-Enabled-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://docker.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4.0-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Tests](https://img.shields.io/badge/Tests-79%20Passed-brightgreen?style=for-the-badge&logo=php&logoColor=white)](tests)

Plataforma de suscripciones e-commerce mobile-first desarrollada en **Laravel 12**, **Alpine.js** y **Tailwind CSS v4**. Diseñada para orquestar la selección de planes, recurrencia de entrega, cobranza a través de **Mercado Pago** y sincronización de pedidos y ventas hacia **Shopify** e **IGS**.

---

## 📌 Tabla de Contenidos

- [Arquitectura del Sistema](#-arquitectura-del-sistema)
- [Características Principales](#-características-principales)
- [Seguridad & Hardening](#-seguridad--hardening)
- [Requisitos Previos e Instalación](#-requisitos-previos-e-instalación)
- [Comandos de Operación (Makefile)](#-comandos-de-operación-makefile)
- [Configuración de Entorno (.env)](#-configuración-de-entorno-env)
- [Suite de Pruebas & Calidad](#-suite-de-pruebas--calidad)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Licencia y Propiedad](#-licencia-y-propiedad)

---

## 🏗️ Arquitectura del Sistema

La aplicación opera bajo una arquitectura de micro-servicios dockerizada con desacoplamiento mediante Interfaces de Dominio para todas las pasarelas externas:

```text
               ┌────────────────────────────────────────────────────────┐
               │                Nginx Reverse Proxy (:8080)             │
               └───────────────────────────┬────────────────────────────┘
                                           │
                                           ▼
               ┌────────────────────────────────────────────────────────┐
               │            Laravel 12 Application (PHP 8.3)            │
               │   Blade Templates + Alpine.js + Tailwind CSS v4 + Vite │
               └──────┬────────────────────┬────────────────────┬───────┘
                      │                    │                    │
                      ▼                    ▼                    ▼
        ┌───────────────────┐  ┌───────────────────┐  ┌───────────────────┐
        │ MercadoPagoGateway│  │  ShopifyGateway   │  │    IGSGateway     │
        │    Interface      │  │    Interface      │  │    Interface      │
        └─────────┬─────────┘  └─────────┬─────────┘  └─────────┬─────────┘
                  │                      │                      │
                  ▼                      ▼                      ▼
        ┌───────────────────┐  ┌───────────────────┐  ┌───────────────────┐
        │  MockMercadoPago  │  │   MockShopify     │  │      MockIGS      │
        │      Adapter      │  │     Adapter       │  │      Adapter      │
        └───────────────────┘  └───────────────────┘  └───────────────────┘
                      │                    │                    │
                      └────────────────────┼────────────────────┘
                                           │
                                           ▼
               ┌────────────────────────────────────────────────────────┐
               │             MySQL 8.4 Engine (Persistencia)            │
               │    Suscripciones · Pagos · Historial · Auditoría       │
               └────────────────────────────────────────────────────────┘
```

---

## ✨ Características Principales

### 🛒 Storefront & Checkout Mobile-First
- **Flujo "Armá tu plan"**: Interfaz optimizada e interactiva construida con Alpine.js.
- **Frecuencias dinámicas**: Configuración flexible de intervalos de despacho y facturación.
- **Transparencia en Checkout**: Integración sin captura de datos sensibles de tarjetas (cumplimiento PCI-DSS por diseño).

### ⚙️ Engine de Suscripciones (`MockSubscriptionFlow`)
- **Idempotencia estricta**: Previene duplicación de cobros y creación doble de pedidos/ventas.
- **Regla de integridad**: Únicamente los pagos en estado `approved` desencadenan la generación de pedidos e integración IGS.
- **Persistencia en MySQL**: Trazabilidad completa de decisiones, historial de políticas, versiones de propuestas y eventos de auditoría.

### 🛡️ Panel Administrativo (`/admin`)
- Autenticación protegida con bloqueo por throttle, CSRF y regeneración de sesión.
- Gestión de propuestas comerciales y simulaciones de cálculo de comisiones.
- Exportación de auditoría e historial en formatos **Markdown**, **JSON** y **CSV**.

---

## 🔒 Seguridad & Hardening

El sistema cuenta con auditoría E2E aprobada y controles de seguridad activos:

- **Validación de Webhooks con Firma HMAC SHA-256**: Bloqueo directo (HTTP 400 Bad Request) ante notificaciones de Mercado Pago sin cabecera `X-Signature` o con firma inválida.
- **Middleware de Headers de Seguridad (`AddSecurityHeaders`)**:
  - `Content-Security-Policy`: Restricción estricta de orígenes autorizados (self + scripts/frames de Mercado Pago).
  - `Strict-Transport-Security (HSTS)`: Habilitado automáticamente bajo conexiones HTTPS.
  - `Permissions-Policy`: Desactivación de APIs sensibles del navegador (`camera=()`, `microphone=()`, `geolocation=()`).
- **Protección contra Información de Servidor**: Remoción explícita del header `X-Powered-By` en bootstrap.
- **Indexación Bloqueada**: Inclusión automática de metaetiquetas `noindex, nofollow` en rutas administrativas.

---

## 🚀 Requisitos Previos e Instalación

### Requisitos
- **Docker Desktop** (con soporte para Compose)
- **Git**
- **Node.js 20+** (para desarrollo local sin Docker)

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
   *Nota: Configurar `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `TAMARA_USERNAME` y `TAMARA_PASSWORD` en el archivo `.env`.*

3. **Desplegar con Docker:**
   ```bash
   make install
   # O alternativamente: docker compose up -d
   ```

4. **Acceso a Servicios Locales:**
   - **Landing / Storefront**: [http://localhost:8080](http://localhost:8080)
   - **Login Administrativo**: [http://localhost:8080/admin/login](http://localhost:8080/admin/login)
   - **Mailpit (Sandbox de correo)**: [http://localhost:8025](http://localhost:8025)

---

## 🛠️ Comandos de Operación (Makefile)

El archivo `Makefile` simplifica la gestión del ciclo de vida del contenedor:

| Comando | Descripción |
| :--- | :--- |
| `make up` | Inicia todos los contenedores en segundo plano. |
| `make down` | Detiene los contenedores preservando los datos de MySQL. |
| `make fresh` | Recrea la base de datos local y ejecuta los seeders. |
| `make test` | Ejecuta la suite de pruebas automatizadas (PHPUnit / Pest). |
| `make logs` | Muestra los logs en tiempo real del servidor PHP/Nginx. |
| `make shell` | Abre una terminal interactiva dentro del contenedor PHP. |
| `make import-promarine` | Refresca los recursos públicos e imágenes desde endpoints oficiales. |

---

## ⚙️ Configuración de Entorno (.env)

El comportamiento de los adaptadores de integración se controla mediante variables de modo:

```dotenv
# Modos de Adaptadores (mock / live)
MERCADOPAGO_MODE=mock
SHOPIFY_MODE=mock
IGS_MODE=mock

# Credenciales Sandbox para Pruebas de Mercado Pago
# DNI de prueba: 12345678
# Titular: Nombre exacto según la tarjeta de prueba utilizada
```

*En entorno local, los registros creados incluyen el flag `is_mock=true` y `environment=local`.*

---

## 🧪 Suite de Pruebas & Calidad

La suite automatizada valida la integridad de los flujos de pago, webhooks, modelos y reglas de negocio:

```bash
# Ejecutar suite completa
php artisan test

# Salida esperada:
# Tests: 79 passed (266 assertions)
```

---

## 📁 Estructura del Proyecto

```text
promarine-subscriptions/
├── app/
│   ├── Http/
│   │   ├── Controllers/         # Controladores de Storefront, Admin y Webhooks
│   │   └── Middleware/          # AddSecurityHeaders y autenticación
│   ├── Services/
│   │   ├── Gateways/            # Interfaces e Implementaciones Mock (MP, Shopify, IGS)
│   │   └── SubscriptionEngine/  # Lógica central del flujo de suscripciones
│   └── Models/                  # Modelos Eloquent con auditoría y casts
├── bootstrap/                   # Configuración del framework y middlewares
├── config/                      # Archivos de configuración
├── database/                    # Migraciones, Seeders y Factories
├── docker/                      # Configuraciones de Nginx y PHP-FPM
├── public/                      # Entrypoint public e imágenes importadas
├── resources/
│   ├── css/                     # Estilos Tailwind CSS v4
│   ├── js/                      # Lógica frontend y Alpine.js
│   └── views/                   # Plantillas Blade (Storefront y Admin)
├── routes/                      # Definición de rutas (web, admin, api, webhooks)
├── tests/                       # Suite de pruebas unitarias y de integración
├── docker-compose.yml           # Definición del entorno Docker
└── Makefile                     # Accesos directos para desarrollo
```

---

## 📄 Licencia y Propiedad

Desarrollado para **Promarine**. Todos los derechos reservados.
