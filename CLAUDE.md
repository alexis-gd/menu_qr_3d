# menu_qr_3d — Menú Digital 3D/AR para Restaurantes

## Contexto del proyecto

| Archivo | Contenido |
|---|---|
| `docs/context-map.md` | Mapa maestro: qué archivo actualizar según el tipo de cambio |
| `docs/estado-producto.md` | Fases completadas, pendientes, API endpoints, estados de productos |
| `docs/bd-schema.md` | Schema MySQL completo: CREATE TABLE, relaciones, índices |
| `docs/bd-queries.md` | Consultas clave del sistema |
| `docs/bd-reglas-negocio.md` | Reglas de negocio, PDO, mantenimiento |
| `docs/bd-migraciones.md` | Historial de migraciones, seed, BD demo |
| `docs/demos.md` | Sistema de demos: arquitectura, trial, slug, create_demo.php |
| `docs/deploy.md` | Deploy cPanel: paths, FTP, migraciones, proxy Vite, entornos |

## Reglas de desarrollo

| Regla | Archivo |
|---|---|
| Seguridad: config.php, prepared statements, HttpOnly | [.claude/rules/seguridad.md](.claude/rules/seguridad.md) |
| Convenciones PHP/Vue/fechas/Pinia/VueDatePicker | [.claude/rules/convenciones-codigo.md](.claude/rules/convenciones-codigo.md) |
| Migraciones: cuándo crearlas, nombrado, idempotencia | [.claude/rules/migraciones.md](.claude/rules/migraciones.md) |
| Versionado semántico: npm version antes de cada commit | [.claude/rules/versionado.md](.claude/rules/versionado.md) |

## Stack (no proponer alternativas)

| Capa | Tecnología |
|---|---|
| Backend | PHP nativo 8.1+, sin frameworks. PDO + prepared statements. |
| Frontend | Vue 3 + Vite, Composition API con `<script setup>`. Sin Options API. |
| 3D/AR | Google Model-Viewer web component. Sin Three.js, sin A-Frame. |
| Modelos 3D | Meshy.ai API → `.glb` en `/uploads/modelos/` (flujo semi-manual) |
| Servidor | cPanel propio. Sin Docker, sin servicios cloud externos. |
| BD | MySQL local de cPanel |
| Estado global | Pinia + pinia-plugin-persistedstate |

## Estructura de carpetas clave

```
api/
  config.php          ← LOCAL Y SERVIDOR SOLO, nunca al repo
  config.example.php  ← Esta sí va al repo (sin valores reales)
  index.php           ← Router + TODOS los endpoints (no hay carpeta routes/)
uploads/              ← fotos, logos, modelos (prod/local)
uploads_demos/        ← fotos, logos, modelos para APP_ENV=demo_local (separado para evitar colisión de IDs)
database/
  migrations/         ← faseN_*.sql — idempotentes, van al repo
  demos/              ← init_demo_db.sql, templates, migrate_to_prod.sql
scripts/
  create_demo.php     ← CLI para crear demo nueva (~2 min)
src/
  views/admin/Dashboard.vue     ← orquestador del panel admin
  views/MenuPublico.vue         ← vista del cliente
  components/menu/              ← ProductoCard, ProductoModal, CheckoutModal, etc.
  components/admin/tabs/        ← TabPlatillos, TabCategorias, TabApariencia, TabNegocio, TabPedidos
  stores/carrito.js             ← Pinia store con persistencia
  composables/useApi.js         ← fetch con credentials: include
.claude/
  rules/              ← reglas permanentes de código para Claude
  commands/           ← comportamiento de comandos especiales
```

## Comandos útiles

```bash
npm run dev        # Desarrollo Vue local
npm run build      # Build para subir a cPanel por FTP
php -v             # Verificar PHP local
```

## Git

- Rama principal: `master` — repo: https://github.com/alexis-gd/menu_qr_3d (público)
- `dist/` y `uploads/` NO van al repo (ver `.gitignore`)
- Commits: Conventional Commits en español, sin `Co-Authored-By`

---

## DECISIONES ARQUITECTÓNICAS — Razonamiento y Estado Actual

