# CONTEXTO PROYECTO: menu_qr_3d
> Lee este archivo COMPLETO al inicio de cada nuevo chat antes de escribir cualquier código.
> Este documento es la fuente de verdad del proyecto. No inventar nada que no esté aquí.

---

## ✅ CHECKLIST DE DESARROLLO

### Fases Completadas
- [x] **Fase 1** — Backend y BD: config.php, router, endpoints menú dummy
- [x] **Fase 2** — Frontend menú cliente: Vue 3 + Vite, componentes, build
- [x] **Fase 3** — Panel admin: login, CRUD restaurantes/categorias/productos, protección de rutas
- [x] **Fase 4** — Integración 3D: upload-fotos, upload-glb manual, model-viewer en modal, cron script
- [x] **Fase 5** — QR & Mesas: endpoint mesas, admin Mesas.vue, QR por mesa, badge de mesa en menú
- [x] **Fase 6** — Sistema de Pedidos: carrito sin sesión, checkout con WhatsApp deep link, tabs Negocio/Pedidos en admin
- [ ] **Fase 7** — Personalización por pasos (estilo Rappi/Uber Eats): BD ✅, API ✅, Vue pendiente

### Funcionalidades Implementadas
- [x] API endpoints: `menu`, `login`, `restaurantes`, `categorias`, `productos`, `mesas`, `upload-fotos`, `upload-glb`, `upload-logo`, `job-status`
- [x] CRUD completo de productos (create, read, update, delete lógico)
- [x] Subida de múltiples fotos por producto + actualiza `foto_principal` automáticamente
- [x] Subida manual de .glb validado por magic bytes (`glTF`) desde admin
- [x] Model-Viewer en modal con AR nativo (webxr + quick-look) cuando `tiene_ar = 1`
- [x] CRUD mesas por restaurante, QR generado en browser con lib `qrcode` (npm)
- [x] QR descargable como PNG; URL = `{origin}/menu/?r={slug}&mesa={numero}`
- [x] Badge "Mesa X" en header del menú público cuando URL incluye `?mesa=`
- [x] Autenticación via cookies HttpOnly (`Set-Cookie` en login, `$_COOKIE['token']` en helpers.php, `credentials: 'include'` en fetch)
- [x] Protección de rutas admin con beforeEach guard async (caché de auth-check, `resetAuth()` exportado)
- [x] CORS habilitado en `/uploads/` vía `.htaccess` (necesario para model-viewer en dev)
- [x] **Panel Apariencia** en Dashboard: tema de color, frase QR, WiFi en QR, logo del restaurante
- [x] **5 temas visuales** para el menú público: `calido`, `oscuro`, `moderno`, `rapida`, `rosa` — cada uno con sus CSS variables en MenuPublico.vue
- [x] **QR Card descargable** — diseño rico con gradiente vertical del tema, logo circular del restaurante (fallback emoji), sección WiFi (caja estilizada con nombre/clave de red), frase personalizada
- [x] **Logo del restaurante** — upload desde admin (JPG/PNG/WebP, max 2 MB), guardado en `uploads/logos/`, URL absoluta en DB, visible en: card QR, navbar del admin, menú público (vía campo `logo_url` en API)
- [x] **Favicon dinámico** — watch en Dashboard.vue actualiza `<link rel="icon">` con el logo del restaurante al cargar
- [x] **Sistema de Pedidos** — toggle por restaurante (`pedidos_activos`). Carrito sin sesión en Vue `ref([])`. Checkout: tipo entrega (recoger/envío con costo configurable), datos cliente, pago (efectivo con denominación / transferencia con botones copiar por campo — solo visible si `pedidos_trans_activo = 1`), observaciones por platillo. Al confirmar: POST a `/api/?route=pedidos` → abre WhatsApp con resumen pre-llenado. Admin: tab Negocio (config + compartir menú con mensaje personalizado guardado en DB) + tab Pedidos (lista con status + auto-refresh 30s). Nuevas tablas: `pedidos`, `pedido_items`. Nuevas columnas en `restaurantes`: `pedidos_activos`, `pedidos_envio_activo`, `pedidos_envio_costo`, `pedidos_whatsapp`, `pedidos_trans_activo`, `pedidos_trans_clabe/cuenta/titular/banco`, `compartir_mensaje`.
- [x] **Utilería ucfirst** — `src/utils/ucfirst.js`. Primera letra mayúscula al tipear. Patrón: `:value + @input` con `ucfirst($event.target.value)`. Usada en Dashboard (CRUD), CheckoutModal (nombre, dirección, observación) y ProductoModal (observación).
- [ ] **Personalización por pasos (Fase 7)** — Sistema genérico de grupos de opciones por producto estilo Rappi/Uber Eats. BD completa: `producto_grupos`, `producto_opciones`, `pedido_item_opciones` + columnas en `productos` (`tiene_personalizacion`, `aviso_complemento`, `aviso_categoria_id`). API: `GET/POST producto-grupos`, extensión de `menu` GET (grupos embebidos), `pedidos` POST/GET con opciones. **Pendiente Vue:** `PersonalizacionModal.vue` (bottom sheet nuevo), fix X sticky en `ProductoModal.vue`, extensión de `carrito.js`, integración en `MenuPublico.vue`, extensión de `CheckoutModal.vue`, sección personalización en `TabPlatillos.vue`.

