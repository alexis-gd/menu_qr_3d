# docs/demos.md — Sistema de Demos para Prospectos

## Qué es

Sistema para generar demos rápidas por rubro. Cada prospecto tiene su propio restaurante aislado (slug único, usuario propio, trial de 7 días). Los 5 rubros son plantillas SQL — no restaurantes activos.

---

## Arquitectura

```
nodosmx.com/menu/              ← Dolce Mare (BD prod: nodosmxc_menu_qr_3d)

taqueria.nodosmx.com/  ─┐
burgers.nodosmx.com/   ─┤─→ public_html/demos/        ← Document Root de cada subdominio
pizza.nodosmx.com/     ─┤       .htaccess              ← redirect / → /menu/
mariscos.nodosmx.com/  ─┤       menu/                  ← mismo dist (base: /menu/)
cafe.nodosmx.com/      ─┘         api/env.php          ← APP_ENV=demo

                            BD: nodosmxc_menu_demos
                              restaurantes: template-taqueria (plantilla, trial_expires_at=NULL)
                                           tacos-garcia     ← prospecto
                                           tacos-lopez      ← otro prospecto
```

**Flujo URL:**
```
taqueria.nodosmx.com/?r=tacos-garcia
  → .htaccess redirect → /menu/?r=tacos-garcia
  → Vue carga, guarda slug en sessionStorage, limpia ?r= de la URL
  → Recargas posteriores usan sessionStorage['menu_slug']
```

---

## Entornos de configuración

| Entorno | BD | Quién lo usa |
|---|---|---|
| `local` | `nodosmxc_menu_qr_3d` | Dev con Dolce Mare |
| `demo_local` | `nodosmxc_menu_demos` | Dev probando el sistema demo |
| `demo` | `nodosmxc_menu_demos` | Servidor nodosmx.com/demos/ |
| `qa` | `nodosmxc_menu_qr_3d` | QA en nodosmx.com/menu/ |
| `prod` | BD del cliente | Deploy a cliente final |

Cambiar entorno: editar `api/env.php` (gitignored):
```php
<?php define('APP_ENV', 'demo_local');
```

---

## Archivos del sistema de demos

| Archivo | Propósito |
|---|---|
| `database/demos/init_demo_db.sql` | Schema completo para crear BD demo desde cero |
| `database/demos/template_taqueria.sql` | Plantilla restaurante: slug `template-taqueria`, tema `calido` |
| `database/demos/template_burgers.sql` | Plantilla: slug `template-burgers`, tema `oscuro` |
| `database/demos/template_pizza.sql` | Plantilla: slug `template-pizza`, tema `moderno` |
| `database/demos/template_mariscos.sql` | Plantilla: slug `template-mariscos`, tema `calido` |
| `database/demos/template_cafe.sql` | Plantilla: slug `template-cafe`, tema `rosa` |
| `database/demos/migrate_to_prod.sql` | Template comentado para migrar prospecto convertido → BD prod |
| `scripts/create_demo.php` | CLI para crear demos forkeando una plantilla |
| `database/migrations/fase22_trial_system.sql` | Columna `trial_expires_at` en restaurantes |

---

## Crear una nueva demo

```bash
php scripts/create_demo.php \
  --template=taqueria \
  --slug=tacos-garcia \
  --nombre="Tacos García" \
  --whatsapp=9611234567 \
  --email=garcia@demo.com \
  --pass=demo1234 \
  --days=7
```

El script conecta directamente a `nodosmxc_menu_demos` (hardcodeado en el script).
Rubros disponibles: `taqueria | burgers | pizza | mariscos | cafe`

Output:
```
✅ Demo creada exitosamente
   Menú público: https://taqueria.nodosmx.com/menu/?r=tacos-garcia
   Admin:        https://taqueria.nodosmx.com/menu/admin
   Usuario:      garcia@demo.com
   Pass:         demo1234
   Expira:       27/04/2026 (7 días)
```

**Tiempo por nueva demo: ~2 minutos.**

---

## Sistema de trial

`restaurantes.trial_expires_at TIMESTAMP NULL`:
- `NULL` → sin restricción (plantillas o clientes reales)
- Fecha pasada → trial vencido
- Fecha futura → trial activo

### Frontend

- `TrialBanner.vue` (sticky en menú público):
  - Trial vencido → banner rojo + botón "Contratar →" (WA: `529231311146`)
  - ≤2 días restantes → banner amarillo
  - Activo + >2 días → no renderiza

- `modoLectura` en `MenuPublico.vue` se activa cuando `!trialActivo` (sin carrito, sin botones "+")

- `Dashboard.vue`:
  - Trial vencido → overlay `<Teleport to="body">` bloqueante con botón WA
  - ≤2 días → aviso amarillo en header del panel

### API (`GET menu`)

```php
'trial_activo'         => $rows[0]['trial_expires_at'] === null
                          || strtotime($rows[0]['trial_expires_at']) > time(),
'trial_dias_restantes' => ...  // int o null
```

### Simular trial vencido (pruebas)

```sql
UPDATE restaurantes SET trial_expires_at = '2020-01-01' WHERE slug = 'tacos-prueba';
-- Restaurar:
UPDATE restaurantes SET trial_expires_at = DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE slug = 'tacos-prueba';
```

---

## Slug persistente entre recargas

`MenuPublico.vue` guarda el slug en `sessionStorage` al primer load con `?r=`:
```js
if (slug) sessionStorage.setItem('menu_slug', slug)
```
En recargas posteriores (visibilitychange, F5), el slug se recupera de `sessionStorage` aunque la URL ya esté limpia.

`menuUrl` en Dashboard incluye `?r=slug` para que el link compartido al prospecto cargue su restaurante específico.

---

## Imágenes de productos

Los templates SQL referencian rutas como `demos/taco_pastor.jpg`. Estas imágenes deben existir en:
- **Servidor**: `public_html/demos/menu/uploads/demos/`
- **Local (demo_local)**: `c:/xampp/htdocs/menu_qr_3d/uploads/demos/`

Para pruebas locales sin imágenes:
```sql
UPDATE productos SET foto_principal = NULL;  -- en nodosmxc_menu_demos
```

---

## Migrar prospecto convertido a prod

Ver `database/demos/migrate_to_prod.sql` — template comentado paso a paso:
1. Backup de ambas BDs
2. Exportar restaurante + datos de demo DB
3. Ajustar IDs (AUTO_INCREMENT independientes entre BDs)
4. Importar en prod DB con `trial_expires_at = NULL`
5. Limpiar slot en demo DB con `DELETE FROM restaurantes WHERE id = X`
