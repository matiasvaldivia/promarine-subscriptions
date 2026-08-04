# Plan de implementación Promarine Subscriptions

Documento de continuidad técnica y funcional del proyecto.

Fecha de actualización: 2026-08-04  
Entorno actual: demo local con integraciones simuladas  
URL pública: https://promarine.matiasvaldivia.com.ar/  
URL local: http://localhost:8080/

## 1. Objetivo general

Construir una experiencia web mobile-first, similar a una aplicación, para:

- Presentar los productos y sus distintas presentaciones.
- Guiar al cliente por un formulario de suscripción paso a paso.
- Mostrar calendario de pagos y entregas antes de continuar.
- Simular la autorización de pago sin generar cobros reales.
- Permitir el acceso del cliente a su plan mediante email y código temporal.
- Permitir que Tamara gestione decisiones, preguntas y simulaciones desde un área privada.
- Incorporar una suscripción anual independiente, sin obligar a comprar productos.

## 2. Trabajo realizado

### 2.1 Catálogo y recursos gráficos

- Se revisaron y organizaron los recursos de `public/assets/promarine`.
- Se incorporaron versiones optimizadas WebP para logo, erizo Promarine, Tamara y composiciones de productos.
- Se conservaron recursos de botellas, cajas, sellos, instituciones y certificaciones.
- Se incorporaron las presentaciones visuales de Marine Epic, Marine Fusion, Echa Marine y Marine Pulse.
- Se agregó la imagen de Tamara para el hero, con adaptación responsive.
- Se documentó el inventario en `ASSET_CATALOG.md`.

### 2.2 Landing principal

- Hero “Tu Promarine, todos los meses”.
- Órbitas animadas alrededor del erizo Promarine.
- Logo con movimiento y microinteracciones.
- Cards de productos con reacciones hover/focus.
- CTA hacia el flujo de suscripción.
- Sección de comunidad, podcasts y charlas.
- Footer conectado con la tienda clásica de Promarine.
- Redes sociales y enlaces de soporte.

### 2.3 Wizard de suscripción de productos

El flujo permite seleccionar producto, presentación, consumo, frecuencia, plan, dirección y calendario.

También se implementó:

- Formulario reutilizable para clientes autenticados.
- Uso de datos guardados del cliente.
- Resumen del plan en móvil arriba del botón de continuar.
- Barra de progreso sticky.
- Acciones sticky en móvil.
- Bloqueo del scroll exterior durante el wizard.
- Calendario horizontal táctil con arrastre, flechas y botón “ver próximos ciclos”.
- Avisos de podcasts y charlas de la comunidad.
- Popups explicativos para cada consentimiento.
- Confirmaciones sobre cobro recurrente, pedidos, políticas y cancelación.
- Preservación de las imágenes de cada presentación seleccionada.

### 2.4 Pago simulado

- Pantalla de emulación de Mercado Pago.
- Resultado aprobado simulado.
- Pantalla final de compra exitosa.
- Creación de pedido y pago mock únicamente después de la aprobación simulada.
- Email de confirmación de la compra simulada.
- Identificadores mock e integración mock con Shopify e IGS.
- Advertencia visible de que no se genera un cobro real.

### 2.5 Calendario del cliente

- Área `/mi-plan` para solicitar acceso.
- Verificación por código de seis dígitos enviado al email.
- Sesión temporal de cliente.
- Dashboard con producto, presentación, estado, frecuencia, importe y calendario.
- Acceso a recompra desde el plan existente.
- Código con expiración y límite de intentos.
- Respuesta neutral para emails que no tienen un plan activo.

### 2.6 Área privada de Tamara

- Login privado en `/login`.
- `/admin/login` redirige al login canónico.
- Dashboard privado.
- Cuestionarios e informe consolidado.
- Simulador de escenarios.
- Métricas de preguntas, políticas, productos y suscripciones simuladas.
- Contraseñas almacenadas con hash.
- Registro de auditoría de accesos.

### 2.7 Corrección del error 419

Se corrigió el error `419 Page Expired` del login público.

Motivo: Laravel no confiaba en los encabezados del proxy HTTPS del túnel.