> Leer antes de cualquier refactor o feature nueva.
> Al completar una tarea relevante, agregar entrada al LOG al final de esta sección.

---

### TEMA CSS — Sistema de dos capas

**Decisión:** Estilos en dos capas con responsabilidades distintas.

**Capa 1 — `src/assets/theme.css`:** Variables fijas del sistema (tamaños de botones, espaciados, border-radius, tipografía, sombras). Se importa una vez en `main.js`.

**Capa 2 — Temas dinámicos:** 5 temas (`calido`, `oscuro`, `moderno`, `rapida`, `rosa`) vía clase CSS en `MenuPublico.vue`. Datos centralizados en `src/utils/themes.js` (fuente única).

**Archivos:** `src/assets/theme.css`, `src/utils/themes.js`, `src/main.js`, `src/assets/admin.css`

**Estado:** Implementado (2026-03-11).

---

### AUTENTICACIÓN — Cookies HttpOnly

**Decisión:** Token de admin en cookie HttpOnly, no en localStorage.

**Por qué:** localStorage es accesible por JS — un script XSS puede exfiltrarlo. Cookies HttpOnly no son accesibles desde JS. Repo público + proyecto a comercializarse = seguridad crítica.

**Flujo:** Login → PHP emite `Set-Cookie: token; HttpOnly; Secure; SameSite=Strict` → `helpers.php` lee `$_COOKIE['token']` → router guard llama `auth-check` una vez por carga (resultado cacheado).

**Archivos:** `api/index.php`, `api/helpers.php`, `src/composables/useApi.js`, `src/views/admin/Login.vue`, `src/views/admin/Dashboard.vue`, `src/router/index.js`

**Estado:** Implementado (2026-03-11).

---

### ARQUITECTURA DE COMPONENTES — Dashboard particionado

**Decisión:** `Dashboard.vue` dividido en componentes hijo por tab. Dashboard es solo orquestador (~170 líneas).

**Por qué:** Dashboard acumuló toda la lógica del admin. Separar por tab = ciclo de vida independiente, menos riesgo de romper otra sección.

```
src/components/admin/tabs/
  TabPlatillos.vue   TabCategorias.vue   TabApariencia.vue
  TabNegocio.vue     TabPedidos.vue
src/components/menu/
  ProductoCard.vue   ProductoModal.vue   PersonalizacionModal.vue
  ModelViewer3D.vue  CarritoFlotante.vue CheckoutModal.vue
```

Dashboard pasa props (`restauranteId`, `categorias`, `restaurante`, `menuUrl`, `active`) y recibe emits (`notif`, `categorias-changed`, `restaurante-updated`, `tema-preview`).

**Estado:** Implementado (2026-03-15).

---

### ESTADO GLOBAL — Pinia para el carrito

**Decisión:** Carrito en Pinia store con `pinia-plugin-persistedstate`.

**Por qué:** Estado local se pierde al destruirse el componente. Pinia persiste en localStorage y hace el carrito accesible desde cualquier componente sin prop drilling.

**Archivo:** `src/stores/carrito.js`

**Estado:** Implementado (2026-03-15).

---

### PERSONALIZACIÓN POR PASOS — Sistema genérico estilo Rappi/Uber Eats

**Decisión:** Productos con grupos de opciones configurables. Flujo paso a paso en bottom sheet modal.

**Reglas clave:**
- `precio` del producto es FIJO (base). Solo `opciones.precio_extra > 0` suma.
- Max de selecciones checkbox puede ser dinámico: depende de opción radio elegida → `max_dinamico_grupo_id` + `max_override`.
- Aviso de complemento: texto configurable + categoría destino. "Ver X" → cierra modal + scroll.
- Checkout/WA muestran opciones como snapshot (`pedido_item_opciones`).

**Tablas:** `producto_grupos`, `producto_opciones`, `pedido_item_opciones`

**Archivos:** `src/components/menu/PersonalizacionModal.vue`, `src/stores/carrito.js`, `database/migrations/fase7_personalizacion.sql`

**Estado:** Implementado (2026-03-15).

---

