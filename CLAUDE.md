# menu_qr_3d — Menú Digital 3D/AR para Restaurantes

## Contexto completo del proyecto
Lee estos archivos antes de cualquier tarea compleja:
- `CONTEXT_MAP.md` — que contexto existe, para que sirve cada archivo y como se enlazan
- `CONTEXTO_PROYECTO.md` — arquitectura, stack, flujos, fases, decisiones técnicas
- `CONTEXTO_BASE_DE_DATOS.md` — esquema MySQL completo, consultas clave, reglas de negocio

## Stack (no proponer alternativas)
- Backend: **PHP nativo 8.1+**, sin frameworks. PDO con prepared statements siempre.
- Frontend: **Vue 3 + Vite**, Composition API con `<script setup>`. Sin Options API.
- 3D/AR: **Google Model-Viewer** web component. Sin Three.js, sin A-Frame.
- Modelos 3D: generados por **Meshy.ai API** → archivos `.glb` guardados en `/uploads/modelos/`
- Servidor: **cPanel** propio. Sin Docker, sin servicios cloud externos.
- DB: **MySQL** local de cPanel.

## Reglas críticas de desarrollo

### Seguridad — NUNCA ignorar
- `api/config.php` contiene credenciales reales → **nunca subir al repo**
- Está en `.gitignore`. Verificar antes de cualquier commit que lo involucre.
- El repo es **público**. Cualquier credencial expuesta es un problema permanente.
- Para configurar localmente: copiar `api/config.example.php` → `api/config.php`

### Migraciones de BD — OBLIGATORIO
- **Toda** tabla nueva, columna nueva o cambio de esquema requiere un archivo SQL en `database/migrations/` ANTES de escribir código PHP/Vue que lo use.
- Nombrado: `faseN_descripcion.sql` (ej: `fase11_recompensas_referidos.sql`). Usar `IF NOT EXISTS` y `IF EXISTS` para que sean idempotentes.
- El archivo va al repo. El admin lo ejecuta manualmente en local → QA → prod en ese orden.
- **Nunca** aplicar cambios de esquema directamente en QA/prod sin el archivo de migración correspondiente.

### PHP
- Sin namespaces, sin autoload complejo. Código limpio y directo.
- Siempre prepared statements con parámetros nombrados (`:param`), nunca interpolación en SQL.
- Borrado siempre lógico (`activo = 0`), nunca DELETE en producción.
- Rutas relativas en DB para modelos y fotos. URL absoluta se construye en PHP con `BASE_URL`.

### Vue / Vite
- `base` en `vite.config.js` debe coincidir con la ruta del servidor: `/menu/` o `/`
- Imágenes de UI estática → carpeta `public/imgs/` → referenciar como `/menu/imgs/archivo.png`
- Imágenes de productos → siempre URLs absolutas desde la API, nunca imports de módulo
- `.htaccess` en la carpeta dist para Vue Router modo history
- **Fechas locales — OBLIGATORIO:** Nunca usar `new Date().toISOString().slice(0,10)` para obtener la fecha actual. `toISOString()` devuelve UTC y en México (UTC-6) da el día siguiente después de las 6 PM. Usar siempre `localIso()`:
  ```js
  const localIso = (d = new Date()) =>
    `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`
  ```
  Aplica a: cualquier fecha `YYYY-MM-DD` calculada en el frontend (reportes, filtros, rangos de fecha).

### Git
- Rama principal: `master`
- Repo: https://github.com/alexis-gd/menu_qr_3d
- `dist/` y `uploads/` no se suben al repo (ver `.gitignore`)

### Versionado — OBLIGATORIO antes de cada commit
El proyecto usa **Semantic Versioning** (`MAJOR.MINOR.PATCH`) gestionado en `package.json`.
La versión se inyecta en el build vía `vite.config.js` → `__APP_VERSION__` y se muestra en el footer del menú público (`MenuPublico.vue`).

**Regla por tipo de commit:**
- `feat:` → `npm version minor` (1.X.0) — nueva funcionalidad
- `fix:` / `chore:` / `style:` / `refactor:` → `npm version patch` (1.1.X)
- Cambio disruptivo (raro) → `npm version major` (X.0.0)

**Flujo obligatorio:**
1. Terminar los cambios de código
2. Correr `npm version patch|minor|major` — edita `package.json` automáticamente
3. Hacer el commit con el mensaje convencional

Claude Code debe correr el `npm version` adecuado como parte de cada sesión de commit, sin que el usuario tenga que pedirlo.

## Estructura de carpetas clave
```
api/
  config.php          ← LOCAL Y SERVIDOR SOLO, nunca al repo
  config.example.php  ← Esta sí va al repo (sin valores reales)
  index.php           ← Router + TODOS los endpoints (no hay carpeta routes/)
uploads/
  fotos/              ← Fotos de productos por producto_id
  modelos/            ← Archivos .glb descargados de Meshy
cron/
  check_meshy_jobs.php ← Cron cada 2 min en cPanel
```

## Comando especial
`actualiza contextos` = ejecutar este flujo:
1. Leer `CONTEXT_MAP.md`
2. Revisar los cambios recientes del proyecto antes de editar nada
3. Decidir que archivos toca actualizar segun el tipo de cambio
4. Actualizar solo los contextos necesarios entre:
   - `CLAUDE.md`
   - `CONTEXTO_PROYECTO.md`
   - `CONTEXTO_BASE_DE_DATOS.md`
   - `MEMORY.md` (en `~/.claude/projects/.../memory/`)
5. Corregir contradicciones obvias entre contextos si existen

Regla:
- no actualizar todos por inercia
- si el cambio fue arquitectonico, actualizar `CLAUDE.md`
- si fue funcional/producto, actualizar `CONTEXTO_PROYECTO.md`
- si fue schema o SQL, actualizar `CONTEXTO_BASE_DE_DATOS.md`
- si fue pitfall, bug raro o diferencia de entorno, actualizar `MEMORY.md`