### Decisión: Flujo 3D sin Meshy API
La API de Meshy requiere plan Pro ($20/mes) para acceso programático. Se adoptó **flujo semi-manual (Opción B)**:
1. Admin genera el .glb en meshy.ai (web) o TRELLIS.2 (Hugging Face Space, gratis)
2. Descarga el .glb manualmente
3. Sube el .glb desde el panel admin → botón "Subir 3D (.glb)" en la tabla de productos
4. El sistema valida magic bytes y actualiza `tiene_ar = 1` automáticamente
- El cron `check_meshy_jobs.php` sigue existente pero no es necesario para este flujo

### Paths críticos de archivos
- `foto_principal` en BD → relativo a `/uploads/`, ej: `fotos/1/foto_1_0_1234.jpg`
  - URL completa en API: `UPLOADS_URL . $foto_principal` = `http://menu.local/uploads/fotos/1/...`
- `modelo_glb_path` en BD → solo nombre del archivo, ej: `modelo_1_1234.glb`
  - URL completa en API: `UPLOADS_URL . 'modelos/' . $modelo_glb_path`
- `logo_url` en BD → relativo a `/uploads/`, ej: `logos/logo_1_1234.jpg`
  - URL completa en API: `UPLOADS_URL . $logo_url` — se antepone en **ambos** endpoints (`menu` y `restaurantes`)
  - Guardado físico: `uploads/logos/logo_{restaurante_id}_{timestamp}.{ext}`
- `fotos_producto.ruta` → relativo a webroot, ej: `uploads/fotos/1/foto.jpg`

### Bugs/Workarounds Conocidos
- ⚠️ **URL de logo en GET restaurantes** — Corregido: la respuesta del endpoint GET `restaurantes` ahora antepone `UPLOADS_URL` a `logo_url` (igual que el endpoint `menu`). Sin este fix, la imagen se resolvía como ruta relativa y el servidor devolvía HTML (200 OK pero imagen rota).

### Funcionalidades Pendientes
- [x] **Descarga QR fiel al preview** — html2canvas captura el DOM real de `.qr-card-dm` → PNG pixel-perfect. Selector Normal/Alta calidad (scale 2x/3x).
- [ ] **Mesas / QR por mesa** — `Mesas.vue` existe (completo con su QR card) pero está **inactivo**. El QR actual es uno solo por restaurante, gestionado desde Dashboard. Activar cuando se requiera multi-mesa con QR individual por mesa.
- [ ] **Validación de formularios** — Cliente + servidor, feedback visual (precio positivo, slug sin espacios)
- [ ] **Feedback visual mejorado** — Loaders en botones, toasts de éxito/error
- [ ] **Thumbnail de foto en admin** — Mostrar foto_principal en la tabla de productos del admin
- [ ] **Cron registrado en cPanel** — Script existe (`cron/check_meshy_jobs.php`) pero no está en scheduler
- [ ] **Meshy API key** — Aún en placeholder; configurar cuando se tenga acceso al plan API