### SISTEMA DE DEMOS — BD separada + uploads separados

**Decisión:** BD demo (`nodosmxc_menu_demos`) completamente separada de prod. Uploads también separados.

**Por qué:** IDs de BD demo e IDs de BD prod son independientes (AUTO_INCREMENT separados). Si comparten el mismo filesystem, `logo_1_*.jpeg` de demo pisa al logo de Dolce Mare. Solución: `demo_local` usa `uploads_demos/` en local; en servidor `public_html/demos/menu/uploads/` ya es físicamente distinto de `public_html/menu/uploads/`.

**Archivos:** `api/config.php` (entorno `demo_local`), `uploads_demos/` (carpeta local), `scripts/create_demo.php`

**Estado:** Implementado (2026-04-20).

---

### INSTRUCCIÓN — Documentar cambios

Al completar una tarea relevante:
1. Agregar entrada en el LOG con formato: `- [FECHA] [TAREA] — Archivos: X, Y, Z. Notas.`
2. Si se tomó una decisión técnica nueva → agregarla como subsección arriba.

---

## LOG DE CAMBIOS ARQUITECTÓNICOS

- [2026-04-21] **Templates con imágenes reales + fix CORS QR + fase25** — Todos los templates SQL actualizados con imágenes reales en `uploads_demos/demos/` (carpeta plana, 1 imagen por tipo), `logo_url` y hash bcrypt real (`demo1234`). `create_demo.php` simplificado: ya no copia archivos, hereda rutas del template. `uploads_demos/.htaccess` con `Access-Control-Allow-Origin: *` — fix html2canvas al descargar PNG del QR. `fase25_pedidos_referencia.sql`: columna `referencia VARCHAR(150)` en pedidos (existía en queries pero faltaba migración formal). Archivos: `database/demos/template_*.sql`, `scripts/create_demo.php`, `uploads_demos/.htaccess`, `database/migrations/fase25_pedidos_referencia.sql`, `database/demos/init_demo_db.sql`, `db/init.sql`.

- [2026-04-20] **Registro histórico de demos creadas** — Nueva tabla `demo_registros` para llevar control operativo de demos creadas desde CLI aunque después expiren, se conviertan o se eliminen. `scripts/create_demo.php` inserta el registro automáticamente y se añadió `scripts/list_demos.php` para consultar el historial desde terminal. Archivos: `database/migrations/fase24_demo_registros.sql`, `database/demos/init_demo_db.sql`, `scripts/create_demo.php`, `scripts/list_demos.php`, `docs/demos.md`, `docs/bd-migraciones.md`.

- [2026-04-20] **Restructura de contextos + fix uploads_demos** — Sistema de contextos replicado del patrón wa-cloud-panel: `CONTEXT_MAP.md` (raíz) → `docs/context-map.md`; `CONTEXTO_BASE_DE_DATOS.md` (652 líneas) → 4 archivos modulares (`docs/bd-schema.md`, `docs/bd-queries.md`, `docs/bd-reglas-negocio.md`, `docs/bd-migraciones.md`); `CONTEXTO_PROYECTO.md` → `docs/estado-producto.md` (slim); creados `.claude/rules/` (seguridad, convenciones-codigo, migraciones, versionado) y `.claude/commands/actualiza-contextos.md`. Fix uploads: `demo_local` ahora usa `uploads_demos/` para evitar colisión de IDs entre `nodosmxc_menu_demos` y `nodosmxc_menu_qr_3d` en filesystem local. Archivos: `api/config.php`, `uploads_demos/`, todos los docs nuevos.

- [2026-03-30] **Push con logo del restaurante + cierre de bug QA Fase 17** — Push: `api/helpers.php` ahora consulta `restaurantes.logo_url` y lo envía como `icon`/`badge` en el payload; `src/sw.js` usa ese icono dinámico y hace fallback a `pwa-icon.svg` solo si no existe logo. QA: se confirmó que el error al cancelar pedidos no era móvil sino schema desfasado; faltaba `fase17_pedido_contada_recompensa.sql` en QA. Al ejecutar esa migración, la cancelación volvió a funcionar. Archivos: `api/helpers.php`, `src/sw.js`.