## Comandos útiles
```bash
# Desarrollo Vue local
npm run dev

# Build para subir a cPanel por FTP
npm run build

# Verificar PHP local
php -v
```

## DECISIONES ARQUITECTÓNICAS — Razonamiento y Estado Actual

> Esta sección documenta el POR QUÉ de las decisiones técnicas tomadas.
> Claude Code debe leer esto antes de cualquier tarea de refactor o feature nueva.
> Al terminar cada tarea relevante, actualizar el LOG DE CAMBIOS al final de esta sección.

---

### TEMA CSS — Sistema de dos capas

**Decisión:** El proyecto usa un sistema de estilos en dos capas separadas con responsabilidades distintas.

**Capa 1 — `src/assets/theme.css` (valores fijos del sistema):**
Variables CSS que no cambian por cliente ni por tema: tamaños de botones, espaciados, border-radius, tipografía base, sombras. Se importa una vez en `main.js`. Resuelve la inconsistencia histórica donde cada componente definía sus propios tamaños de botón y espaciados, resultando en variaciones sutiles de padding y fuentes entre vistas.

**Capa 2 — Temas dinámicos del cliente (valores que sí cambian):**
Los 5 temas (`calido`, `oscuro`, `moderno`, `rapida`, `rosa`) se aplican vía clase CSS (`:class="\`tema-${tema}\`"`) en `MenuPublico.vue`. Los colores vienen de la API (campo `tema` del restaurante en BD). El admin aplica `--accent` vía `:style`. Los datos de los temas (objetos JS) se centralizan en `src/utils/themes.js` como fuente única de verdad, importada por Dashboard.vue.

**Archivos involucrados:** `src/assets/theme.css` (nuevo), `src/utils/themes.js` (nuevo), `src/main.js`, `src/views/admin/Dashboard.vue`, `src/components/ProductoCard.vue`, `src/components/ProductoModal.vue`, `src/components/CheckoutModal.vue`

**Estado:** Implementado (2026-03-11).

---

### AUTENTICACIÓN — Migración de localStorage a cookies HttpOnly

**Decisión:** El token de admin se mueve de `localStorage` a cookie HttpOnly.

**Por qué:** `localStorage` es accesible desde cualquier JS en la página — un script XSS puede leer y exfiltrar el token. Las cookies HttpOnly no son accesibles desde JS: el browser las gestiona y las envía automáticamente en cada request. El repo es público y el proyecto se comercializará, por lo que la seguridad del panel admin es crítica.

**Cambio de flujo:** Login → PHP hace `Set-Cookie: token=...; HttpOnly; Secure; SameSite=Strict` → `useApi.js` ya no necesita leer ni enviar el token manualmente → `helpers.php` lee el token de `$_COOKIE['token']` en lugar de headers → el guard del router verifica existencia de cookie vía endpoint de validación en lugar de leer localStorage.

**Archivos involucrados:** `api/index.php` (login + nuevos endpoints `logout`/`auth-check`), `api/helpers.php` (cookie helpers + `require_auth`), `src/composables/useApi.js`, `src/views/admin/Login.vue`, `src/views/admin/Dashboard.vue` (logout + uploads), `src/router/index.js` (guard async + `resetAuth`)

**Notas de implementación:**
- Router guard cachea el resultado en `authenticated` (módulo-level) — solo hace 1 llamada a `/api/?route=auth-check` por carga de página, no en cada navegación interna.
- Cookie `Secure` se activa automáticamente si `$_SERVER['HTTPS']` está presente — funciona en local (HTTP) y en producción (HTTPS) sin cambio de config.
- `useApi.js` mantiene el parámetro `includeAuth` por compatibilidad con call sites existentes pero ya no tiene efecto.

**Estado:** Implementado (2026-03-11).

---

### ARQUITECTURA DE COMPONENTES — Por qué se partió Dashboard.vue

**Decisión:** `Dashboard.vue` se dividió en componentes hijo por cada tab del panel.

**Por qué:** Dashboard.vue acumuló toda la lógica del admin conforme se agregaban fases (platillos, categorías, apariencia, negocio, pedidos). Un archivo con múltiples responsabilidades hace que cualquier cambio en una sección requiera entender todo el archivo, aumenta el riesgo de romper otra sección y hace el código difícil de mantener. Separar por tab permite que cada sección tenga su propio ciclo de vida, sus propios datos y sea cargada solo cuando se necesita.

**Estructura resultante:**
```
src/components/admin/tabs/
  TabPlatillos.vue    ← CRUD de productos, subida de fotos y .glb
  TabCategorias.vue   ← CRUD de categorías
  TabApariencia.vue   ← Selección de tema, logo, configuración visual
  TabNegocio.vue      ← WhatsApp, pedidos, transferencia, compartir menú
  TabPedidos.vue      ← Lista de pedidos con auto-refresh
```

`Dashboard.vue` queda como orquestador: maneja qué tab está activa, tiene el `restaurante_id` activo y lo pasa como prop a cada tab. Cada tab emite eventos cuando necesita refrescar datos del padre.

**Archivos involucrados:** `src/views/admin/Dashboard.vue` (reducido), nuevos archivos en `src/components/admin/tabs/`

**Estado:** Implementado (2026-03-15).

---

### COMPONENTES — Reorganización de src/components/

**Decisión:** Los componentes se organizan por dominio, no en carpeta plana.

**Por qué:** Con 5+ componentes del menú público y 5+ del admin mezclados en la misma carpeta, el proyecto se vuelve difícil de navegar conforme crece. La separación por dominio hace evidente a qué parte del sistema pertenece cada archivo.