---

### Accionables técnicos pendientes (arquitectura)
> Ver detalles de razonamiento y decisiones en `CLAUDE.md` sección "DECISIONES ARQUITECTÓNICAS".

#### A1 — Seguridad de autenticación (Prioridad: Alta — ✅ Implementado 2026-03-11)
- [x] **Migrar token de localStorage a cookies HttpOnly**
  - PHP emite `Set-Cookie: token=...; HttpOnly; SameSite=Strict; Secure (si HTTPS)`
  - `helpers.php`: `require_auth()` lee `$_COOKIE['token']`; nuevas funciones `set_auth_cookie()` / `clear_auth_cookie()`
  - `api/index.php`: login emite cookie (no retorna token en body); nuevos endpoints `logout` y `auth-check`
  - `useApi.js`: eliminado todo manejo de token; todos los fetch usan `credentials: 'include'`
  - `router/index.js`: guard async con caché (`authenticated`); exporta `resetAuth()`
  - `Dashboard.vue`: logout llama `POST logout` + `resetAuth()`; uploads usan `credentials: 'include'`
  - `Login.vue`: eliminado `localStorage`

#### A2 — Sistema de temas CSS (Prioridad: Alta — ✅ Implementado 2026-03-11)
- [x] `src/assets/theme.css` — variables del sistema (espaciados, radios, sombras, tipografía) + clases globales `.btn-primary`, `.btn-secondary`, `.btn-danger`, `.btn-sm`
- [x] `src/utils/themes.js` — fuente de verdad de los 5 temas (TEMAS + TEMAS_EXTRA). Importado por Dashboard.vue
- [x] Botones estandarizados: `btn-ver`, `btn-agregar-carrito`, `btn-confirmar` extienden `.btn-primary` global

#### A3 — Arquitectura de componentes (Prioridad: Media — ✅ Implementado 2026-03-15)
- [x] **Dashboard.vue** particionado en 5 tabs + orquestador (~170 líneas vs 1721 originales)
  - `src/components/admin/tabs/`: `TabPlatillos.vue`, `TabCategorias.vue`, `TabApariencia.vue`, `TabNegocio.vue`, `TabPedidos.vue`
  - Dashboard pasa props (`restauranteId`, `categorias`, `restaurante`, `menuUrl`, `active`) y recibe emits (`notif`, `categorias-changed`, `restaurante-updated`, `tema-preview`)
- [x] **`src/components/`** reorganizado por dominio
  - `src/components/menu/` — ProductoCard, ProductoModal, ModelViewer3D, CarritoFlotante, CheckoutModal
  - `src/components/admin/tabs/` — tabs del panel
- [x] **`src/assets/admin.css`** — estilos compartidos del admin (`.card`, `.field`, `.sw`, `.btn-icon`, etc.)

#### A4 — Estado global con Pinia (Prioridad: Media — ✅ Implementado 2026-03-15)
- [x] **Carrito migrado a Pinia store** con persistencia en localStorage
  - `src/stores/carrito.js` — `items`, `agregar()`, `vaciar()`, `total()`
  - `pinia-plugin-persistedstate` — carrito sobrevive recargas
  - `MenuPublico.vue` — usa `carritoStore.agregar()` y `carritoStore.vaciar()`
- [ ] **Store de restaurante activo en admin** — pendiente si se necesita multi-restaurante

### Testing Local
- ✅ Base de datos: MySQL tablas creadas
- ✅ Usuario de prueba: katche4@gmail.com / katch123
- ✅ Login funciona
- ✅ CRUD restaurantes/productos/mesas funciona
- ✅ Subida de fotos funciona (guardan en `/uploads/fotos/` y actualiza `foto_principal`)
- ✅ Subida de .glb funciona (guardan en `/uploads/modelos/` y actualiza `tiene_ar = 1`)
- ✅ Model-Viewer muestra .glb en el modal del menú público
- ✅ QR generados correctamente, descarga PNG funciona
- ✅ Badge de mesa visible en menú público con `?mesa=N`
- ⚠️ Meshy API sin key (no aplica al flujo semi-manual actual)
- ⚠️ Cron no registrado en cPanel (no necesario para flujo semi-manual)