- [2026-03-30] **Fase 21 + Sistema de contexto** — Push/PWA: `vite.config.js` integra `vite-plugin-pwa` con `injectManifest`; nuevos `src/sw.js` y `public/pwa-icon.svg`; `index.html` añade meta tags iOS. Admin: `TabNegocio.vue` card de notificaciones push por dispositivo. API: `vapid-key`, `push-subscribe`, `push-unsubscribe`; `notify_new_order()` en `api/helpers.php` envía pushes VAPID. BD: `database/migrations/fase21_push_subscriptions.sql`. Reportes: `TabPedidos.vue` y `GET reportes` distinguen cupones de envío gratis.

- [2026-03-26] **UX Dashboard + Footer versionado** — Tab activa persistida en `localStorage('dashboard_tab')`. Body scroll lock en checkout. `CheckoutModal.restauranteId` → `default: null`. TabNegocio collapsible (5 cards). Footer con `__APP_VERSION__` desde `package.json`. Sistema de versionado Semver.

- [2026-03-26] **Eliminar polling interval + URL limpia sin `?r=`** — Polling 90s/120s removidos (causaban saltos de scroll). Solo `visibilitychange` queda. `cargandoInicio` para spinner solo en primera carga. `GET menu` slug opcional; `MenuPublico` hace `router.replace` eliminando `?r=`. `menuUrl` en Dashboard ya no incluye `?r=`.

- [2026-03-26] **Fase 20a — Popup tienda cerrada + modo lectura + pedidos programados** — BD: `fase20a_popup_tienda_cerrada.sql`. `TiendaCerradaPopup.vue` (bottom-sheet 2 pasos). `modoLectura` computed. Banner sticky programado. VueDatePicker para fecha/hora. Pitfall TDZ: declarar `modoLectura` DESPUÉS de `tiendaAbierta`.

- [2026-03-26] **Fase 19 — Folio único + Ajuste manual + Cupón envío gratis + Corte de ventas** — BD: 3 migraciones. Folio `YYYYMMDD-KBR4`. Ajuste manual post-venta. Cupón `envio_gratis`. TabReportes/TabPedidos fusionados. Endpoint `GET reportes`.

- [2026-03-26] **Fase 18 — Lógica descuentos, UX cuponera, reversión cancelaciones** — Recompensa y cupón mutuamente excluyentes. Cuponera rediseñada. Cancelación revierte `total_compras` solo si `contada_en_recompensas=1`.

- [2026-03-23] **Fase 16 — GA4 + Fixes checkout + Backfill clientes** — GA4 con `useAnalytics.js`. Preview bypass `?preview=debug2026`. `fase16_backfill_clientes.sql`.

- [2026-03-23] **Fixes prod + Fase 15 + Performance imágenes + UX** — `cache: 'no-store'` en useApi. Lightbox `LightboxImagen.vue`. Watermark circular. Thumbnails WebP 220px. `codigos_promo_habilitado`.

- [2026-03-23] **Fases 11-14 — Recompensas, Códigos promo, Stock, UX** — Tablas `recompensas_config`, `clientes`, `codigos_promo`. Cuponera de sellos. Validación stock en carrito y checkout. Botón "+" deshabilitado al límite.

- [2026-03-23] **Fase 10 — Estado productos + Estado tienda + Watermark** — `tienda_cerrada_manual`, `tienda_horarios JSON`. `TiendaCerradaView.vue`. Overlay Agotado/Próximamente.

- [2026-03-15] **Fases 7-9 — Personalización, MDI icons, Envío gratis, 3D, Cache-busting** — Ver entradas anteriores del LOG por detalle de cada fase.

- [2026-03-15] **Arquitectura — Dashboard particionado + Pinia + Reorganización components** — Dashboard 1721 → ~170 líneas. Pinia store carrito con persistencia. `src/components/` reorganizado en `menu/` y `admin/tabs/`.

- [2026-03-11] **Autenticación HttpOnly + Tema CSS dos capas** — Token migrado de localStorage a cookie HttpOnly. `theme.css` + `themes.js` como fuente única de temas.