**Estructura resultante:**
```
src/components/
  menu/
    ProductoCard.vue
    ProductoModal.vue
    ModelViewer3D.vue
    CarritoFlotante.vue
    CheckoutModal.vue
  admin/
    tabs/   ← ver sección anterior
```

**Estado:** Implementado (2026-03-15).

---

### ESTADO GLOBAL — Pinia para el carrito

**Decisión:** El carrito migra de `ref([])` local en `MenuPublico.vue` a un Pinia store.

**Por qué:** El carrito como estado local en el componente significa que si el componente se destruye (navegación, recarga), el carrito se pierde. Pinia centraliza el estado y con el plugin `pinia-plugin-persistedstate` se persiste automáticamente en localStorage. Adicionalmente, si en el futuro el carrito necesita ser accedido desde otro componente (por ejemplo un header con badge de cantidad), ya está disponible sin prop drilling.

**Archivo nuevo:** `src/stores/carrito.js`

**Estado:** Implementado (2026-03-15).

---

### PERSONALIZACIÓN POR PASOS — Sistema genérico estilo Rappi/Uber Eats

**Decisión:** Los productos pueden tener grupos de opciones configurables por el admin. El flujo de selección es paso a paso en un bottom sheet modal, no en el ProductoModal existente.

**Por qué:** Productos como poke bowls, hamburguesas o pizzas requieren que el cliente elija ingredientes, tamaños y extras antes de agregar al carrito. El sistema es genérico: cualquier producto puede tenerlo o no (`tiene_personalizacion` flag). Los productos simples siguen usando `ProductoModal.vue` sin cambios.

**Reglas clave:**
- `precio` del producto es FIJO (base). Solo `opciones.precio_extra > 0` suma al total.
- El max de selecciones de un grupo checkbox puede ser dinámico: depende de la opción elegida en otro grupo radio (ej: Tamaño Mediano → max 5 ingredientes). Se resuelve via `max_dinamico_grupo_id` + `max_override` en opciones.
- Aviso de complemento (bebida, etc.) es texto configurable + categoría destino. El cliente toca "Ver X" → se cierra el modal y el menú hace scroll a esa categoría.
- El checkout y WhatsApp muestran las opciones seleccionadas como snapshot (nombres + precio_extra guardados en `pedido_item_opciones`).

**Estado BD y API:** ✅ Implementado (2026-03-15).
**Estado Vue Chat 2:** ✅ Implementado (2026-03-15):
- `src/components/menu/PersonalizacionModal.vue` — bottom sheet completo (CREADO)
- `src/components/menu/ProductoModal.vue` — fix botón X: `.close-sticky` wrapper `position:sticky; margin-bottom:-56px`
- `src/stores/carrito.js` — `agregar(producto, obs, opciones)`, `precio_unitario`, dedup solo sin opciones
**Estado Vue Chat 3:** ✅ Implementado (2026-03-15):
- `src/views/MenuPublico.vue` — `abrirModal` + `onCardAgregar` ruteán a PersonalizacionModal o ProductoModal; `onIrCategoria` cierra modal + scroll
- `src/components/menu/CheckoutModal.vue` — chips opciones, subtotal con `precio_unitario`, WA flatMap, POST incluye `opciones[]`
**Estado Vue Chat 4:** ✅ Implementado (2026-03-15):
- `src/components/admin/tabs/TabPlatillos.vue` — editor inline de grupos/opciones; iniciarEdicion async; guardarEdicionProducto llama PUT productos + POST producto-grupos; max_dinamico convertido id↔índice

**Archivos BD:** `database/migrations/fase7_personalizacion.sql`
**Tablas nuevas:** `producto_grupos`, `producto_opciones`, `pedido_item_opciones`
**Columnas nuevas en `productos`:** `tiene_personalizacion`, `aviso_complemento`, `aviso_categoria_id`
**Endpoints nuevos:** `GET/POST producto-grupos`

---

### INSTRUCCIÓN PARA CLAUDE CODE — Documentar cambios

Cada vez que Claude Code complete una tarea de las listadas arriba, debe:

1. Agregar una entrada en el **LOG DE CAMBIOS** al final de esta sección con formato:
   ```
   - [FECHA] [TAREA] — Archivos creados/modificados: X, Y, Z. Notas relevantes.
   ```
2. Actualizar el estado de la tarea correspondiente de `Pendiente` a `Implementado`.
3. Si durante la implementación se tomó una decisión técnica no documentada aquí (por ejemplo, elegir una forma específica de pasar props entre componentes), agregarla como subsección nueva con el mismo formato: Decisión → Por qué → Archivos involucrados → Estado.

**El objetivo es que cualquier chat futuro entienda el estado del proyecto leyendo solo este archivo, sin necesidad de leer el código.**

---

## LOG DE CAMBIOS ARQUITECTÓNICOS

- [2026-03-30] **Push con logo del restaurante + cierre de bug QA Fase 17** — Push: `api/helpers.php` ahora consulta `restaurantes.logo_url` y lo envía como `icon`/`badge` en el payload; `src/sw.js` usa ese icono dinámico y hace fallback a `pwa-icon.svg` solo si no existe logo. QA: se confirmó que el error al cancelar pedidos no era móvil sino schema desfasado; faltaba `fase17_pedido_contada_recompensa.sql` en QA. Al ejecutar esa migración, la cancelación volvió a funcionar. Archivos: `api/helpers.php`, `src/sw.js`, `CONTEXTO_PROYECTO.md`.

