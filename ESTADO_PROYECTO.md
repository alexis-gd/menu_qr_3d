# 🚀 ESTADO DEL PROYECTO menu_qr_3d — 27 FEB 2026

## ✅ COMPLETADO

### Fase 1 — Backend y Base de Datos
- [x] Tablas MySQL (usuarios, restaurantes, categorias, productos, fotos_producto, meshy_jobs, mesas, sesiones_admin)
- [x] config.php con constantes (DB, BASE_URL, ADMIN_TOKEN, MESHY_API_KEY)
- [x] Router principal en api/index.php

### Fase 2 — Frontend Menú Cliente
- [x] Vue 3 + Vite configurado (base: '/menu/')
- [x] Componentes: MenuPublico.vue, ProductoCard.vue, ProductoModal.vue, ModelViewer3D.vue
- [x] Imagen placeholder taco.jpg en public/imgs/
- [x] .htaccess para Vue Router history mode
- [x] Build production compilado

### Fase 3 — Panel Admin
- [x] Login view + token en localStorage
- [x] Protección de rutas (beforeEach guard)
- [x] Vista Restaurantes (GET/POST)
- [x] Vista Productos (GET/POST/PUT/DELETE)
- [x] Creación de categorías inline
- [x] Subida de múltiples fotos por producto

### Fase 4 — Integración Meshy (Parcial)
- [x] Endpoint `upload-fotos` llama Meshy API para crear 3D task
- [x] Tabla meshy_jobs registra estado de conversión
- [x] Endpoint `job-status` para consultar estado
- [x] Script cron/check_meshy_jobs.php (descarga .glb cuando listo)
- [x] Frontend muestra badges: "3D listo", "pending", "processing"
- [x] Botón "Ver estado" para polling manual
- [ ] Cron registrado en cPanel (aún no)

### Composables & Utils
- [x] useApi.js con GET/POST/PUT/DELETE + auth (token por query string)
- [x] Router completo con 4 rutas (menu, admin, admin/restaurantes/1/productos)

### Upload & Almacenamiento
- [x] Archivos fotos guardan en /uploads/fotos/{producto_id}/
- [x] URLs públicas registradas en BD

---

## 📊 ARQUITECTURA ACTUAL

```
Frontend (Vue 3 + Vite)
├── /menu              ← Menú público (ProductoCard + Modal + 3D)
└── /admin             ← Panel admin (Login + CRUD)
    ├── restaurantes   ← Lista/crear restaurantes
    └── /id/productos  ← Productos de restaurante

Backend (PHP nativo)
├── api/index.php      ← Router (menu, login, restaurantes, categorias, productos, upload-fotos, job-status)
├── api/helpers.php    ← Funciones (json_response, require_auth, get_bearer_token)
├── api/config.php     ← Constantes (MySQL, MESHY_API_KEY, ADMIN_TOKEN)
└── cron/check_meshy_jobs.php ← Poll Meshy cada 2 minutos

Base de Datos
├── usuarios           ← Admins (email, password_hash)
├── restaurantes       ← Restaurants (slug, nombre, descripcion)
├── categorias         ← Grupos de productos (Entradas, Platos fuertes)
├── productos          ← Platillos (nombre, precio, foto_principal, modelo_glb_path)
├── fotos_producto     ← Imágenes enviadas a Meshy (ruta, url_publica)
└── meshy_jobs         ← Estado de conversión 3D (status, task_id, intentos)
```

---

## 🔧 STACK FINAL

| Capa | Tech | Status |
|------|------|--------|
| Frontend | Vue 3 + Vite | ✅ |
| 3D/AR | Google Model-Viewer | ✅ (ready) |
| Generación 3D | Meshy.ai API | ✅ (ready) |
| Backend | PHP 8.1 nativo | ✅ |
| DB | MySQL | ✅ |
| Servidor | cPanel | ✅ (local testing) |
| Auth | Token estático + localStorage | ⚠️ (workaround: query string) |
| QR | qrcode.js | ⏳ (Fase 5) |

---

## 📈 SIGUIENTE: Fase 5 — QR y Mesas

- [ ] Endpoint mesas (GET/POST restaurante_id)
- [ ] Vista admin: Mesas (tabla lista, crear)
- [ ] Generar QR code para cada mesa (qrcode.js)
- [ ] Imprimir/descargar QR por mesa
- [ ] Parámetro ?mesa=numero en URL del menú público

---

## 🐛 BUGS CONOCIDOS & WORKAROUNDS

| Problema | Workaround | Solución permanente |
|----------|-----------|-------------------|
| Headers Authorization no llegan | Token por query string | Investigar Apache/Vite, usar cookies HttpOnly |
| No logs encontrados | error_log() | Escribir a archivo /tmp o configurar php.ini |

---

## 📁 ESTRUCTURA CARPETAS

```
menu_qr_3d/
├── api/                    ← Backend PHP
│   ├── config.example.php
│   ├── config.php         ← LOCAL ONLY, .gitignore
│   ├── helpers.php
│   └── index.php
├── db/
│   └── init.sql           ← Script creación tablas
├── cron/
│   └── check_meshy_jobs.php ← Polling Meshy
├── src/                    ← Frontend Vue
│   ├── views/
│   │   ├── MenuPublico.vue
│   │   └── admin/
│   │       ├── Login.vue
│   │       ├── Restaurantes.vue
│   │       └── Productos.vue
│   ├── components/
│   ├── composables/
│   ├── router/
│   └── main.js
├── public/
│   └── imgs/              ← UI estática (taco.jpg, etc)
├── dist/                  ← Build production (no se sube, .gitignore)
├── uploads/               ← RUNTIME FILES (no se sube, .gitignore)
│   ├── fotos/
│   └── modelos/
├── package.json
├── vite.config.js
└── .htaccess              ← Router history mode
```

---

## 🔗 REPOSITORIO

- **URL:** https://github.com/alexis-gd/menu_qr_3d
- **Rama:** master
- **Últimos commits:** Hotfix token auth, Meshy integration, CRUD completo

---

## 📝 NOTAS

- **Seguridad local:** El token está hardcodeado en config.php. Cambiar en producción.
- **Meshy:** Necesita API key válida para conversión 3D end-to-end. Plan gratuito: 200 créditos/mes.
- **cPanel:** Script cron aún no configurado. Necesita acceso SSH/cPanel.
- **Testing:** Usuario de prueba `katche4@gmail.com` / `katch123` ya existe.

