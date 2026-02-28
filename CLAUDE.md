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
  index.php           ← Router principal
  routes/             ← Endpoints PHP
uploads/
  fotos/              ← Fotos de productos por producto_id
  modelos/            ← Archivos .glb descargados de Meshy
cron/
  check_meshy_jobs.php ← Cron cada 2 min en cPanel
```

## Comandos útiles
```bash
# Desarrollo Vue local
npm run dev

# Build para subir a cPanel por FTP
npm run build

# Verificar PHP local
php -v
```

## Cómo funciona la conversión 3D (flujo async)
1. Admin sube fotos → PHP llama Meshy API → guarda `task_id` en tabla `meshy_jobs`
2. Cron de cPanel cada 2 min → consulta status en Meshy
3. Cuando `SUCCEEDED` → descarga `.glb` a `/uploads/modelos/` → actualiza `productos.tiene_ar = 1`
4. El cliente nunca espera: el modelo ya está listo cuando llega al menú