- [2026-03-30] **Fase 21 + Sistema de contexto** — Contexto: nuevo `CONTEXT_MAP.md` como mapa maestro que explica para qué sirve `CLAUDE.md`, `CONTEXTO_PROYECTO.md`, `CONTEXTO_BASE_DE_DATOS.md` y `MEMORY.md`, y redefine el comando `actualiza contextos` para actualizar solo lo necesario. Push/PWA: `vite.config.js` integra `vite-plugin-pwa` con `injectManifest`; nuevos `src/sw.js` y `public/pwa-icon.svg`; `index.html` añade meta tags iOS. Admin: `TabNegocio.vue` incorpora card de notificaciones push por dispositivo con detección de soporte, estado de suscripción e instrucciones para iOS instalado. API: `vapid-key`, `push-subscribe`, `push-unsubscribe`; `notify_new_order()` en `api/helpers.php` envía pushes usando VAPID y `Minishlink/WebPush`; `api/index.php` hace la llamada de forma defensiva para no romper deploys parciales. BD: migración `database/migrations/fase21_push_subscriptions.sql`. Reportes: `TabPedidos.vue` y `GET reportes` distinguen cupones de envío gratis. Archivos: `CONTEXT_MAP.md`, `CLAUDE.md`, `CONTEXTO_PROYECTO.md`, `CONTEXTO_BASE_DE_DATOS.md`, `api/index.php`, `api/helpers.php`, `api/config.example.php`, `src/components/admin/tabs/TabNegocio.vue`, `src/components/admin/tabs/TabPedidos.vue`, `vite.config.js`, `src/sw.js`, `index.html`.

- [2026-03-26] **UX Dashboard + Footer versionado** — Sin cambios de BD ni API. (1) **Tab activa persistida**: `tabActivo` en Dashboard lee/escribe `localStorage('dashboard_tab')` — el admin recarga y queda en la misma pestaña. Fix derivado: `TabPedidos` y `TabApariencia` añaden `{ immediate: true }` a sus watchers de `props.active` — antes no cargaban datos si `active` ya era `true` desde el inicio (tab persistida). (2) **Body scroll lock**: `watch(mostrarCheckout)` en `MenuPublico.vue` pone `document.body.style.overflow = 'hidden'` al abrir checkout, lo limpia al cerrar y en `onUnmounted`. (3) **Fix warning prop**: `CheckoutModal.restauranteId` cambiado de `required: true` a `default: null` — el componente renderiza con `v-show` antes de que la API devuelva el restaurante. (4) **TabNegocio collapsible**: 5 cards con `.card-header.collapsible` + chevron ▲▼; solo "Estado de la tienda" abre por defecto (`secTienda = ref(true)`). (5) **Footer con versión**: `vite.config.js` inyecta `__APP_VERSION__` desde `package.json` → `MenuPublico.vue` muestra "Hecho con ❤ en Las Choapas" y "Versión X.X.X" al final del footer. (6) **Sistema de versionado**: Semver en `package.json`. `feat:` → minor, `fix:/chore:/refactor:` → patch. Claude corre `npm version patch|minor` antes de cada sesión de commits. **Archivos**: `Dashboard.vue`, `MenuPublico.vue`, `CheckoutModal.vue`, `TabPedidos.vue`, `TabApariencia.vue`, `TabNegocio.vue`, `vite.config.js`, `CLAUDE.md`.

- [2026-03-26] **Eliminar polling interval + URL limpia sin `?r=`** — Sin cambios de BD. (1) **Polling eliminado**: `_menuPollTimer` (90s en MenuPublico) y `_platillosPollTimer` (120s en TabPlatillos) removidos — causaban saltos de scroll porque `loading=true` destruía el DOM con el `v-if="loading"`. Se conserva solo `visibilitychange` (recarga al volver al tab). `TabPedidos` mantiene intervalo 30s pero agrega save/restore de `window.scrollY` + `nextTick`. (2) **Spinner solo en primera carga**: `MenuPublico` usa `cargandoInicio = ref(true)` en lugar del `loading` de useApi para el full-page spinner — set a `false` tras primer load exitoso. `TabPlatillos` y `TabPedidos` usan `v-if="loadingX && !items.length"` — el spinner no aparece en refrescos posteriores. (3) **URL limpia**: `GET menu` en API hace slug opcional — sin `?restaurante=`, devuelve primer restaurante activo (single-tenant). `MenuPublico` llama a la API con o sin slug, y tras carga exitosa hace `router.replace` eliminando `?r=` (preserva `?mesa=` y `?preview=`). `menuUrl` en Dashboard ya no incluye `?r=`. Links viejos con `?r=rest1` siguen funcionando. **Archivos**: `MenuPublico.vue`, `TabPlatillos.vue`, `TabPedidos.vue`, `api/index.php`, `Dashboard.vue`.

- [2026-03-26] **Fixes UX + fusión TabReportes en TabPedidos + bug timezone** — (1) `CheckoutModal.vue`: 409 `stock_agotado` ahora muestra `errorMsg` dentro del modal con nombre del producto — antes el toast (z-index 500) quedaba oculto detrás del modal (z-index 600); toast subido a 700. (2) `TabReportes` fusionado en `TabPedidos` como sección colapsable (`card-header.collapsible` + `▲▼`, patrón de TabPlatillos). Tab "Reportes" eliminado del nav. Carga automática de "Hoy" al abrir el colapsable por primera vez. (3) **Bug timezone**: `toISOString()` devuelve UTC — a las 7 PM en México ya es día siguiente en UTC. Corregido con `localIso()` en TabPedidos y TabReportes. **Regla permanente**: ver sección Vue/Vite de este archivo.