Corrección aplicada en `bootstrap/app.php`: confianza en `X-Forwarded-For`, `X-Forwarded-Host`, `X-Forwarded-Port`, `X-Forwarded-Proto` y `X-Forwarded-Prefix`.

Se verificó que el POST del login ahora responde con redirección HTTPS y no con 419.

### 2.8 Suscripción anual independiente

Ruta principal: `/Plan-de-subscription`

La nueva página permite solicitar una suscripción anual sin comprar productos.

Incluye:

- Hero gráfico con erizo Promarine y beneficios orbitales.
- Descuentos para miembros.
- Entregas prioritarias.
- Información exclusiva.
- Sección “sí incluye / no incluye”.
- Explicación en tres pasos.
- Formulario de nombre, email, teléfono opcional y preferencias de comunidad.
- Estado “pendiente de confirmación”.
- Confirmación visual.
- Email de recepción de solicitud.
- Persistencia independiente en `membership_subscriptions`.
- Protección contra duplicados por email y estado pendiente.
- No crea productos, pedidos ni pagos.

Archivos principales:

- `app/Http/Controllers/MembershipController.php`
- `app/Models/MembershipSubscription.php`
- `app/Mail/MembershipRequested.php`
- `database/migrations/2026_08_04_020000_create_membership_subscriptions_table.php`
- `resources/views/membership/show.blade.php`
- `resources/views/membership/confirmation.blade.php`
- `resources/views/emails/membership-requested.blade.php`
- `resources/css/membership.css`

## 3. Verificaciones realizadas

### Pruebas automatizadas

La suite enfocada de membresía pasó correctamente:

- 3 pruebas.
- 19 verificaciones.
- Solicitud de membresía persistida.
- Email simulado enviado.
- Cero suscripciones de producto creadas.
- Cero pedidos creados.
- Cero pagos creados.
- Consentimiento obligatorio validado.
- Solicitud repetida idempotente.

### Build frontend

El build de Vite se completó correctamente.

Último build verificado:

- CSS: `app-Cef_Rkfb.css`
- JavaScript: `app-BQrOxfaD.js`

### Responsive

Verificado a 390 px:

- Overflow horizontal: 0 px.
- Formulario visible.
- Hero visible.
- Imagen del erizo cargada.
- Beneficios legibles.
- Navegación móvil sticky.

### HTTPS público

Verificado:

- `/Plan-de-subscription` responde 200.
- `/login` responde 200.
- El login corregido mantiene redirecciones HTTPS.

## 4. Estado de integraciones

Actualmente el proyecto utiliza modo demo/mock para Mercado Pago, Shopify, IGS, pedidos, pagos y envíos.

El correo SMTP está configurado para el entorno existente. Los emails de demo se envían según la configuración vigente, pero deben seguir verificándose con una bandeja real antes de producción.

## 5. Datos de prueba

### Área privada de Tamara

- URL: `/login`
- Usuario: `tamara`
- La contraseña no se documenta en este archivo ni en el repositorio.

### Portal de cliente

El seed mock contiene:

- Email: `tamara.demo@invalid.local`

El código del portal se genera dinámicamente, dura 10 minutos y se invalida después de utilizarlo. No existe un código fijo permanente.

## 6. Deuda técnica y pendientes

### 6.1 Antes de una salida comercial

- Definir precio real de la membresía anual.
- Definir porcentaje exacto de descuento.
- Definir productos elegibles.
- Definir reglas de prioridad logística.
- Publicar términos de membresía.
- Publicar política de cancelación y reembolso.
- Implementar cobro real de membresía.
- Implementar webhook real de Mercado Pago.
- Reemplazar Shopify e IGS mock por integraciones reales.
- Confirmar tratamiento fiscal y facturación.
- Definir consentimiento legal de marketing y comunidad.

### 6.2 Seguridad

- Configurar `APP_ENV=production` y desactivar `APP_DEBUG`.
- Usar una `APP_KEY` estable y protegida.
- Verificar `SESSION_SECURE_COOKIE=true` bajo HTTPS.
- Limitar proxies confiables cuando la infraestructura esté estable.
- Añadir recuperación de contraseña y 2FA para Tamara.
- Revisar permisos por rol.
- Auditar y rotar credenciales SMTP si fueron expuestas durante pruebas.
- Agregar CSP y HSTS cuando el dominio definitivo esté confirmado.

