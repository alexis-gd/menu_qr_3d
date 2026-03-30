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
- [x] **Fase 7** — Personalización por pasos (estilo Rappi/Uber Eats): BD ✅, API ✅, Vue ✅ completo
- [x] **Fase 8** — Migración completa emojis → Material Design Icons (MDI): UI chrome + picker categorías
- [x] **Fase 9** — Envío gratis por monto mínimo, aviso sugerido inteligente (1 vez por carrito), visor 3D en PersonalizacionModal, CarritoFlotante siempre visible, Terminal a domicilio, separador entre platillos en WA, cache-busting .htaccess
- [x] **Fase 10** — Estado de productos (No disponible / Próximamente / Normal), estado de tienda (cerrada manual + horarios semanales), watermark de logo en fotos de productos, pantalla `TiendaCerradaView`
- [x] **Fase 11** — Sistema de recompensas por sellos: `recompensas_config`, `clientes`, cuponera en checkout, ciclos completados, descuento aplicable
- [x] **Fase 12** — Stock mínimo aviso configurable por restaurante (`stock_minimo_aviso`), badge "Últimas N" desactivable
- [x] **Fase 13** — Códigos de promotor: restaurante crea códigos con descuento fijo/%, cliente ingresa en checkout, validación en tiempo real, CRUD en TabNegocio
- [x] **Fase 14** — Descuentos guardados en pedidos (`descuento_recompensa`, `descuento_promo`, `codigo_promo`), visibles en TabPedidos
- [x] **Fase 19** — Folio no secuencial + Ajuste manual de pedido + Cupón envío gratis + Tab Reportes (corte de ventas)
- [x] **@vuepic/vue-datepicker v12** — Date pickers en TabReportes + time pickers en TabNegocio
- [x] **Fase 20a** — Popup tienda cerrada + modo lectura + pedidos programados
- [x] **Fase 21** — Notificaciones push para pedidos nuevos + PWA/service worker del panel admin