---

## 1. QUÉ ES ESTE PROYECTO

Menú digital para restaurantes que se abre al escanear un QR desde la mesa.
Permite ver los platillos en **3D interactivo** (siempre disponible) y en **AR sobre la mesa** (cuando el dispositivo lo soporta).
Los modelos 3D se generan automáticamente desde fotos tomadas por el dueño del restaurante, usando la API de Meshy.ai. No se hacen modelos 3D manualmente.

---

## 2. STACK TECNOLÓGICO DEFINITIVO

| Capa | Tecnología | Notas |
|---|---|---|
| Frontend cliente | Vue 3 + Vite + Pinia | Compilado localmente, dist subido por FTP |
| Frontend admin | Vue 3 + Vite | Mismo proyecto, mismo build |
| 3D / AR | Google Model-Viewer (web component) | Sin Three.js, sin A-Frame |
| Generación 3D | Meshy.ai API (image-to-3d) | Genera .glb automáticamente desde fotos |
| Backend | PHP 8.1+ nativo | Sin Laravel, sin frameworks |
| Base de datos | MySQL | Incluido en cPanel |
| Servidor | cPanel (hosting compartido propio) | Sin Docker, sin render.com, sin Railway |
| Almacenamiento | Carpeta /uploads/ en el mismo servidor | Sin S3, sin Cloudflare R2 |
| Cron jobs | Cron de cPanel | Para polling de jobs de Meshy |
| QR | Generado con qrcode.js en el frontend admin | Sin servicios externos |

**Regla de oro:** Nada que no pueda correr en un cPanel estándar. Si una solución requiere Node.js en servidor, Docker, o servicios externos de pago adicionales al plan ya confirmado, no aplica.

---

## 3. SERVICIOS EXTERNOS CONTRATADOS

- **Meshy.ai** — Plan gratuito para inicio, Pro si escala
  - Plan gratuito: **200 créditos/mes sin costo**, solo registro
  - Cada modelo usa ~3-5 créditos → entre 40 y 65 modelos/mes gratis
  - Para un restaurante piloto (~30-50 platillos) el plan gratuito es suficiente
  - Los modelos se generan una sola vez, no se repite el gasto cada mes
  - Plan Pro ~$20/mes (1,500 créditos) solo si se escala a varios restaurantes activos
  - Endpoint: `POST https://api.meshy.ai/openapi/v1/image-to-3d`
  - Polling: `GET https://api.meshy.ai/openapi/v1/image-to-3d/{task_id}`
  - El modelo resultante se descarga como `.glb` y se guarda en el servidor propio

---

## 4. ESTRUCTURA DE CARPETAS EN EL SERVIDOR

```
/public_html/
├── menu/                          ← Frontend Vue compilado (dist)
│   ├── index.html
│   ├── assets/
│   └── .htaccess                  ← Redirige todo a index.html para Vue Router
│
├── admin/                         ← Panel admin Vue compilado (dist)
│   ├── index.html
│   ├── assets/
│   └── .htaccess
│
├── api/                           ← Backend PHP nativo
│   ├── index.php                  ← Router + TODOS los endpoints en un solo archivo
│   ├── config.php                 ← Constantes, conexión DB, config multi-entorno
│   └── helpers.php                ← Funciones reutilizables
│
├── uploads/                       ← Archivos públicos
│   ├── fotos/                     ← Fotos originales por producto_id
│   │   └── {producto_id}/
│   └── modelos/                   ← Archivos .glb descargados de Meshy
│
└── cron/                          ← Fuera de public si es posible, si no aquí
    └── check_meshy_jobs.php       ← Se ejecuta cada 2 min via cron cPanel
```