### 6.3 Correo

- Configurar un destinatario real de pruebas para el portal.
- Confirmar SPF, DKIM y DMARC.
- Añadir reintentos y cola.
- Añadir observabilidad de entregas y rebotes.
- Evitar depender de direcciones `.invalid.local` en pruebas remotas.

### 6.4 Calidad y mantenimiento

- Agregar pruebas E2E del login sobre HTTPS.
- Agregar pruebas E2E del portal con código temporal.
- Agregar pruebas responsive para 390 px, 768 px y desktop.
- Normalizar textos con problemas de codificación heredados.
- Revisar accesibilidad: foco, contraste, labels y teclado.
- Verificar el asset del sello e-Trade bajo lazy loading.
- Agregar restricción contra duplicados bajo concurrencia.
- Implementar ciclo de vida para solicitudes pendientes.
- Agregar listado administrativo de miembros.
- Definir retención y anonimización de datos personales.

### 6.5 Operaciones

- Crear repositorio remoto y configurar `origin`.
- Definir ramas `main`, `staging` y `dev`.
- Añadir CI para tests, lint y build.
- Crear procedimiento de deploy y rollback.
- Crear backups de base de datos.
- Monitorizar aplicación, PHP-FPM, Nginx, MySQL y SMTP.
- Documentar renovación del túnel público.
- Verificar ejecución automática de migraciones en cada release.

## 7. Comandos operativos

```powershell
docker compose up -d --build
docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan test --filter=MembershipSubscriptionTest
C:\Users\pc\.cache\codex-runtimes\codex-primary-runtime\dependencies\node\bin\node.exe node_modules\vite\bin\vite.js build
docker compose logs --tail 120 app nginx
```

## 8. Commit actual

```text
d411ba9 feat: add annual membership and fix HTTPS CSRF login
```

El repositorio local quedó limpio después del commit. Todavía no existe un repositorio remoto configurado.

## 9. Próximo paso recomendado

Tamara debe definir:

1. Precio anual.
2. Descuento exacto.
3. Productos alcanzados.
4. Beneficios logísticos reales.
5. Condiciones de cancelación.
6. Texto legal de aceptación.

Con esas definiciones se puede transformar la membresía de demostración en una suscripción comercial real sin mezclarla con la compra de productos.
## 10. Deuda técnica de temas claro y oscuro

### Estado actual

- El tema claro es el modo inicial y usa la paleta blanca/turquesa de la tienda oficial.
- El tema oscuro conserva la estética original Promarine.
- El switch del navbar persiste la elección en `localStorage` con la clave `promarine-theme`.
- Las imágenes del orbit se intercambian mediante `data-dark-src` y `data-light-src`.

### Deuda y riesgos

1. Los tokens de color están concentrados en overrides al final de `resources/css/app.css`.
2. Todavía existen colores literales heredados que pueden producir contraste desigual.
3. La preferencia no se sincroniza entre dispositivos ni se guarda en el perfil del cliente.
4. No se usa `prefers-color-scheme` como valor inicial cuando no hay preferencia guardada.
5. Las imágenes por tema no tienen un manifiesto central.
6. Falta una auditoría automatizada WCAG AA para ambos temas.
7. Falta una prueba visual/e2e del switch en desktop, móvil y wizard activo.

### Estructura propuesta para una futura corrección

```text
resources/
  css/themes/
    tokens.css       # tokens semánticos compartidos
    light.css        # valores del tema claro
    dark.css         # valores del tema oscuro
    components.css   # componentes sin colores literales
  js/theme-manager.js
  data/product-media.js
tests/Browser/ThemeToggleTest.php
```

### Plan de saneamiento

1. Extraer colores a tokens semánticos (`--color-bg`, `--color-text`, `--color-border`, `--color-accent`).
2. Reemplazar colores literales y eliminar overrides duplicados.
3. Centralizar la prioridad: preferencia guardada → preferencia del sistema → claro.
4. Centralizar el manifiesto de assets y sus fallbacks.
5. Ejecutar auditoría WCAG AA en 360, 768 y 1440 px.
6. Validar que el tema no cambie ni pierda estado durante el wizard.
