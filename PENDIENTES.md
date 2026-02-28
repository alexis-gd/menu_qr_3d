# 📋 PENDIENTES A MODIFICAR

## 🔴 CRÍTICO

### 1. Auth Headers — Token por URL (INSEGURO)
**Problema:** Los headers `Authorization: Bearer` no llegan al servidor desde el navegador (probablemente Vite proxy o Apache config).

**Solución actual:** Token se pasa por query string (`?token=...`).

**Solución permanente:** 
- Investigar configuración de Apache/Vite proxy headers.
- Alternativa: usar cookies con HttpOnly en lugar de localStorage.
- Cambiar `useApi.js` para volver a usar headers cuando esté resuelto.

**Archivos afectados:**
- `src/composables/useApi.js` (línea que añade token a queryParams)
- `api/helpers.php` (require_auth() que valida por query string)

---

## 🟡 IMPORTANTE

### 2. Validación de formularios
Los formularios de Productos.vue y Restaurantes.vue no tienen validación visual (cliente + servidor).

**Implementar:**
- Validación en tiempo real en Vue (tamaño de strings, números, etc).
- Errores amigables al usuario.

### 3. Cron Job en cPanel
El script `cron/check_meshy_jobs.php` se debe registrar en cPanel cada 2 minutos para descargar .glb automáticamente.

**Nota:** Aún no configurado en tu hosting. Requiere acceso a cPanel.

### 4. Meshy API Key
Configurar `MESHY_API_KEY` en `api/config.php` para que la conversión 3D funcione end-to-end.

---

## 🟢 NICE-TO-HAVE

### 5. QR y Mesas
Generar QR por mesa con `qrcode.js` en el front.
Crear tabla de mesas con endpoint en back.

### 6. Mostrar modelo 3D en modal
Integrar `<model-viewer>` correctamente cuando `tiene_ar = 1`.

### 7. Feedback visual mejorado
Loaders, toasts, mejor UX en errores.

---