**Estructura del proyecto Vue (local, antes de compilar):**
```
/menu_qr_3d_vue/
├── src/
│   ├── views/
│   │   ├── MenuPublico.vue        ← Vista del cliente (QR → menú)
│   │   └── admin/
│   │       ├── Dashboard.vue       ← VISTA PRINCIPAL DEL ADMIN (platillos, categorías, apariencia, pedidos)
│   │       ├── Productos.vue       ← NO USADA (ruta /admin/restaurantes/:id/productos — inactiva)
│   │       ├── Mesas.vue           ← NO USADA (ruta /admin/restaurantes/:id/mesas — inactiva)
│   │       └── Restaurantes.vue
│   ├── components/
│   │   ├── menu/                  ← Componentes del menú público
│   │   │   ├── ProductoCard.vue
│   │   │   ├── ProductoModal.vue  ← Contiene <model-viewer>
│   │   │   ├── ModelViewer3D.vue  ← Wrapper del web component
│   │   │   ├── CarritoFlotante.vue
│   │   │   └── CheckoutModal.vue
│   │   └── admin/
│   │       └── tabs/              ← Tabs del panel admin
│   │           ├── TabPlatillos.vue
│   │           ├── TabCategorias.vue
│   │           ├── TabApariencia.vue
│   │           ├── TabNegocio.vue
│   │           └── TabPedidos.vue
│   ├── stores/
│   │   └── carrito.js             ← Pinia store con persistedstate
│   ├── composables/
│   │   └── useApi.js              ← Fetch a /api/ con credentials: include
│   ├── router/
│   │   └── index.js
│   └── main.js
├── public/
│   └── imgs/                      ← Imágenes estáticas de UI (placeholders, íconos)
└── vite.config.js
```

---

## 5. CONFIGURACIONES CRÍTICAS DE VITE

```javascript
// vite.config.js
export default defineConfig({
  base: '/menu/',     // Ajustar según donde se suba: '/menu/' o '/' si es subdominio
  plugins: [vue()]
})
```

**Regla de imágenes en Vue:**
- Imágenes de UI estáticas → carpeta `public/imgs/` → referenciar como `/menu/imgs/foto.png`
- Imágenes de productos → vienen de la API como URLs absolutas `https://dominio.com/uploads/...`
- NUNCA importar imágenes de productos como módulos ES dentro de componentes

**Variables de entorno Vite:**
```
.env.local           ← gitignored (desarrollo local)
.env.production      ← sí al repo (sin credenciales)
VITE_PUBLIC_ORIGIN=https://nodosmx.com
```
Patrón de uso en Vue para URLs públicas (QR, compartir menú):
```js
const origin = import.meta.env.VITE_PUBLIC_ORIGIN || window.location.origin
```
> ⚠️ `window.location.origin` devuelve `localhost:5173` en dev → SIEMPRE usar la env var para URLs que se compartirán externamente (WhatsApp, QR, etc.).

**.htaccess para Vue Router (modo history):**
```apache
Options -MultiViews
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.html [QSA,L]
```

---

## 6. FLUJO COMPLETO DEL SISTEMA

### Flujo Admin (dueño del restaurante)
1. Admin entra a `tudominio.com/admin/` → login
2. Crea/edita restaurante → genera QR automáticamente
3. Crea producto → llena nombre, precio, descripción
4. Sube 4-8 fotos del platillo desde el panel
5. PHP recibe fotos → las guarda en `/uploads/fotos/{id}/` → llama Meshy API
6. Meshy devuelve un `task_id` → se guarda en tabla `meshy_jobs` con status `pending`
7. Cron cada 2 min → consulta Meshy por cada job pendiente
8. Cuando Meshy responde `SUCCEEDED` → cron descarga el `.glb` a `/uploads/modelos/` → actualiza producto con `tiene_ar = 1`
9. Admin ve el estado en tiempo real (polling desde Vue admin cada 10s)

### Flujo Cliente
1. Escanea QR en la mesa → abre `tudominio.com/menu/?r=restaurante-slug&mesa=5`
2. Vue carga → hace GET a `/api/?route=menu&restaurante=slug`
3. Ve categorías y productos con fotos
4. Toca un producto → modal con `<model-viewer>` mostrando el `.glb` rotando en 3D
5. Si el dispositivo soporta AR → ve botón "Ver en tu mesa 📱"
6. En Android Chrome → WebXR Scene Viewer. En iOS Safari → AR Quick Look

