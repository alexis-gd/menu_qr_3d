# docs/bd-migraciones.md — Migraciones e inicialización

## Seed inicial (BD nueva)

```sql
-- Usuario superadmin
INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (
  'Admin Principal', 'admin@tudominio.com',
  '$2y$10$REEMPLAZAR_CON_HASH_REAL',  -- echo password_hash('Pass', PASSWORD_DEFAULT)
  'superadmin'
);

-- Restaurante de prueba
INSERT INTO restaurantes (usuario_id, slug, nombre, descripcion, color_primario) VALUES (
  1, 'restaurante-demo', 'Restaurante Demo', 'Menú de demostración', '#FF6B35'
);
```

---

## Historial de migraciones

Orden obligatorio de aplicación: **local → QA → prod**. Ver [deploy.md](deploy.md).

| Archivo | Qué agrega |
|---|---|
| `fase7_personalizacion.sql` | `producto_grupos`, `producto_opciones`, `pedido_item_opciones`, cols en `productos` |
| `fase8_iconos_mdi.sql` | `categorias.emoji` → `icono VARCHAR(100)` |
| `fase9_envio_gratis.sql` | `restaurantes.pedidos_envio_gratis_desde` |
| `fase9b_terminal_domicilio.sql` | `restaurantes.pedidos_terminal_activo`, ENUM `metodo_pago` + terminal |
| `fase10_estado_tienda.sql` | `restaurantes.tienda_cerrada_manual`, `tienda_horarios JSON` |
| `fase11_recompensas_referidos.sql` | `recompensas_config`, `clientes` |
| `fase12_stock_minimo_aviso.sql` | `restaurantes.stock_minimo_aviso` |
| `fase13_codigos_promo.sql` | `codigos_promo` |
| `fase14_pedidos_descuentos.sql` | `pedidos.descuento_recompensa`, `descuento_promo`, `codigo_promo` |
| `fase15_codigos_promo_habilitado.sql` | `restaurantes.codigos_promo_habilitado` |
| `fase16_backfill_clientes.sql` | Backfill `clientes.total_compras` desde historial de pedidos |
| `fase17_pedido_contada_recompensa.sql` | `pedidos.contada_en_recompensas` |
| `fase18_codigo_promo_usos_maximo.sql` | `codigos_promo.usos_maximo`, `telefono_restringido` |
| `fase19a_folio_no_secuencial.sql` | `pedidos.numero_pedido` formato `YYYYMMDD-XXXX` + UNIQUE index |
| `fase19b_cupon_envio.sql` | `codigos_promo.tipo ENUM` extendido + `envio_gratis` |
| `fase19c_ajuste_pedido.sql` | `pedidos.ajuste_manual`, `ajuste_nota` |
| `fase20a_popup_tienda_cerrada.sql` | `restaurantes.pedidos_programar_activo`, `pedidos.fecha_programada`, `hora_programada` |
| `fase21_push_subscriptions.sql` | `push_subscriptions` |
| `fase22_trial_system.sql` | `restaurantes.trial_expires_at TIMESTAMP NULL` |
| `fase23_qr_frase_wifi.sql` | `restaurantes.qr_frase`, `qr_frase_activa`, `qr_wifi_nombre`, `qr_wifi_clave`, `qr_wifi_activo` |

### Migraciones pendientes de aplicar en demo/QA

| Migración | BD demo local | QA |
|---|---|---|
| `fase22_trial_system.sql` | Aplicar en `nodosmxc_menu_demos` | ✅ |
| `fase23_qr_frase_wifi.sql` | Aplicar en `nodosmxc_menu_demos` | Pendiente |

### BD demo — archivos especiales

| Archivo | Propósito |
|---|---|
| `database/demos/init_demo_db.sql` | Schema completo para crear `nodosmxc_menu_demos` desde cero |
| `database/demos/template_taqueria.sql` | Plantilla restaurante calido — ejecutar UNA vez en demo DB |
| `database/demos/template_burgers.sql` | Plantilla oscuro |
| `database/demos/template_pizza.sql` | Plantilla moderno |
| `database/demos/template_mariscos.sql` | Plantilla calido |
| `database/demos/template_cafe.sql` | Plantilla rosa |
| `database/demos/migrate_to_prod.sql` | Template de migración prospecto → BD prod |
