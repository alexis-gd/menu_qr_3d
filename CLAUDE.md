# menu_qr_3d — Menú Digital 3D/AR para Restaurantes

## Contexto completo del proyecto
Lee estos archivos antes de cualquier tarea compleja:
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

### Git
- Rama principal: `master`
- Repo: https://github.com/alexis-gd/menu_qr_3d
- `dist/` y `uploads/` no se suben al repo (ver `.gitignore`)

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
"actualiza contextos" = revisar y actualizar los 4 archivos de contexto del proyecto:
- `CONTEXTO_BASE_DE_DATOS.md`
- `CONTEXTO_PROYECTO.md`
- `CLAUDE.md`
- `MEMORY.md` (en `~/.claude/projects/.../memory/`)

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

- [2026-03-15] **FASE 7 Chat 5 — PersonalizacionModal acordeón + fixes** — `src/components/menu/PersonalizacionModal.vue`: reescritura completa con acordeón progresivo (solo paso activo expandido, headers siempre visibles con resumen colapsado), radio auto-avanza al seleccionar, checkbox auto-avanza al alcanzar `max_selecciones`, sin btn-listo por paso, botón "Agregar" muted/accent según `puedeAgregar`, aviso complemento como popup `<Teleport to="body">` post-agregar. Fix crítico: `esRequerido(grupo)` verifica `grupo.obligatorio || grupo.requerido` (la columna BD se llama `obligatorio`, no `requerido`). `src/components/menu/CheckoutModal.vue`: `carritoLocal` reemplazado por `carritoStore.items` directo — corrige bug donde reducir/eliminar items en checkout no actualizaba el badge del carrito. `src/views/MenuPublico.vue`: quitado prop `:carrito` en `<CheckoutModal>`.
- [2026-03-15] **FASE 7 BD+API — Personalización por pasos** — `database/migrations/fase7_personalizacion.sql` (migración ejecutada en local). `api/index.php`: nuevos endpoints `GET/POST producto-grupos`; `menu` GET extiende productos con `grupos[]` embebidos (query eficiente sin N+1); `pedidos` POST guarda `pedido_item_opciones`; `pedidos` GET retorna opciones por item. Pendiente: toda la capa Vue (Chat 2+).
- [2026-03-15] **PINIA — Carrito store con persistencia** — `src/stores/carrito.js` (store con `items`, `agregar()`, `vaciar()`). `main.js`: registra Pinia + `pinia-plugin-persistedstate`. `MenuPublico.vue`: `carrito` pasa de `ref([])` a `computed(() => carritoStore.items)`. El carrito sobrevive recargas del menú público.
- [2026-03-15] **ARQUITECTURA — Dashboard particionado + reorganización components** — `Dashboard.vue` reducido de 1721 → ~170 líneas (solo orquestador). Creados: `src/components/admin/tabs/` (5 tabs) y `src/assets/admin.css` (estilos compartidos del admin). `src/components/` reorganizado: carpeta `menu/` para componentes del menú público, carpeta `admin/tabs/` para tabs. Props/emits: Dashboard pasa `restauranteId`, `categorias`, `restaurante`, `menuUrl`, `active` a cada tab; recibe `notif`, `categorias-changed`, `restaurante-updated`, `tema-preview`. Bug fix en imports relativos de componentes movidos (`../` → `../../`).
- [2026-03-11] **AUTENTICACIÓN — Cookies HttpOnly** — Eliminado token en localStorage y query string. `helpers.php`: `require_auth()` lee `$_COOKIE['token']`; funciones `set_auth_cookie()`/`clear_auth_cookie()`. `api/index.php`: login emite cookie, nuevos endpoints `logout` y `auth-check`. `useApi.js`: `credentials: 'include'`, sin lógica de token. `router/index.js`: guard async con `checkAuth()` cacheado + `resetAuth()` exportado. `Dashboard.vue`: logout llama API + `resetAuth()`; uploads usan `credentials: 'include'`. `Login.vue`: sin `localStorage`.
- [2026-03-11] **TEMA CSS — Sistema de dos capas** — Archivos creados/modificados: `src/assets/theme.css` (variables del sistema + clases globales `.btn-primary/.btn-secondary/.btn-danger/.btn-sm` + override `.tema-oscuro-admin`), `src/utils/themes.js` (fuente única de TEMAS y TEMAS_EXTRA, extraído de Dashboard.vue), `src/main.js` (importa theme.css). Componentes actualizados para usar `.btn-primary` como base: `ProductoCard.vue` (`.btn-ver`), `ProductoModal.vue` (`.btn-agregar-carrito`), `CheckoutModal.vue` (`.btn-confirmar`), `Dashboard.vue` (scoped `.btn-primary` eliminado — usa global). `ACCIONABLES_MEJORAS.md` eliminado — contenido fusionado en `CONTEXTO_PROYECTO.md` sección "Accionables técnicos pendientes".