- [2026-03-26] **CheckoutModal — UX rediseño + fixes + copy WhatsApp** — Sin cambios de BD ni API. `CheckoutModal.vue`: (1) **VueDatePicker fix**: `:enable-time-picker="false"` (prop directo) no funciona en v12 — reemplazado por `:time-config="{ enableTimePicker: false }"` para eliminar el ícono del reloj del calendario de fecha programada. (2) **Banner pedido programado dentro del checkout**: `<div v-if="pedidoProgramado" class="banner-programado">` entre el header y el body — igual al del menú (fondo azul, texto blanco), sin botón de cerrar. (3) **Secciones como tarjetas**: `.checkout-body` con `background: #f4f4f6` y `gap: 8px`; cada `.checkout-section` pasa a `background: #fff; border-radius: 12px; padding: 12px; box-shadow`; `.section-title` en color `--accent` con `border-bottom`. (4) **Layout item 2 filas**: fila superior `[− cant +] [nombre] [precio]` con `.item-top`; fila inferior chips + observación a todo el ancho con `.item-bottom`. (5) **Fix chips overflow**: `.chip-opcion` cambiado de `white-space: nowrap` a `white-space: normal; word-break: break-word`. (6) **Fix observación**: `.item-obs-input` con `width: 100%; box-sizing: border-box`. (7) **Copy botón confirmar**: `'✓ Confirmar pedido por WhatsApp'` → `'📲 Abrir WhatsApp y enviar →'`; estado enviando: `'Abriendo WhatsApp...'`; hint debajo del botón: `"Solo da clic en 'Enviar' en WhatsApp para completar tu pedido"` — reduce abandono de usuarios que no enviaban el mensaje.

- [2026-03-26] **Fase 20a — Popup tienda cerrada + modo lectura + pedidos programados** — BD: `fase20a_popup_tienda_cerrada.sql` — `pedidos_programar_activo TINYINT(1)` en `restaurantes`; `fecha_programada DATE`, `hora_programada TIME` en `pedidos`. API: `menu` GET, `restaurantes` GET/PUT, `pedidos` GET/POST actualizados. Nuevo: `TiendaCerradaPopup.vue` (bottom-sheet 2 pasos: horario + explicación programar, Teleport to body). `MenuPublico.vue`: eliminado bloque `v-if="!tiendaAbierta"` → menú siempre visible; `modoLectura` computed (`(!tiendaAbierta && !pedidoProgramado) || !pedidosActivos`); `popupVisible` ref + watch `tiendaAbierta`; `cargarMenu` activa popup en primera carga si cerrado; banner sticky `.banner-programado` cuando `pedidoProgramado`; CarritoFlotante usa `!modoLectura` en lugar de `tiendaAbierta`. `ProductoCard`/`ProductoModal`/`PersonalizacionModal`: prop `modoLectura` oculta CTA de carrito. `CheckoutModal`: prop `pedidoProgramado` + sección fecha/hora (VueDatePicker), `fecha_programada`/`hora_programada` en POST y en mensaje WA. `TabNegocio`: toggle `pedidos_programar_activo` debajo de "Cerrar tienda ahora"; `auto-apply` añadido a time pickers de horarios (fix: cambios se descartaban sin click "Apply"). `TabPedidos`: clase `.pedido-card--programado` (borde izquierdo azul `#6C8EBF`) + chip `📅 Prog.` cuando `fecha_programada != null`. **Pitfall TDZ**: `modoLectura` y `watch(tiendaAbierta)` deben declararse DESPUÉS de `tiendaAbierta` computed en el script — `const` tiene temporal dead zone. **`?preview=debug2026`** fuerza `tiendaAbierta=true` en el computed — quitarlo de la URL para probar horarios reales.

- [2026-03-26] **Datepicker @vuepic/vue-datepicker v12** — Instalado. Import correcto: `import { VueDatePicker } from '@vuepic/vue-datepicker'` (named, NO default). `locale` y `format` string causan error en v12 con `getMonths` — usar `:format` como función JS con `toLocaleDateString`. En modo `range`, la hora se cuela aunque se ponga `:enable-time-picker="false"` porque el picker está teleportado → solución: dos pickers separados (uno para desde, otro para hasta). CSS del tema en `src/assets/datepicker-theme.css`, importado en `main.js` después del CSS del propio datepicker. Time picker para horarios en TabNegocio: convierte strings `"HH:MM"` ↔ objetos `{ hours, minutes }` con `strToTime()`/`timeToStr()`. TabReportes: "Hasta" es opcional (si vacío, usa misma fecha que "Desde"). Pills: Hoy/Ayer/Esta semana/Este mes/Personalizado; label de rango bajo pills muestra el rango exacto.

- [2026-03-26] **Fase 19 — Folio único + Ajuste manual + Cupón envío gratis + Corte de ventas** — BD: 3 migraciones (`fase19a_folio_no_secuencial.sql`, `fase19b_cupon_envio.sql`, `fase19c_ajuste_pedido.sql`). **Folio**: `YYYYMMDD-KBR4` con `random_int()` PHP, loop 5 intentos + UNIQUE INDEX. **Ajuste manual**: `ajuste_manual DECIMAL + ajuste_nota VARCHAR(100)` en `pedidos`; `total_final = total + ajuste_manual` computado en SELECT; form inline en TabPedidos; no afecta recompensas. **Cupón envío gratis**: `codigos_promo.tipo` ENUM extendido a `descuento_porcentaje/descuento_fijo/envio_gratis` + `telefono_restringido VARCHAR(20)` + `usos_maximo`. `promoEfectiva` computed en CheckoutModal: retorna null si tipo=envio_gratis y cliente eligió recoger O ya hay envío gratis por umbral → evita cupón consumido sin beneficio real. `watch(telefono)` re-valida cupón cuando `promoError === 'telefono'`. TabNegocio: tipo selector 3 opciones, campo "valor" oculto si envio_gratis, campo teléfono restringido. **TabReportes**: nuevo componente con pills de período, cards de resumen (ingresos netos/efectivo/transferencia/terminal/envíos/descuentos/ajustes), tabla por día si rango > 1 día. Endpoint `GET reportes`. Dashboard: nuevo tab.