---

## 7. AUTENTICACIÓN ADMIN

Token estático comparado contra `ADMIN_TOKEN` en `config.php`. Flujo con **cookies HttpOnly**:
- Login: PHP valida credenciales → emite `Set-Cookie: token=...; HttpOnly; SameSite=Strict; Secure (si HTTPS)`
- Requests autenticados: browser envía la cookie automáticamente (`credentials: 'include'` en fetch)
- `helpers.php`: `require_auth()` lee `$_COOKIE['token']`; `set_auth_cookie()` / `clear_auth_cookie()`
- Endpoints: `auth-check` (valida sesión activa), `logout` (limpia cookie)
- Router guard: llama `auth-check` una vez por carga de página (resultado cacheado en `authenticated`); exporta `resetAuth()` para limpiar caché tras login/logout
- Cookie sin `Secure` en local (HTTP), con `Secure` en producción (HTTPS) — detección automática por `$_SERVER['HTTPS']`

---

## 8. API ENDPOINTS DEFINIDOS

Todos bajo `/api/index.php` con parámetro `?route=`:

| Método | Ruta | Descripción | Auth |
|---|---|---|---|
| GET | `/api/?route=menu&restaurante={slug}` | Menú público completo | No |
| POST | `/api/?route=login` | Login admin | No |
| GET | `/api/?route=restaurantes` | Lista restaurantes | Sí |
| POST | `/api/?route=restaurantes` | Crear restaurante | Sí |
| GET | `/api/?route=categorias&restaurante_id={id}` | Lista categorías | Sí |
| POST | `/api/?route=categorias` | Crear categoría | Sí |
| GET | `/api/?route=productos&restaurante_id={id}` | Lista productos | Sí |
| POST | `/api/?route=productos` | Crear producto | Sí |
| PUT | `/api/?route=productos&id={id}` | Editar producto | Sí |
| DELETE | `/api/?route=productos&id={id}` | Eliminar producto (lógico) | Sí |
| GET | `/api/?route=mesas&restaurante_id={id}` | Lista mesas + slug del restaurante | Sí |
| POST | `/api/?route=mesas` | Crear mesa `{restaurante_id, numero}` | Sí |
| DELETE | `/api/?route=mesas&id={id}` | Eliminar mesa (lógico) | Sí |
| POST | `/api/?route=upload-fotos` | Subir fotos, actualiza `foto_principal` | Sí |
| POST | `/api/?route=upload-glb` | Subir .glb validado, `tiene_ar=1` | Sí |
| POST | `/api/?route=upload-logo` | Subir logo del restaurante (JPG/PNG/WebP, max 2MB) | Sí |
| GET | `/api/?route=job-status&producto_id={id}` | Estado conversión 3D (Meshy) | Sí |
| GET | `/api/?route=pedidos&restaurante_id={id}` | Lista pedidos con items nested + opciones | Sí |
| POST | `/api/?route=pedidos` | Crear pedido + items + opciones | No |
| PUT | `/api/?route=pedidos&id={id}` | Actualizar status del pedido | Sí |
| GET | `/api/?route=producto-grupos&producto_id={id}` | Grupos y opciones de un producto | No |
| POST | `/api/?route=producto-grupos` | Guardar/reemplazar grupos+opciones de un producto | Sí |

---

## 9. COMPATIBILIDAD AR OBJETIVO

| Plataforma | Modo 3D | Modo AR |
|---|---|---|
| Android Chrome 79+ | ✅ | ✅ WebXR Scene Viewer |
| iOS Safari 15+ | ✅ | ✅ AR Quick Look |
| iOS Chrome | ✅ | ⚠️ Solo abre Safari para AR |
| Otros navegadores | ✅ | ⚠️ Sin garantía AR |

`<model-viewer>` detecta automáticamente. Si no hay AR disponible, el botón no aparece. El usuario siempre ve el 3D interactivo como fallback.

