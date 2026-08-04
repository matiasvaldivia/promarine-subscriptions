# Catálogo de recursos Promarine

Inventario normalizado de `public/assets/promarine`. Convención: nombres en minúsculas, palabras separadas por guiones y categoría expresada por carpeta y nombre.

## Resumen

| Grupo | Cantidad | Uso recomendado |
|---|---:|---|
| `brand/` | 9 | Identidad Promarine, fondos claros u oscuros |
| `institutions/` | 1 | Identidad institucional CONICET |
| `products/` | 8 | Packshots y composiciones verificadas del catálogo |
| `demo/` | 16 | Exploración visual; no publicar como evidencia o certificación |
| `optimized/` | 22 | Derivados WebP livianos utilizados por la landing pública |
| **Total** | **56** | |

## Marca (`brand/`)

| Archivo | Dimensiones | Descripción |
|---|---:|---|
| `promarine-logo-dark.svg` | vectorial | Logo oscuro oficial importado del sitio |
| `promarine-logo-white-web.png` | 832 × 249 | Logo blanco oficial optimizado para web |
| `promarine-lockup-stacked-white.png` | 1500 × 1500 | Composición vertical blanca |
| `promarine-lockup-horizontal-mark-right-white.png` | 1500 × 1500 | Composición horizontal blanca, símbolo a la derecha |
| `promarine-lockup-horizontal-mark-left-white.png` | 1500 × 1500 | Composición horizontal blanca, símbolo a la izquierda |
| `promarine-wordmark-white.png` | 1500 × 1500 | Logotipo tipográfico blanco |
| `promarine-lockup-stacked-dark.png` | 1500 × 1500 | Composición vertical oscura |
| `promarine-lockup-horizontal-dark.png` | 1500 × 1500 | Composición horizontal oscura |
| `promarine-sea-urchin-mark-white.png` | 440 × 406 | Símbolo de erizo blanco aislado |

## Institucionales (`institutions/`)

| Archivo | Dimensiones | Descripción |
|---|---:|---|
| `conicet-logo-color.png` | 352 × 345 | Logo CONICET color con transparencia, importado del sitio |

## Productos (`products/`)

| Archivo | Dimensiones | Descripción |
|---|---:|---|
| `marine-epic-packshot-square.png` | 2000 × 2000 | Packshot cuadrado Marine Epic |
| `marine-fusion-packshot-square.png` | 2000 × 2000 | Packshot cuadrado Marine Fusion |
| `echa-marine-packshot-square.png` | 2000 × 2000 | Packshot cuadrado Echa Marine |
| `marine-pulse-packshot-square.png` | 2000 × 2000 | Packshot cuadrado Marine Pulse |
| `marine-epic-composition-portrait.png` | 1122 × 1402 | Composición vertical Marine Epic |
| `marine-fusion-composition-portrait.png` | 1122 × 1402 | Composición vertical Marine Fusion; corrige el antiguo typo `fucion` |
| `echa-marine-composition-portrait.png` | 1122 × 1402 | Composición vertical Echa Marine |
| `marine-pulse-composition-portrait.png` | 1122 × 1402 | Composición vertical Marine Pulse |

## Recursos de demostración (`demo/`)

Estos archivos tienen señales claras de generación con IA —nombres originales `ChatGPT Image`, textos deformados y sellos no trazables—. Sirven para prototipos y dirección visual, pero no deben presentarse como envases, claims o certificaciones oficiales sin validación humana y documental.

### Sellos e iconos

Todos miden 1536 × 1024 y tienen transparencia.

- `seal-gmp.png`
- `seal-gluten-free.png`
- `seal-non-gmo.png`
- `seal-heavy-metals-tested.png`
- `seal-cruelty-free.png`
- `seal-clinically-tested.png`
- `icon-respiratory-support.png`

### Composiciones generadas de producto

Todos miden 1024 × 1024 y contienen textos o etiquetado no confiable.

- `marine-epic-bottle.png` — botella con identidad visual azul
- `marine-fusion-bottle.png` — botella con identidad visual turquesa
- `echa-marine-bottle.png` — botella con identidad visual rosa
- `marine-pulse-bottle.png` — botella con identidad visual naranja
- `echa-marine-box.png`
- `marine-epic-box.png`
- `marine-fusion-box.png`
- `marine-pulse-box.png`

### Institucional experimental

- `conicet-logo-glow.png` — 1536 × 1024; recreación con resplandor, no usar como logo oficial.

## Recursos optimizados (`optimized/`)

Derivados WebP de 240 a 480 px para teléfonos y pantallas de alta densidad. Mantienen intactos los PNG originales y reducen el conjunto utilizado por la landing de 33,84 MB a 0,43 MB.

- Marca: `promarine-logo-300.webp`, `promarine-urchin-320.webp`
- Productos: `{slug}-composition-480.webp`, `{slug}-bottle-480.webp`, `{slug}-box-480.webp`
- Institucionales y sellos: `optimized/trust/*-240.webp`

Se regeneran ejecutando `scripts/optimize_promarine_images.py` con Pillow.

## Rutas activas en la aplicación

- Logo de cabecera: `/assets/promarine/optimized/promarine-logo-300.webp`
- Logo institucional: `/assets/promarine/optimized/trust/conicet-240.webp`
- Producto del formulario: `/assets/promarine/optimized/{slug}-composition-480.webp`
- Presentación botella: `/assets/promarine/demo/{slug}-bottle.png`
- Presentación monodosis: `/assets/promarine/demo/{slug}-box.png`