- [2026-03-26] **Fase 18 — Lógica descuentos, UX cuponera, reversión cancelaciones** — Sin cambios de BD. `CheckoutModal.vue`: (1) Cupones disponibles para TODOS los clientes (quitar restricción `compras === 0`); recompensa bloquea cupón: input disabled + aviso "No disponible con recompensa activa" cuando `tiene_recompensa=true`; watch limpia cupón solo cuando `tiene_recompensa` (no cuando `compras > 0`); guard en `descuentoPromo` computed. (2) Cuponera rediseñada: muestra "Con esta compra tendrías X+1 de Y" (incluye la compra actual en el contador), sellos rellenos con `compras_en_ciclo+1`; cuando completa ciclo muestra "¡Con esta compra ganas tu recompensa!"; siempre muestra el premio ("Premio: X% de descuento"). (3) Mensaje recompensa lista: "¡Premio ganado! Completaste tus X compras — Y de descuento aplicado." — transparencia para clientes que no recuerdan. `api/index.php`: (4) Canje de recompensa NO incrementa `total_compras` → `contada_en_recompensas` queda en 0; solo actualiza `ultima_compra`. El cliente arranca en "0 de N" después del canje. (5) Cancelación reestructurada: `total_compras` se revierte solo si `contada_en_recompensas=1` (compra normal); `recompensas_ganadas` se revierte si `descuento_recompensa > 0` (independiente); `codigos_promo.usos` se decrementa si `codigo_promo` no es null — ambos con `GREATEST(0,...)`. Regla de negocio final: recompensa y cupón son mutuamente excluyentes; recompensa tiene prioridad.