**Requisito crítico del servidor:** HTTPS activo (SSL). WebXR y acceso a cámara no funcionan en HTTP.

---

## 10. COSTOS DEL PROYECTO

| Concepto | Costo |
|---|---|
| cPanel hosting | Ya contratado, $0 adicional |
| Meshy.ai (plan gratuito) | $0/mes (200 créditos, ~40-65 modelos/mes) |
| Meshy.ai (plan Pro, si escala) | ~$20/mes |
| SSL Let's Encrypt | $0 (incluido en cPanel) |
| Model-Viewer de Google | $0 (open source) |
| qrcode.js | $0 (open source) |
| **Total operativo inicial** | **$0/mes** (hasta que el volumen justifique Pro) |

---

## 11. FASES DE DESARROLLO (ORDEN SUGERIDO)

### Fase 1 — Backend y BD
- Crear tablas MySQL (ver CONTEXTO_BASE_DE_DATOS.md)
- `config.php` con constantes y conexión PDO
- Router `index.php`
- Endpoint GET menu (prueba con datos dummy)

### Fase 2 — Frontend menú cliente
- Vue 3 + Vite configurado con `base` correcto
- Componentes: MenuPublico, ProductoCard, ProductoModal con model-viewer
- Probar con modelos .glb de ejemplo (Meshy tiene samples gratuitos)
- Deploy a cPanel, verificar .htaccess e imágenes

### Fase 3 — Panel admin
- Login, CRUD restaurantes, CRUD productos
- Subida de fotos con preview
- Indicador de estado de conversión 3D

### Fase 4 — Integración Meshy
- Endpoint upload-fotos → llamada a Meshy API
- Cron check_meshy_jobs.php
- Descarga automática del .glb
- Actualización de estado en admin

### Fase 5 — QR y detalles finales
- Generación de QR por mesa en el admin
- Ajustes de UX, animaciones de entrada
- Pruebas AR en dispositivos reales (Android + iOS)

---

## 12. ENTORNO LOCAL DE DESARROLLO

| Concepto | Detalle |
|---|---|
| XAMPP activo | Segundo XAMPP con PHP 8.1.17 |
| Puerto | 80 (único Apache corriendo) |
| URL local del proyecto | `http://menu.local/` |
| DocumentRoot | `C:/xampp81/htdocs/menu_qr_3d` (confirmar ruta exacta) |
| Vhost configurado en | `httpd-vhosts.conf` del XAMPP activo |
| Host en Windows | `127.0.0.1 menu.local` en `C:\Windows\System32\drivers\etc\hosts` |
| MySQL local | phpMyAdmin del XAMPP activo |
| PHP en producción (cPanel) | Verificar que sea 8.1+ también |

**Nota:** El XAMPP anterior (PHP 5.6) tiene el proyecto `senda` (Joomla) comentado en vhosts. No tocar ese XAMPP.

---

## 13. DECISIONES TÉCNICAS YA TOMADAS (NO CUESTIONAR)

- ❌ No Laravel → PHP nativo
- ❌ No Cloudflare R2 / S3 → almacenamiento local en servidor
- ❌ No render.com / Railway → solo cPanel propio
- ❌ No Three.js para el menú cliente → model-viewer de Google
- ❌ No A-Frame → model-viewer de Google
- ❌ No 8thWall → model-viewer nativo (WebXR + Quick Look)
- ✅ Vue 3 compilado localmente, dist subido por FTP
- ✅ Cron de cPanel para jobs async
- ✅ MySQL local del cPanel
- ✅ Meshy.ai como único proveedor de conversión 3D

---

## 13. REPOSITORIO Y HERRAMIENTAS DISPONIBLES EN CADA CHAT

### Repositorio GitHub
- **URL:** https://github.com/alexis-gd/menu_qr_3d
- **Visibilidad:** Público
- **Rama principal:** `master`
- El repo ya contiene los archivos de contexto (`CONTEXTO_PROYECTO.md` y `CONTEXTO_BASE_DE_DATOS.md`)
- Todo el código del proyecto se sube aquí conforme se desarrolla
- **Clonar:** `git clone https://github.com/alexis-gd/menu_qr_3d.git`

