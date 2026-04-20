# .claude/rules/convenciones-codigo.md — Convenciones PHP + Vue

## PHP

- Sin namespaces, sin autoload complejo. Código limpio y directo.
- Rutas relativas en BD para fotos y modelos. URL absoluta se construye en PHP con `UPLOADS_URL`.
- `array_key_exists($f, $body)` en lugar de `isset()` para aceptar NULL explícito (ej: quitar control de stock).
- Sin `echo` sueltos ni HTML en PHP. Todo output vía `json_response()`.

## Vue / Vite

- **Composition API con `<script setup>`** siempre. Sin Options API.
- `base` en `vite.config.js` debe coincidir con ruta del servidor: `/menu/` o `/`.
- Imágenes de UI estática → `public/imgs/` → referenciar como `/menu/imgs/archivo.png`.
- Imágenes de productos → siempre URLs absolutas desde la API. Nunca imports de módulo.
- `.htaccess` en carpeta dist para Vue Router modo history.

## Fechas locales — OBLIGATORIO

Nunca `new Date().toISOString().slice(0,10)`. `toISOString()` devuelve UTC — en México (UTC-6) da el día siguiente después de las 6 PM.

Usar siempre:
```js
const localIso = (d = new Date()) =>
  `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`
```

Aplica a: cualquier fecha `YYYY-MM-DD` calculada en el frontend (reportes, filtros, rangos).

## URLs públicas (QR, compartir menú)

```js
const origin = import.meta.env.VITE_PUBLIC_ORIGIN || window.location.origin
```

`window.location.origin` devuelve `localhost:5173` en dev. Siempre usar la env var para URLs que se compartirán externamente.

## VueDatePicker v12 — pitfalls críticos

- Import: `import { VueDatePicker } from '@vuepic/vue-datepicker'` (named, NO default)
- `:enable-time-picker="false"` no funciona — usar `:time-config="{ enableTimePicker: false }"`
- `v-if` causa crash al desmontarse con dropdown abierto → usar `v-show`
- Modo range + hora: usar dos pickers separados (uno desde, otro hasta)
- Time picker: v-model espera `{ hours, minutes }`, no string. Usar `strToTime(s)`/`timeToStr(t)`

## CSS / Estilos

- Variables del sistema en `src/assets/theme.css` (tamaños, radios, sombras)
- Temas del cliente en `src/utils/themes.js` (fuente de verdad de los 5 temas)
- Estilos compartidos del admin en `src/assets/admin.css`
- `var(--accent)` NO llega a elementos teleportados a `<body>` → pasar como prop y aplicar `:style="{ '--accent': accent }"`

## Pinia store carrito

- `carritoStore.items` siempre directo en template y JS. Nunca alias `= carritoStore.items` (queda stale tras `vaciar()`).
- `persist: { paths: ['items'] }` (no `persist: true`) para no serializar el Set interno de avisos.

## Componentes — estructura

```
src/components/
  menu/     ← ProductoCard, ProductoModal, PersonalizacionModal, ModelViewer3D, CarritoFlotante, CheckoutModal
  admin/
    tabs/   ← TabPlatillos, TabCategorias, TabApariencia, TabNegocio, TabPedidos
```

Dashboard.vue es el orquestador — pasa props, recibe emits de los tabs.