### Funcionalidades Implementadas
- [x] API endpoints: `menu`, `login`, `restaurantes`, `categorias`, `productos`, `mesas`, `upload-fotos`, `upload-glb`, `upload-logo`, `job-status`, `vapid-key`, `push-subscribe`, `push-unsubscribe`
- [x] CRUD completo de productos (create, read, update, delete lógico)
- [x] Subida de múltiples fotos por producto + actualiza `foto_principal` automáticamente
- [x] Subida manual de .glb validado por magic bytes (`glTF`) desde admin
- [x] Model-Viewer en modal con AR nativo (webxr + quick-look) cuando `tiene_ar = 1`
- [x] CRUD mesas por restaurante, QR generado en browser con lib `qrcode` (npm)
- [x] QR descargable como PNG; URL = `{origin}/menu/` (sin `?r=`). `?mesa=N` sigue funcionando.
- [x] Badge "Mesa X" en header del menú público cuando URL incluye `?mesa=`
- [x] **URL limpia — sin `?r=slug`** — `GET menu` en la API acepta request sin parámetro `restaurante`; si no viene, devuelve el primer restaurante activo (single-tenant). `MenuPublico` limpia `?r=` de la URL con `router.replace` tras la primera carga exitosa (preserva `?mesa=` y `?preview=`). Links viejos con `?r=rest1` siguen funcionando transparentemente. `menuUrl` en Dashboard ya genera la URL sin `?r=`.
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
- [x] **Personalización por pasos (Fase 7)** — Sistema genérico de grupos de opciones por producto estilo Rappi/Uber Eats. BD: `producto_grupos`, `producto_opciones`, `pedido_item_opciones` + columnas en `productos` (`tiene_personalizacion`, `aviso_complemento`, `aviso_categoria_id`). API: `GET/POST producto-grupos`, `menu` GET con grupos embebidos, `pedidos` POST/GET con opciones. Vue: `PersonalizacionModal.vue` (acordeón progresivo, radio auto-avanza al seleccionar, checkbox auto-avanza al alcanzar max, botón agregar muted/accent según validación, popup aviso complemento post-agregar), `carrito.js` con `opciones[]` y `precio_unitario`, `MenuPublico.vue` rutea a modal correcto según `tiene_personalizacion`, `CheckoutModal.vue` usa `carritoStore.items` directo (fix bug badge), `TabPlatillos.vue` con editor inline de grupos/opciones.
- [x] **Fix campo obligatorio** — API devuelve `obligatorio` (nombre real de columna en BD), no `requerido`. PersonalizacionModal usa `esRequerido(grupo)` que acepta ambos como fallback.
- [x] **Fase 8 — MDI icons** — Migración completa de emojis unicode a `@mdi/js`. Nuevo: `src/components/SvgIcon.vue` (wrapper genérico), `src/utils/iconosCategorias.js` (63 íconos en 6 grupos, `ICONOS_MDI`, `resolverIcono()`, `ICONO_GRUPOS`). Picker de categorías guarda nombre MDI en BD (ej: `"mdiPizza"`) en lugar de emoji unicode. Columna `categorias.emoji` renombrada a `icono VARCHAR(100)`. Todos los componentes admin y menú público migrados a SvgIcon. `btn-primary`/`btn-secondary` tienen `display: inline-flex; align-items: center` para alinear íconos con texto. Favicion: `public/favicon.svg` + `src/assets/base.css` (estilos base migrados de inline en index.html a import en main.js).
- [x] **Fix CheckoutModal — carritoLocal stale** — `const carritoLocal = carritoStore.items` capturaba referencia al array viejo tras `vaciar()`. Fix: usar `carritoStore.items` directo en template y JS. Además, form fields (nombre, telefono, dirección, etc.) se resetean en `confirmar()` exitoso para el próximo pedido.
- [x] **Fase 9 — Envío gratis por monto** — Nueva columna `pedidos_envio_gratis_desde DECIMAL NULL` en `restaurantes`. Toggle + campo en TabNegocio. CheckoutModal: `umbralGratis` computed, `envioEsGratis` computed (subtotal >= umbral), badge "¡Envío gratis!" en opción de entrega, "¡Gratis!" en totales, "Envio: GRATIS" en WA. Migración: `database/migrations/fase9_envio_gratis.sql`.
- [x] **Fase 9 — Aviso sugerido inteligente** — Lógica movida de PersonalizacionModal a MenuPublico. Reglas: no mostrar si producto es de la misma categoría sugerida, no mostrar si ya hay un producto de esa categoría en carrito, no mostrar si ya se mostró antes en esta sesión. Tracking via `let _avisosMostrados = new Set()` a nivel de módulo (evita problema de serialización JSON de Pinia). `carrito.js` expone `tieneCategoriaEnCarrito()`, `marcarAvisoMostrado()`, `avisoYaMostrado()`. `vaciar()` resetea el Set. `persist: { paths: ['items'] }` en lugar de `persist: true`.
- [x] **Fase 9 — 3D en PersonalizacionModal** — `ModelViewer3D` integrado en la sección visual del modal. Si `producto.tiene_ar` muestra el viewer 3D, si no muestra la foto. Hint "Toca para explorar en 3D / AR" con ícono refresh.
- [x] **Fase 9 — CarritoFlotante siempre visible** — Eliminado `v-if="carrito.length"`. Badge siempre muestra, copy cambia: "Ver pedido" con items, "Carrito vacío" sin items.
- [x] **Fase 9 — Terminal a domicilio** — Nueva columna `pedidos_terminal_activo TINYINT DEFAULT 0` en `restaurantes`. ENUM `metodo_pago` amplíado a `('efectivo','transferencia','terminal')`. Toggle en TabNegocio ("Terminal a domicilio"). CheckoutModal: opción 💳 "Terminal a domicilio" solo visible cuando `tipoEntrega === 'envio' && pedidosConfig.pedidos_terminal_activo`. Layout de métodos de pago cambiado de grid 2 col a filas horizontales (`.opciones-filas`). Seleccionar "recoger" resetea a "efectivo" si tenías "terminal". WA muestra "Terminal a domicilio". Migración: `database/migrations/fase9b_terminal_domicilio.sql`.
- [x] **Fase 9 — Separador entre platillos en WA** — `──────────` entre items del pedido en el mensaje de WhatsApp (no después del último). `flatMap((i, idx, arr)` con `const sep = idx < arr.length - 1 ? ['──────────'] : []`.
- [x] **Fase 9 — Cache-busting .htaccess** — `index.html` con `Cache-Control: no-cache`. Assets con hash (`*.js`, `*.css`) con `max-age=31536000, immutable`. Resuelve problema de prod sirviendo JS/CSS viejos.
- [x] **Fase 10 — Estados de productos** — `ProductoCard.vue` y `ProductoModal.vue` muestran overlay "Agotado" (stock=0, escala de grises) y badge "Próximamente" (disponible=0, color del tema). Botón "+" oculto cuando bloqueado. API `GET menu` ya no filtra `AND p.disponible=1`. Watermark: logo del restaurante (opacity 15%) centrado en fotos de ProductoCard cuando `logo_url` existe.
- [x] **Fase 10 — Estado de tienda** — `TiendaCerradaView.vue` (pantalla de cierre con SVG, horarios formateados). `MenuPublico.vue` usa `tienda_abierta` de la API. Toggle manual + horarios JSON semanales en `TabNegocio.vue`. PHP calcula `tienda_abierta` en cada `GET menu`.
- [x] **Fase 11 — Sistema de recompensas (sellos)** — `recompensas_config` y `clientes` en BD. Checkout: detecta automáticamente por teléfono (10 dígitos), muestra cuponera con sellos visuales (★), aplica descuento al completar ciclo. `TabNegocio.vue`: configura número de compras, descripción y tipo de descuento. Advertencia ⚠️ visible: cambiar reglas afecta todos los clientes inmediatamente.
- [x] **Fase 12 — Stock mínimo aviso configurable** — `restaurantes.stock_minimo_aviso SMALLINT DEFAULT 5`. Campo en TabNegocio. `ProductoCard` y `ProductoModal` usan prop `:stock-minimo-aviso`. Badge desactivable con umbral=0.
- [x] **Fase 13 — Códigos de promotor** — `codigos_promo` en BD. Restaurante crea códigos (fijo/%) en TabNegocio. Cliente los ingresa en checkout (debounce 600ms, validación en tiempo real con tick verde o cruz roja). API pública `validar-codigo-promo`. Contador de usos acumulado.
- [x] **Fase 14 — Descuentos en pedidos** — `descuento_recompensa`, `descuento_promo`, `codigo_promo` guardados en pedido. TabPedidos muestra chips "🎁 Recompensa: -$X" y "🏷️ CODIGO: -$Y".
- [x] **Fix "Quitar control de stock"** — PUT productos: PHP usa `array_key_exists($f, $body)` en lugar de `isset()` para aceptar valores NULL. Vue siempre incluye `payload.stock = f.stock` (incluso null). Antes ponía 0 en lugar de NULL.
- [x] **Fix cat-nav scroll activo** — Reemplazado IntersectionObserver por scroll listener directo con `CAT_OFFSET=130`. Flag `_ignoreScroll` evita conflicto con scroll programático al hacer click en pill.
- [x] **WebP + thumbnails** — PHP GD: `save_as_webp()` convierte fotos a WebP al subir. `save_thumb_webp()` genera miniatura 220px (reducida de 300px) prefijada con `thumb_`. `ProductoCard` usa `thumbSrc` computed con `srcset="thumb 1x, original 2x"` y fallback a original si 404. `decoding="async"` en todas las imágenes de producto. `content-visibility: auto` en `.producto-card` para skip de render fuera del viewport.
- [x] **Lightbox fullscreen** — `LightboxImagen.vue` componente reutilizable: Teleport a body, fondo negro, imagen centrada con zoom-in animado, cierre por click fuera / botón X / Esc, `touch-action: pinch-zoom` en móvil. Usado en `ProductoModal.vue` y `PersonalizacionModal.vue` al tocar la foto (cursor `zoom-in`).
- [x] **Watermark circular** — Logo del restaurante como `<img>` circular (`border-radius: 50%`, `object-fit: cover`) en top-left de la foto. Tamaños: 26px en cards, 34px en modales, 44px en lightbox. Opacity 45%. Prop `logoUrl` agregada a ProductoModal y PersonalizacionModal, pasada desde MenuPublico.
- [x] **Fase 15 — Toggle códigos de promotor** — `restaurantes.codigos_promo_habilitado TINYINT DEFAULT 1`. Toggle en header de la card "Códigos de promotor" en TabNegocio. CheckoutModal oculta el campo con `v-if="pedidosConfig.codigos_promo_habilitado"`. Migración: `fase15_codigos_promo_habilitado.sql`.
- [x] **Fase 21 — Push notifications de pedidos** — Admin: `TabNegocio.vue` agrega card "🔔 Notificaciones de pedidos" con detección de soporte, aviso especial para iOS instalado en pantalla de inicio, toggle por dispositivo/navegador y mensajes de estado. Frontend: `vite-plugin-pwa` con `injectManifest`, `src/sw.js` como service worker y `public/pwa-icon.svg` para instalar el panel como app. API: `GET vapid-key` expone la clave pública, `POST push-subscribe` guarda la suscripción y `POST push-unsubscribe` la elimina. Backend: `notify_new_order()` en `api/helpers.php` envía push silenciosamente al crear un pedido; `api/index.php` lo llama de forma defensiva con `function_exists()` para no romper deploys incompletos. Config: nuevas claves `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT` en `api/config*.php`. BD: nueva tabla `push_subscriptions`. Migración: `database/migrations/fase21_push_subscriptions.sql`.
- [x] **Reporte: cupones de envío gratis** — `GET reportes` ahora devuelve `cupones_envio_gratis` contando pedidos con `codigo_promo` y `descuento_promo = 0`, y `TabPedidos.vue` los muestra como card de resumen. En la lista de pedidos, un código promo con descuento cero se etiqueta como "Envío gratis" en vez de ocultarse.
- [x] **Validación stock en carrito y checkout** — `carrito.js`: `cantidadEnCarrito(id)` suma unidades en carrito; `agregar()` retorna `'ok'` o `'stock_agotado'`. Toast rojo en `MenuPublico` cuando se rechaza. Checkout: botón "+" deshabilitado al llegar al límite del stock.
- [x] **Refresco silencioso sin UX disruption** — `MenuPublico` y `TabPlatillos` recargan datos solo al volver al tab (`visibilitychange`). Los intervalos de 90s/120s se eliminaron porque causaban saltos de scroll (Vue destruía el DOM al activar `loading=true`). `TabPedidos` mantiene el intervalo de 30s (pedidos del admin requieren frescura) con save/restore de `window.scrollY` + `nextTick`. El spinner de carga solo aparece en la primera carga (`cargandoInicio` en MenuPublico; `loadingX && !items.length` en los tabs). Los refrescos posteriores son 100% silenciosos.
- [x] **Teléfono siempre requerido en checkout** — Campo teléfono visible siempre (no solo en envío a domicilio).
- [x] **Fix sw-track en Teleport** — El modal de edición de TabPlatillos usa `<Teleport to="body">` — `var(--accent)` no llegaba al overlay. Fix: prop `accent` en TabPlatillos, `--accent` aplicado como CSS var en el overlay teleportado. Dashboard pasa `:accent="temaAccent"`.
- [x] **Fix grupo-tipo selector** — Reemplazado `<select>` con emojis en `<option>` (HTML no permite SVG) por grupo de botones personalizados con SvgIcon (`.grupo-tipo-btns` / `.tipo-btn`). Fix alineación: `min-width: 0` en `.grupo-nombre-input`.

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
- ✅ **carritoLocal stale en CheckoutModal** — Corregido dos veces: (1) Era copia del prop → migrado a `carritoStore.items` directo. (2) `carritoStore.items` era alias `= carritoStore.items` que quedaba stale tras `vaciar()` (crea nuevo array). Fix final: `carritoStore.items` referenciado directamente en template y JS, sin alias local.
- ✅ **campo `obligatorio` vs `requerido` en grupos** — La columna BD se llama `obligatorio`, la API la devuelve como `obligatorio`. PersonalizacionModal usaba `grupo.requerido` (undefined). Fix: `esRequerido(grupo)` verifica ambos.
- ✅ **Validación editor inline grupos/opciones** — `guardarEdicionProducto` valida: nombre de grupo vacío, nombre de opción vacío, max_selecciones < 1 en checkbox, precio_extra negativo, precio del platillo negativo. Mensajes específicos por campo.
- ✅ **POST producto-grupos feedback granular** — `guardarEdicionProducto` separado en dos try-catch independientes. Si PUT básico falla → error claro, no guarda nada. Si POST grupos falla después de PUT exitoso → mensaje "Datos básicos guardados. Error al guardar personalización" + recarga lista.
- ✅ **Punto débil 1 (max_dinamico id↔índice)** — No era bug real: el servidor hace la conversión índice→ID en dos pasadas (insertar todos + UPDATE max_dinamico_grupo_id). Frontend envía índices correctamente.
- ✅ **Cancelación de pedidos en QA** — Resuelto. El problema no era móvil: en QA faltaba aplicar la migración `fase17_pedido_contada_recompensa.sql`, por eso fallaba el `SELECT` de `contada_en_recompensas` al cancelar. Tras correr Fase 17 en QA, la cancelación volvió a funcionar.