### Conectores / Herramientas activas en Claude
En cada chat nuevo Claude tiene acceso a los siguientes conectores que puede usar directamente:

| Herramienta | Para qué usarla en este proyecto |
|---|---|
| **GitHub** | Leer archivos del repo, crear/editar archivos, hacer commits, abrir PRs |
| **Filesystem** | Leer/escribir archivos locales del desarrollador (proyecto Vue, PHP local) |
| **Claude in Chrome** | Navegar y probar la app en el navegador, depurar errores visuales |

**Flujo de trabajo con GitHub:** Cuando se creen archivos nuevos (PHP, Vue, SQL, etc.) se suben directamente al repo via el conector de GitHub. No es necesario copiar y pegar manualmente.

**Flujo de trabajo con Filesystem:** Para acceder a archivos locales del proyecto en XAMPP o en la carpeta del proyecto Vue antes de subirlos.

---

## 14. SEGURIDAD — VARIABLES SENSIBLES Y GITIGNORE

### Regla absoluta
**Nunca subir al repo ningún archivo que contenga credenciales, API keys, contraseñas o datos de conexión.** El repo es público. Cualquier dato sensible que se suba queda expuesto permanentemente aunque después se borre (git guarda el historial).

### Dónde viven las variables sensibles (solo local y en el servidor)
Las credenciales existen únicamente en dos lugares físicos, nunca en el repo:

| Lugar | Archivo | ¿Se sube al repo? |
|---|---|---|
| Local XAMPP | `C:/xampp81/htdocs/menu_qr_3d/api/config.php` | ❌ Ignorado por .gitignore |
| Servidor cPanel | `/public_html/api/config.php` | ❌ Solo existe en el servidor |

### Qué contiene config.php (el archivo sensible — nunca al repo)
```php
<?php
// ESTE ARCHIVO NO SE SUBE AL REPO
define('DB_HOST',      'localhost');
define('DB_NAME',      'usuario_menudb');
define('DB_USER',      'usuario_db');
define('DB_PASS',      'password_real');
define('MESHY_API_KEY','msy_xxxxxxxxxxxxxxxx');
define('ADMIN_TOKEN',  'token_seguro_generado');
define('BASE_URL',     'https://tudominio.com');
define('UPLOADS_PATH', $_SERVER['DOCUMENT_ROOT'] . '/uploads/');
define('UPLOADS_URL',  BASE_URL . '/uploads/');
```

### Qué SÍ se sube al repo (plantilla sin valores)
El repo contiene `api/config.example.php` con los mismos campos pero vacíos. Al clonar o desplegar, se copia ese archivo, se renombra a `config.php` y se llenan los valores reales.

### Archivos protegidos por .gitignore
El `.gitignore` en la raíz del repo bloquea:
- `api/config.php` — credenciales de BD, API keys, tokens
- `api/.env` — por si en el futuro se migra a dotenv
- `uploads/` — archivos subidos por usuarios (fotos, modelos .glb)
- `node_modules/`
- `dist/` — el build de Vue se sube por FTP directo, no por git
- `.DS_Store`, `Thumbs.db`

**Regla para Claude:** Antes de crear o editar cualquier archivo PHP que contenga credenciales, verificar que ese archivo esté listado en `.gitignore`. Si no lo está, agregarlo antes de continuar.

---

## 15. NOTAS IMPORTANTES PARA FUTUROS CHATS

- El proyecto se llama `menu_qr_3d`
- El desarrollador maneja Vue.js, PHP nativo, JS. No necesita explicaciones básicas.
- Cuando se pida código, darlo completo y funcional, no pseudocódigo.
- Si hay duda sobre una decisión técnica, revisar sección 12 antes de proponer alternativas.
- Los archivos PHP NO usan namespaces ni autoload complejo. Código limpio y directo.
- Vue usa Composition API con `<script setup>`. No Options API.
- Para CSS en Vue: scoped styles dentro del componente. Sin frameworks CSS externos por ahora (puede cambiar).