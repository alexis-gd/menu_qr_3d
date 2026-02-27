# CONTEXTO PROYECTO: menu_qr_3d
> Lee este archivo COMPLETO al inicio de cada nuevo chat antes de escribir cualquier código.
> Este documento es la fuente de verdad del proyecto. No inventar nada que no esté aquí.

---

## 1. QUÉ ES ESTE PROYECTO

Menú digital para restaurantes que se abre al escanear un QR desde la mesa.
Permite ver los platillos en **3D interactivo** (siempre disponible) y en **AR sobre la mesa** (cuando el dispositivo lo soporta).
Los modelos 3D se generan automáticamente desde fotos tomadas por el dueño del restaurante, usando la API de Meshy.ai. No se hacen modelos 3D manualmente.

---

## 2. STACK TECNOLÓGICO DEFINITIVO

| Capa | Tecnología | Notas |
|---|---|---|
| Frontend cliente | Vue 3 + Vite | Compilado localmente, dist subido por FTP |
| Frontend admin | Vue 3 + Vite | Mismo proyecto o módulo separado |
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
│   ├── index.php                  ← Router principal
│   ├── config.php                 ← Constantes, conexión DB, helpers
│   ├── helpers.php                ← Funciones reutilizables
│   └── routes/
│       ├── menu.php               ← GET menú público
│       ├── productos.php          ← CRUD productos (admin)
│       ├── upload.php             ← Subida fotos + disparo a Meshy
│       ├── restaurantes.php       ← CRUD restaurantes (admin)
│       └── auth.php               ← Login admin
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
│   │       ├── Dashboard.vue
│   │       ├── Productos.vue
│   │       └── Restaurantes.vue
│   ├── components/
│   │   ├── ProductoCard.vue
│   │   ├── ProductoModal.vue      ← Contiene <model-viewer>
│   │   └── ModelViewer3D.vue     ← Wrapper del web component
│   ├── composables/
│   │   └── useApi.js              ← Fetch a /api/
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

Simple por ahora: token estático en header `Authorization: Bearer {ADMIN_TOKEN}`.
El token se define en `config.php` como constante.
Login: el admin ingresa usuario/password → PHP valida contra tabla `usuarios` → devuelve el token → Vue lo guarda en `localStorage`.
No usar JWT por ahora. Sesiones PHP simples o token estático por restaurante.

---

## 8. API ENDPOINTS DEFINIDOS

Todos bajo `/api/index.php` con parámetro `?route=`:

| Método | Ruta | Descripción | Auth |
|---|---|---|---|
| GET | `/api/?route=menu&restaurante={slug}` | Menú público completo | No |
| POST | `/api/?route=login` | Login admin | No |
| GET | `/api/?route=restaurantes` | Lista restaurantes | Sí |
| POST | `/api/?route=restaurantes` | Crear restaurante | Sí |
| GET | `/api/?route=productos&restaurante_id={id}` | Lista productos | Sí |
| POST | `/api/?route=productos` | Crear producto | Sí |
| PUT | `/api/?route=productos&id={id}` | Editar producto | Sí |
| POST | `/api/?route=upload-fotos` | Subir fotos + llamar Meshy | Sí |
| GET | `/api/?route=job-status&producto_id={id}` | Estado conversión 3D | Sí |

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

## 14. NOTAS IMPORTANTES PARA FUTUROS CHATS

- El proyecto se llama `menu_qr_3d`
- El desarrollador maneja Vue.js, PHP nativo, JS. No necesita explicaciones básicas.
- Cuando se pida código, darlo completo y funcional, no pseudocódigo.
- Si hay duda sobre una decisión técnica, revisar sección 12 antes de proponer alternativas.
- Los archivos PHP NO usan namespaces ni autoload complejo. Código limpio y directo.
- Vue usa Composition API con `<script setup>`. No Options API.
- Para CSS en Vue: scoped styles dentro del componente. Sin frameworks CSS externos por ahora (puede cambiar).