### Funcionalidades Pendientes
- [x] **Descarga QR fiel al preview** — html2canvas captura el DOM real de `.qr-card-dm` → PNG pixel-perfect. Selector Normal/Alta calidad (scale 2x/3x).
- [ ] **Mesas / QR por mesa** — `Mesas.vue` existe (completo con su QR card) pero está **inactivo**. El QR actual es uno solo por restaurante, gestionado desde Dashboard. Activar cuando se requiera multi-mesa con QR individual por mesa.
- [x] **Validación de formularios** — TabPlatillos: precio positivo, nombre grupo/opción, max_selecciones. TabNegocio: WhatsApp requerido si pedidos activos, CLABE 18 dígitos. TabApariencia: nombre del restaurante requerido.
- [x] **Feedback visual mejorado** — Toasts ya existían. TabCategorias: `guardando` ref en botón "Agregar" (evita doble envío). Resto de botones de guardar ya tenían estado de carga.
- [x] **Thumbnail de foto en admin** — Mostrar foto_principal en la tabla de productos del admin
- [ ] **Cron registrado en cPanel** — Script existe (`cron/check_meshy_jobs.php`) pero no está en scheduler. Freeze actual mientras Meshy siga en flujo no prioritario.
- [ ] **Meshy API key** — Aún en placeholder. Freeze actual: no se usará por ahora.

