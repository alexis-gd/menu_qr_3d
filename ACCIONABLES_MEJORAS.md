# ACCIONABLES TÉCNICOS — menu_qr_3d
> Generados a partir de sesión de análisis arquitectónico con el desarrollador.
> Agregar al CONTEXTO_PROYECTO.md en la sección de Funcionalidades Pendientes.

---

## BLOQUE 1 — Seguridad de autenticación
**Prioridad: Alta — resolver antes de comercializar**

- [ ] **Reemplazar token en localStorage por cookies HttpOnly**
  - El token actual es visible en DevTools → Application → localStorage
  - Cualquier script XSS puede leerlo
  - Solución: PHP emite `Set-Cookie: token=...; HttpOnly; Secure; SameSite=Strict`
  - El browser lo envía automático en cada request, JS no puede accederlo
  - Afecta: `api/index.php` (login), `api/helpers.php` (validación), `src/composables/useApi.js` (eliminar header manual), `src/views/admin/Login.vue`, router guard en `src/router/index.js`

---

## BLOQUE 2 — Sistema de temas CSS (crítico para comercialización)
**Prioridad: Alta — base para que los temas del cliente funcionen correctamente**

- [ ] **Crear `src/assets/theme.css` con variables CSS del sistema (valores fijos)**
  - Tamaños de botones, espaciados, border-radius, tipografía, sombras
  - Estos valores NO cambian por tema de cliente — son el sistema de diseño base
  - Importar una sola vez en `main.js`
  - Ejemplo de variables: `--btn-padding`, `--radius-md`, `--spacing-sm`, `--shadow-card`

- [ ] **Migrar colores de temas de cliente a variables CSS dinámicas vía JS**
  - Los 5 temas (`calido`, `oscuro`, `moderno`, `rapida`, `rosa`) actualmente tienen sus colores hardcodeados en `MenuPublico.vue` como bloques CSS separados
  - Refactorizar para que cada tema sea un objeto JS con sus valores
  - Al cargar el menú, aplicar con `document.documentElement.style.setProperty('--color-primary', tema.primary)`
  - Beneficio: el admin cambia tema → BD guarda el slug del tema → menú público lo lee de la API y aplica en runtime sin rebuild
  - El dashboard admin también debe leer y aplicar el tema activo del restaurante

- [ ] **Estandarizar botones con clases utilitarias globales**
  - Actualmente cada componente define sus propios estilos de botón con tamaños inconsistentes
  - Crear clases `.btn-primary`, `.btn-secondary`, `.btn-sm`, `.btn-danger` en `theme.css`
  - Reemplazar estilos locales de botones en todos los componentes

---

## BLOQUE 3 — Arquitectura de componentes
**Prioridad: Media — mejora mantenibilidad y performance**

- [ ] **Partir `Dashboard.vue` en componentes por tab**
  - Dashboard.vue concentra toda la lógica del admin y crece con cada feature
  - Cada tab activa (`Platillos`, `Categorías`, `Apariencia`, `Negocio`, `Pedidos`) debe ser un componente hijo independiente
  - Dashboard.vue queda como orquestador: maneja la tab activa y pasa props/emits
  - Beneficio: carga lazy por tab, archivos mantenibles, cada sección tiene su propio scope de datos
  - Ruta sugerida: `src/components/admin/tabs/TabPlatillos.vue`, `TabApariencia.vue`, etc.

- [ ] **Reorganizar carpeta `src/components/`**
  - Estructura actual: todos los componentes en raíz de components/
  - Estructura propuesta:
    ```
    src/components/
      menu/     ← ProductoCard, ProductoModal, ModelViewer3D, CarritoFlotante, CheckoutModal
      admin/
        tabs/   ← TabPlatillos, TabCategorias, TabApariencia, TabNegocio, TabPedidos
    ```

---

## BLOQUE 4 — Estado global con Pinia
**Prioridad: Media**

- [ ] **Migrar carrito de `ref([])` local en MenuPublico.vue a Pinia store**
  - El carrito actual vive como estado local en MenuPublico.vue
  - Si el componente se destruye o el usuario navega, el carrito se pierde
  - Crear `src/stores/carrito.js` con Pinia
  - Usar plugin `pinia-plugin-persistedstate` para persistir en localStorage automáticamente
  - Beneficio: el carrito sobrevive navegación y recarga de página

- [ ] **Store de restaurante activo en admin**
  - El `restaurante_id` activo del admin actualmente se pasa por props o se repite en cada componente
  - Centralizar en `src/stores/admin.js`

---

## BLOQUE 5 — Instrucción de documentación para Claude Code
**Ver archivo INSTRUCCION_DOCUMENTACION.md**
- Cada vez que Claude Code modifique o cree un archivo relevante, debe agregar una entrada al log de cambios arquitectónicos en `CONTEXTO_PROYECTO.md`