# docs/estado-producto.md — Estado funcional del producto

## Fases completadas

- [x] **Fase 1** — Backend y BD: config.php, router, endpoints
- [x] **Fase 2** — Frontend menú cliente: Vue 3 + Vite, componentes, build
- [x] **Fase 3** — Panel admin: login, CRUD restaurantes/categorias/productos
- [x] **Fase 4** — Integración 3D: upload-fotos, upload-glb, model-viewer, cron
- [x] **Fase 5** — QR & Mesas: endpoint mesas, admin Mesas.vue, QR por mesa
- [x] **Fase 6** — Sistema de pedidos: carrito, checkout WhatsApp, TabPedidos/Negocio
- [x] **Fase 7** — Personalización por pasos (estilo Rappi/Uber Eats)
- [x] **Fase 8** — Migración emojis → Material Design Icons (MDI)
- [x] **Fase 9** — Envío gratis, terminal a domicilio, aviso sugerido, 3D en PersonalizacionModal, cache-busting
- [x] **Fase 10** — Estado de productos (Agotado/Próximamente/Normal), estado de tienda, watermark logo
- [x] **Fase 11** — Sistema de recompensas por sellos
- [x] **Fase 12** — Stock mínimo aviso configurable
- [x] **Fase 13** — Códigos de promotor (CRUD en admin, validación en checkout)
- [x] **Fase 14** — Descuentos guardados en pedidos
- [x] **Fase 15** — Toggle sistema de cupones por restaurante
- [x] **Fase 16** — GA4, fixes checkout, backfill clientes, preview bypass
- [x] **Fase 17** — `pedidos.contada_en_recompensas` (corrección reversión cancelaciones)
- [x] **Fase 18** — UX cuponera, lógica descuentos, reversión cancelaciones
- [x] **Fase 19** — Folio no secuencial + ajuste manual + cupón envío gratis + corte de ventas
- [x] **Fase 20a** — Popup tienda cerrada + modo lectura + pedidos programados
- [x] **Fase 21** — Push notifications pedidos nuevos + PWA/service worker
- [x] **Fase 22** — Sistema de trial: `trial_expires_at`, TrialBanner, overlay admin. Ver [demos.md](demos.md)
- [x] **Fase 23** — Migración formal `qr_frase` y `qr_wifi_*` (existían en prod sin migración)
- [x] **Sistema de demos** — 5 rubros, `create_demo.php`, `APP_ENV=demo_local`. Ver [demos.md](demos.md)

---

## Funcionalidades pendientes

- [ ] **Mesas multi-QR** — `Mesas.vue` existe y completo, pero está inactivo. Activar cuando se requiera QR individual por mesa.
- [ ] **Cron Meshy en cPanel** — Script existe (`cron/check_meshy_jobs.php`), no registrado. Freeze mientras Meshy no sea flujo prioritario.
- [ ] **Meshy API key** — En placeholder. Flujo actual: semi-manual (admin sube .glb desde web de Meshy).
- [ ] **Store de restaurante activo en admin** — Pendiente si se necesita multi-restaurante real.

---

## API endpoints

Todos bajo `api/index.php` con `?route=`:

| Método | Ruta | Auth |
|---|---|---|
| GET | `menu` (+ `?restaurante=slug` opcional) | No |
| POST | `login` | No |
| GET/POST | `auth-check`, `logout` | Cookie |
| GET/POST | `restaurantes` | Sí |
| GET/POST | `categorias` | Sí |
| GET/POST/PUT/DELETE | `productos` | Sí |
| GET/POST/DELETE | `mesas` | Sí |
| POST | `upload-fotos`, `upload-glb`, `upload-logo` | Sí |
| GET | `job-status` | Sí |
| GET/POST/PUT | `pedidos` | GET/PUT=Sí, POST=No |
| GET/POST | `producto-grupos` | GET=No, POST=Sí |
| GET | `reportes` | Sí |
| GET | `recompensas-config` / PUT | Sí |
| GET/POST | `codigos-promo` / PUT toggle | Sí |
| GET | `validar-codigo-promo` | No |
| GET | `cliente-historial` | No |
| GET | `vapid-key` | No |
| POST | `push-subscribe`, `push-unsubscribe` | Sí |

---

## Estados de productos en menú público

| Estado | Condición BD | Visible | Compra |
|---|---|---|---|
| Normal | `disponible=1`, `stock IS NULL` o `>0` | ✓ | ✓ |
| Agotado | `disponible=1`, `stock=0` | ✓ | ✗ |
| Próximamente | `disponible=0` | ✓ | ✗ |
| Oculto | `activo=0` (borrado lógico) | ✗ | ✗ |

Control es 100% frontend. API devuelve todos los productos con `activo=1`.

---

## Rutas críticas de archivos

| Campo BD | Valor ejemplo | URL completa |
|---|---|---|
| `foto_principal` | `fotos/1/foto_1_0_1234.jpg` | `UPLOADS_URL . $foto_principal` |
| `modelo_glb_path` | `modelo_1_1234.glb` | `UPLOADS_URL . 'modelos/' . $modelo_glb_path` |
| `logo_url` | `logos/logo_1_1234.jpg` | `UPLOADS_URL . $logo_url` |

`UPLOADS_URL` varía por entorno. Ver [deploy.md](deploy.md).

**Logos en demo_local:** van a `uploads_demos/logos/` para no colisionar con `uploads/logos/` de prod/local (IDs de BD demo son independientes).

---

## Flujo 3D — decisión actual

Meshy API requiere plan Pro ($20/mes). Flujo adoptado **semi-manual**:
1. Admin genera `.glb` en meshy.ai (web) o TRELLIS.2 (Hugging Face, gratis)
2. Descarga el `.glb`
3. Lo sube desde panel admin → botón "Subir 3D (.glb)"
4. Sistema valida magic bytes y activa `tiene_ar = 1`

El cron `check_meshy_jobs.php` existe pero no está activo.

---

## Estado QA

- ✅ QA operativo hasta Fase 21
- ✅ Push funcionando en QA
- ⚠️ Pendiente aplicar `fase23_qr_frase_wifi.sql` en QA
- ℹ️ La BD demo en QA (`nodosmxc_menu_demos`) requiere setup inicial (ver [demos.md](demos.md))