---

### Estados visuales de productos en el menú público (Fase 10)

| Estado | Condición BD | Visible | Compra | Display |
|--------|-------------|---------|--------|---------|
| **Normal** | `disponible=1`, `stock IS NULL` o `stock > 0` | ✓ | ✓ | Card normal |
| **Agotado** | `disponible=1`, `stock IS NOT NULL AND stock=0` | ✓ | ✗ | Overlay gris + texto "Agotado" |
| **Próximamente** | `disponible=0` (toggle admin "Inactivo") | ✓ | ✗ | Badge con color del tema |
| **Oculto** | `activo=0` (borrado lógico) | ✗ | ✗ | No aparece en el menú |

> API: `GET menu` ya NO filtra `AND p.disponible = 1` — devuelve todos los productos con `activo=1`.
> El filtro visual es 100% frontend (`ProductoCard.vue`, `ProductoModal.vue`, `CheckoutModal.vue`).

### Estado de tienda (Fase 10)

Campos en `restaurantes`:
- `tienda_cerrada_manual TINYINT(1)` — override manual para cerrar el menú
- `tienda_horarios JSON` — objeto semanal `{"lunes": {"activo": true, "apertura": "08:00", "cierre": "22:00"}, ...}`

`tienda_abierta` es **calculado en PHP** (no almacenado): `false` si `tienda_cerrada_manual=1` O si la hora actual está fuera del rango del día actual. `true` si no hay horarios configurados (NULL).