- [2026-03-23] **Fase 16 — GA4, Fixes checkout, Backfill clientes, Preview bypass** — GA4: script en `index.html` con guard `!_gaId.startsWith('%')` (no carga en local), `VITE_GA_MEASUREMENT_ID=G-PFYRPZ89GR` en `.env.production`, nuevo `src/composables/useAnalytics.js` con `trackViewItem()` y `trackAddToCart()` disparados desde `MenuPublico.vue` (`abrirModal` y `agregarAlCarrito`). Dimensiones personalizadas creadas en GA4: `Producto` (`item_name`) y `Categoría` (`item_category`). CheckoutModal fixes: (1) desglose cupón en totales (`v-if="descuentoPromo > 0"` faltaba), (2) campo cupón oculto a clientes recurrentes (`historial.compras > 0`), (3) cupón se limpia en `watch(telefono)` al detectar cliente recurrente. Preview bypass: `route.query.preview === 'debug2026'` en `tiendaAbierta` computed — URL: `?preview=debug2026`. BD: `database/migrations/fase16_backfill_clientes.sql` — pobla `clientes.total_compras` desde `pedidos` históricos con `REGEXP_REPLACE` + `GREATEST()`. Aclaración de entornos: `prod` en `config.php` es para clientes con servidor propio; el servidor `nodosmx.com` usa `APP_ENV=qa`. Los archivos `.env.*` de Vite son solo para build local, nunca van al servidor.
- [2026-03-23] **Fixes prod + Fase 15 + Performance imágenes + UX** — Bugs: `cache: 'no-store'` en useApi (browser caching), redirigir al login en 401, cookie sesión 7 días, logout con try/catch, `Cache-Control: no-store` en json_response + api/.htaccess (LiteSpeed), GET codigos-promo filtra activo=1, `Boolean("0")=true` → corregido con `Number()===1` en watch TabNegocio. Fase 15: `codigos_promo_habilitado TINYINT DEFAULT 1` en `restaurantes`, toggle en card header TabNegocio, `v-if` en CheckoutModal, migración `fase15_codigos_promo_habilitado.sql`. Performance imágenes: thumbnail reducido 300→220px, `srcset="thumb 1x, original 2x"`, `decoding="async"` en todas las imágenes de producto, `content-visibility: auto` + `contain-intrinsic-size` en `.producto-card`. Watermark circular: cambio de background-image div a `<img border-radius:50%>`, 26px cards / 34px modales / 44px lightbox, opacity 45%, prop `logoUrl` en ProductoModal+PersonalizacionModal+LightboxImagen. Lightbox fullscreen: nuevo `LightboxImagen.vue` (Teleport, fondo negro, zoom animado, cierre Esc/click fuera/X, pinch-zoom móvil), integrado en ProductoModal y PersonalizacionModal al tocar foto. Texto overlay stock=0 renombrado "No disponible" → "Agotado".
- [2026-03-23] **FASES 11-14 + Fixes — Recompensas, Códigos promo, Stock, UX** — BD: nuevas tablas `recompensas_config`, `clientes`, `codigos_promo`; nuevas cols en `restaurantes`: `stock_minimo_aviso`; nuevas cols en `pedidos`: `descuento_recompensa`, `descuento_promo`, `codigo_promo`. Migraciones: `fase11_recompensas_referidos.sql`, `fase12_stock_minimo_aviso.sql`, `fase13_codigos_promo.sql`, `fase14_pedidos_descuentos.sql`. API: nuevos endpoints `recompensas-config` (GET/PUT auth), `codigos-promo` (CRUD auth), `validar-codigo-promo` (GET público), `cliente-historial` (GET público); POST pedidos: valida stock antes de INSERT, descuenta con `GREATEST(0, stock-cant)`, guarda descuentos y código promo, incrementa `codigos_promo.usos`; PUT productos: `array_key_exists` en lugar de `isset` para aceptar NULL en stock; GET menu: devuelve `stock_minimo_aviso`. `CheckoutModal.vue`: teléfono siempre visible/requerido, cuponera de sellos (`historial`/watch 10 dígitos/`descuento` computed), códigos promotor con validación en tiempo real debounce 600ms (`codigoPromo`/`promoValidada`/`promoError`), botón "+" deshabilitado al límite de stock, total = subtotal+envío−recompensa−promo, WA incluye descuentos. `TabNegocio.vue`: config recompensas con advertencia ⚠️ (cambiar reglas afecta todos), CRUD códigos promo (`codigos_promo`/`crearCodigoPromo`/`toggleCodigoPromo`/`eliminarCodigoPromo`), campo stock_minimo_aviso. `TabPedidos.vue`: chips 🎁 recompensa y 🏷️ código promo cuando > 0. `TabPlatillos.vue`: polling 120s + `visibilitychange`. `ProductoCard.vue`: `thumbSrc` computed con miniatura WebP + fallback a original; `stockMinimoAviso` prop. `ProductoModal.vue`: badge `stockBajo` visible; prop `stockMinimoAviso`. `carrito.js`: `cantidadEnCarrito(id)`, `agregar()` retorna `'ok'`/`'stock_agotado'`. `MenuPublico.vue`: cat-nav scroll listener (reemplaza IntersectionObserver), `_ignoreScroll` flag, cargarMenu extraído, polling 90s + `visibilitychange`, toast rojo stock agotado, `stockMinimoAviso` computed pasado a cards/modal.
- [2026-03-23] **FASE 10 — Estado productos + Estado tienda + Watermark** — BD: `tienda_cerrada_manual`, `tienda_horarios JSON` en `restaurantes`. Migración: `fase10_estado_tienda.sql`. API GET menu: no filtra `disponible`, devuelve `p.disponible`, `p.stock`, `tienda_abierta` (calculado en PHP), `tienda_cerrada_manual`, `tienda_horarios`. PUT restaurantes: acepta ambos campos. `ProductoCard.vue`: overlay "Agotado" (stock=0, grayscale), badge "Próximamente" (disponible=0), watermark logo (opacity 15%), botón "+" oculto si bloqueado. `ProductoModal.vue`: chips de estado, sin botón agregar si bloqueado. `TiendaCerradaView.vue` (nuevo): pantalla de cierre con SVG perrito durmiendo, horarios formateados. `MenuPublico.vue`: `tiendaAbierta` computed, renderiza `TiendaCerradaView` si cerrada. `TabNegocio.vue`: toggle cierre manual + tabla horarios 7 días con time inputs. `TabPlatillos.vue`: label pill "Próximamente" en lugar de "Inactivo". `public/imgs/cerrado_dog.svg` (nuevo).
- [2026-03-16] **FASE 9 — Envío gratis, Terminal a domicilio, Aviso sugerido inteligente, 3D en Personalización, cache-busting** — BD: `pedidos_envio_gratis_desde DECIMAL NULL`, `pedidos_terminal_activo TINYINT DEFAULT 0` en `restaurantes`; ENUM `metodo_pago` amplíado a `('efectivo','transferencia','terminal')`. API: GET menu + GET restaurantes + PUT allowlist actualizados; POST pedidos acepta 'terminal'. Vue: `CheckoutModal.vue` — envío gratis (`umbralGratis`/`envioEsGratis` computed), layout métodos de pago filas (`.opciones-filas`), opción Terminal solo cuando `tipoEntrega==='envio'`, watch resetea a efectivo si cambia a recoger, WA incluye método correcto, separador `──────────` entre platillos. `TabNegocio.vue` — toggle Terminal + toggle Envío gratis con campo de monto. `carrito.js` — `_avisosMostrados` módulo-level (evita serializacion Set/JSON), `tieneCategoriaEnCarrito()`, `marcarAvisoMostrado()`, `avisoYaMostrado()`, `persist: { paths: ['items'] }`. `MenuPublico.vue` — aviso popup movido aquí desde PersonalizacionModal (funciona para productos simples y con personalización), 4 condiciones, `categoriasVisibles` computed filtra categorías sin productos. `PersonalizacionModal.vue` — simplificado: solo emite, sin lógica de aviso; visor 3D integrado en sección visual. `CarritoFlotante.vue` — siempre visible, copy "Carrito vacío"/"Ver pedido". `.htaccess` — `index.html no-cache`, assets hashed `max-age=1año immutable`. Migraciones: `fase9_envio_gratis.sql`, `fase9b_terminal_domicilio.sql`.
- [2026-03-15] **FASE 8 — Migración emojis → MDI icons** — Nuevos: `src/components/SvgIcon.vue` (wrapper genérico), `src/utils/iconosCategorias.js` (63 íconos en 6 grupos, `resolverIcono()`, `ICONO_GRUPOS`), `public/favicon.svg`, `src/assets/base.css`. Modificados: todos los componentes admin y menú. Picker de categorías guarda nombre MDI (ej: `"mdiPizza"`) en BD en lugar de emoji. BD: `categorias.emoji` → `icono VARCHAR(100)`. `btn-primary`/`btn-secondary` con `display:inline-flex; gap:6px`. `<select>` de tipo de grupo → botón group custom. Prop `accent` en TabPlatillos para resolver CSS var dentro de Teleport. "Requerido" → sw-track pill. tema-activo → badge circular con mdiCheck.
- [2026-03-15] **Fix CheckoutModal carritoLocal stale** — `carritoStore.items` referenciado directamente en template y JS (sin alias local). Reset de campos del form tras confirmar exitoso.
- [2026-03-15] **Fix build Vite — inline style** — `<style>` inline en index.html migrado a `src/assets/base.css` importado en `main.js`.
- [2026-03-15] **TabPlatillos — Modal de edición + pills de categoría + guía** — `src/components/admin/tabs/TabPlatillos.vue` reescrito: (1) Editor inline reemplazado por modal (`<Teleport to="body">`) centrado en desktop y bottom sheet en mobile (border-radius 20px 20px 0 0, max-height 92vh). Header del modal muestra thumbnail + nombre del platillo editado. Footer sticky con Cancelar/Guardar. (2) Pills de categoría con scroll horizontal encima de la lista; filtro local via `productosFiltrados` computed; pill activa toma `--accent`; muestra conteo por categoría. (3) Guía colapsable "¿Cómo funciona esto?" dentro del modal cuando personalización está activa — 5 items: Única, Múltiple, Requerido, Controla máx de, Aviso sugerido. (4) Tooltips `title` en campos confusos del editor de grupos. (5) Headers de columna (Opción, Precio, Items) encima de las opciones de cada grupo. (6) Campo `Máx` en opciones solo visible cuando `grupo.tipo === 'radio' && grupo.max_dinamico_grupo_index !== null`. (7) Validaciones: precio no negativo, nombre grupo/opción requerido, grupo sin opciones bloqueado, max_selecciones ≥ 1 en checkbox. (8) `guardarEdicionProducto` sin parámetro — usa `prodEditando.value` internamente; dos try-catch separados para PUT básico y POST grupos con feedback granular.
- [2026-03-15] **Validaciones + feedback visual en tabs admin** — `TabNegocio.vue`: valida WhatsApp requerido si pedidos activos, costo envío no negativo, CLABE exactamente 18 dígitos. `TabApariencia.vue`: valida nombre del restaurante no vacío antes de guardar. `TabCategorias.vue`: `guardando` ref en botón "Agregar" para evitar doble envío; feedback "Agregando..." durante el request.
- [2026-03-15] **Puntos débiles Fase 7 — todos cerrados** — Punto 1 (max_dinamico id↔índice): confirmado no era bug, el PHP hace la conversión en 2 pasadas. Punto 2 (validación editor inline): corregido en TabPlatillos. Punto 3 (POST grupos sin rollback): corregido con try-catch separados y mensaje específico. Punto 4 (min_selecciones): ya estaba corregido en chat 5. Punto 5 (flatMap opciones undefined): ya estaba corregido con `|| []`.
- [2026-03-15] **FASE 7 Chat 5 — PersonalizacionModal acordeón + fixes — PersonalizacionModal acordeón + fixes** — `src/components/menu/PersonalizacionModal.vue`: reescritura completa con acordeón progresivo (solo paso activo expandido, headers siempre visibles con resumen colapsado), radio auto-avanza al seleccionar, checkbox auto-avanza al alcanzar `max_selecciones`, sin btn-listo por paso, botón "Agregar" muted/accent según `puedeAgregar`, aviso complemento como popup `<Teleport to="body">` post-agregar. Fix crítico: `esRequerido(grupo)` verifica `grupo.obligatorio || grupo.requerido` (la columna BD se llama `obligatorio`, no `requerido`). `src/components/menu/CheckoutModal.vue`: `carritoLocal` reemplazado por `carritoStore.items` directo — corrige bug donde reducir/eliminar items en checkout no actualizaba el badge del carrito. `src/views/MenuPublico.vue`: quitado prop `:carrito` en `<CheckoutModal>`.
- [2026-03-15] **FASE 7 BD+API — Personalización por pasos** — `database/migrations/fase7_personalizacion.sql` (migración ejecutada en local). `api/index.php`: nuevos endpoints `GET/POST producto-grupos`; `menu` GET extiende productos con `grupos[]` embebidos (query eficiente sin N+1); `pedidos` POST guarda `pedido_item_opciones`; `pedidos` GET retorna opciones por item. Pendiente: toda la capa Vue (Chat 2+).
- [2026-03-15] **PINIA — Carrito store con persistencia** — `src/stores/carrito.js` (store con `items`, `agregar()`, `vaciar()`). `main.js`: registra Pinia + `pinia-plugin-persistedstate`. `MenuPublico.vue`: `carrito` pasa de `ref([])` a `computed(() => carritoStore.items)`. El carrito sobrevive recargas del menú público.
- [2026-03-15] **ARQUITECTURA — Dashboard particionado + reorganización components** — `Dashboard.vue` reducido de 1721 → ~170 líneas (solo orquestador). Creados: `src/components/admin/tabs/` (5 tabs) y `src/assets/admin.css` (estilos compartidos del admin). `src/components/` reorganizado: carpeta `menu/` para componentes del menú público, carpeta `admin/tabs/` para tabs. Props/emits: Dashboard pasa `restauranteId`, `categorias`, `restaurante`, `menuUrl`, `active` a cada tab; recibe `notif`, `categorias-changed`, `restaurante-updated`, `tema-preview`. Bug fix en imports relativos de componentes movidos (`../` → `../../`).
- [2026-03-11] **AUTENTICACIÓN — Cookies HttpOnly** — Eliminado token en localStorage y query string. `helpers.php`: `require_auth()` lee `$_COOKIE['token']`; funciones `set_auth_cookie()`/`clear_auth_cookie()`. `api/index.php`: login emite cookie, nuevos endpoints `logout` y `auth-check`. `useApi.js`: `credentials: 'include'`, sin lógica de token. `router/index.js`: guard async con `checkAuth()` cacheado + `resetAuth()` exportado. `Dashboard.vue`: logout llama API + `resetAuth()`; uploads usan `credentials: 'include'`. `Login.vue`: sin `localStorage`.
- [2026-03-11] **TEMA CSS — Sistema de dos capas** — Archivos creados/modificados: `src/assets/theme.css` (variables del sistema + clases globales `.btn-primary/.btn-secondary/.btn-danger/.btn-sm` + override `.tema-oscuro-admin`), `src/utils/themes.js` (fuente única de TEMAS y TEMAS_EXTRA, extraído de Dashboard.vue), `src/main.js` (importa theme.css). Componentes actualizados para usar `.btn-primary` como base: `ProductoCard.vue` (`.btn-ver`), `ProductoModal.vue` (`.btn-agregar-carrito`), `CheckoutModal.vue` (`.btn-confirmar`), `Dashboard.vue` (scoped `.btn-primary` eliminado — usa global). `ACCIONABLES_MEJORAS.md` eliminado — contenido fusionado en `CONTEXTO_PROYECTO.md` sección "Accionables técnicos pendientes".