Cuando `tienda_abierta = false` en el menú público (Fase 20a): aparece `TiendaCerradaPopup.vue` (bottom-sheet overlay, 2 pasos) pero el menú sigue visible en **modo lectura** (sin carrito, sin botón "+"). El admin puede activar `pedidos_programar_activo` para mostrar el botón "Programar pedido" que lleva al paso 2 del popup. Al aceptar, el cliente entra en modo scheduling: carrito activo, checkout muestra picker de fecha/hora, pedido se guarda con `fecha_programada` y `hora_programada`.

`modoLectura` computed en MenuPublico: `(!tiendaAbierta && !pedidoProgramado) || !pedidosActivos`. CarritoFlotante y botones "+" se ocultan cuando `modoLectura=true`.

`TiendaCerradaView.vue` sigue en el proyecto pero ya no se usa (reemplazado por el popup).

**Pitfall VueDatePicker v-if**: Los pickers dentro de `v-if` causan `TypeError: Cannot read properties of null (reading 'parentNode')` al desmontarse mientras el dropdown está teleportado a `<body>`. Fix obligatorio: usar `v-show` en el contenedor de VueDatePicker.

**Fix aplicado (Fase 20a)**: En `@vuepic/vue-datepicker` v12, `:enable-time-picker="false"` no basta para ocultar el reloj del selector de fecha. El ajuste correcto es `:time-config="{ enableTimePicker: false }"`. Mantener este patrón en futuros pickers de solo fecha.

### Watermark automático (Fase 10)
Logo del restaurante superpuesto en fotos de productos con `opacity: 0.15`. Activo automáticamente cuando `restaurante.logo_url` existe. Sin toggle. Implementado como div CSS con `background-image` en `ProductoCard.vue`.

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

#### A5 — TabPlatillos modal + UX admin (Prioridad: Media — ✅ Implementado 2026-03-15)
- [x] **Editor de platillos como modal** — reemplaza el inline edit que se rompía en mobile. `<Teleport to="body">`, bottom sheet en mobile, header con thumbnail + nombre, footer sticky.
- [x] **Pills de categoría** — filtro local por categoría en la lista de platillos. `categoriaFiltro` ref + `productosFiltrados` computed.
- [x] **Guía colapsable de personalización** — dentro del modal, explica Única/Múltiple/Requerido/Controla máx de/Aviso sugerido con ejemplos.
- [x] **Validaciones completas admin** — TabPlatillos: precio, nombre grupo/opción, grupo vacío, max_selecciones. TabNegocio: WhatsApp, costo envío, CLABE 18 dígitos. TabApariencia: nombre restaurante. TabCategorias: loader en botón Agregar.

#### A4 — Estado global con Pinia (Prioridad: Media — ✅ Implementado 2026-03-15)
- [x] **Carrito migrado a Pinia store** con persistencia en localStorage
  - `src/stores/carrito.js` — `items`, `agregar(producto, obs, opciones[])`, `vaciar()`, `total()`; `precio_unitario` por item (base + extras); dedup solo para items sin opciones
  - `pinia-plugin-persistedstate` — carrito sobrevive recargas
  - `MenuPublico.vue` — usa `carritoStore.agregar()` y `carritoStore.vaciar()`
- [ ] **Store de restaurante activo en admin** — pendiente si se necesita multi-restaurante
- Estado actual: **freeze**. No implementar mientras el proyecto siga operando como single-tenant/sin multi-restaurante.

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
- ✅ Fase 7 BD aplicada: tablas `producto_grupos`, `producto_opciones`, `pedido_item_opciones` + columnas en `productos`
- ✅ Fase 7 Vue: PersonalizacionModal funciona con datos de prueba (Poke Bowl Hawaiiano, cat 3, Dolce Mare)
- ✅ Fase 21 local: service worker compila, endpoints `vapid-key/push-subscribe/push-unsubscribe` existen y el alta de pedidos ya no truena si falta el helper de push en un deploy parcial
- ⚠️ Meshy API sin key (freeze actual; no aplica al flujo semi-manual actual)
- ⚠️ Cron no registrado en cPanel (no necesario para flujo semi-manual)

### Estado QA
- ✅ QA operativo hasta Fase 21
- ✅ Push funcionando en QA
- ℹ️ La cancelación de pedidos en QA quedó resuelta al migrar Fase 17 (`contada_en_recompensas`)

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
│   │   │   ├── ProductoModal.vue        ← Modal simple (sin personalización)
│   │   │   ├── PersonalizacionModal.vue ← Bottom sheet por pasos (Fase 7)
│   │   │   ├── ModelViewer3D.vue        ← Wrapper del web component
│   │   │   ├── CarritoFlotante.vue
│   │   │   └── CheckoutModal.vue
│   │   └── admin/
│   │       └── tabs/              ← Tabs del panel admin
│   │           ├── TabPlatillos.vue
│   │           ├── TabCategorias.vue
│   │           ├── TabApariencia.vue
│   │           ├── TabNegocio.vue      ← time pickers @vuepic/vue-datepicker para horarios
│   │           ├── TabPedidos.vue      ← ajuste manual inline (Fase 19)
│   │           └── TabReportes.vue     ← corte de ventas (Fase 19, nuevo)
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
| GET | `/api/?route=reportes&restaurante_id={id}&desde=YYYY-MM-DD&hasta=YYYY-MM-DD` | Corte de ventas: resumen + por_dia | Sí |
| PUT | `/api/?route=pedidos&id={id}` | Actualizar status **o** aplicar ajuste_manual+ajuste_nota | Sí |

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
