-- ============================================================
-- migrate_to_prod.sql — Template para migrar un prospecto convertido
-- de la BD demo (nodosmxc_menu_demos) a la BD prod (nodosmxc_menu_qr_3d)
--
-- ANTES DE EJECUTAR:
--   1. Hacer backup de ambas BDs
--   2. Identificar el restaurante_id del prospecto en demo DB
--   3. Ajustar las variables al inicio de este archivo
--   4. Ejecutar secciones en orden: primero en demo DB, luego en prod DB
--
-- NOTAS:
--   - Los IDs en prod pueden diferir de los IDs en demo (AUTO_INCREMENT independientes)
--   - Se usa mysqldump para el export y se re-asignan IDs en el import
--   - El usuario del prospecto se crea nuevo en prod (password_hash se copia)
-- ============================================================

-- ── VARIABLES A AJUSTAR ──────────────────────────────────────
-- SET @DEMO_RESTAURANTE_ID = X;   -- ID del restaurante en la BD demo
-- SET @DEMO_SLUG = 'tacos-garcia'; -- Slug del prospecto en demo

-- ============================================================
-- PASO 1 — En la BD DEMO: exportar datos
-- Ejecutar en terminal (fuera de phpMyAdmin):
-- ============================================================
/*
# Exportar restaurante y todos sus datos relacionados:
mysqldump nodosmxc_menu_demos \
  --where="id=@DEMO_RESTAURANTE_ID" \
  restaurantes \
  > /tmp/export_restaurante.sql

mysqldump nodosmxc_menu_demos \
  --where="restaurante_id=@DEMO_RESTAURANTE_ID" \
  categorias recompensas_config clientes codigos_promo \
  > /tmp/export_relaciones.sql

# Para productos (via categorías — JOIN necesario):
# Usar phpMyAdmin → Exportar con WHERE custom, o el query de abajo
*/

-- ── QUERY para obtener todos los productos del prospecto ────
/*
SELECT p.*
FROM productos p
JOIN categorias c ON c.id = p.categoria_id
WHERE c.restaurante_id = @DEMO_RESTAURANTE_ID
  AND p.activo = 1;
-- Copiar el resultado como INSERT statements en phpMyAdmin (Export → SQL)
*/

-- ── QUERY para obtener el usuario del prospecto ─────────────
/*
SELECT u.*
FROM usuarios u
JOIN restaurantes r ON r.usuario_id = u.id
WHERE r.id = @DEMO_RESTAURANTE_ID;
*/

-- ============================================================
-- PASO 2 — En la BD PROD: importar con IDs ajustados
-- ============================================================
-- Abrir los archivos exportados y:
--   a) Cambiar todos los `restaurante_id = X` por el nuevo ID en prod
--   b) Cambiar todos los `categoria_id = Y` por los nuevos IDs en prod
--   c) Quitar `trial_expires_at` del INSERT de restaurantes (o poner NULL)
--   d) Ajustar UPLOADS_URL en foto_principal si las fotos se mueven

-- ── Crear usuario en prod ───────────────────────────────────
/*
INSERT INTO usuarios (nombre, email, password_hash, rol)
VALUES ('Nombre del Cliente', 'email@cliente.com', '<password_hash_del_export>', 'admin');
-- Guardar el nuevo usuario_id
SET @PROD_USUARIO_ID = LAST_INSERT_ID();
*/

-- ── Crear restaurante en prod (SIN trial_expires_at) ────────
/*
INSERT INTO restaurantes (
  usuario_id, slug, nombre, descripcion, tema, activo,
  pedidos_activos, pedidos_envio_activo, pedidos_envio_costo,
  pedidos_envio_gratis_desde, pedidos_whatsapp,
  pedidos_trans_activo, pedidos_terminal_activo,
  codigos_promo_habilitado, stock_minimo_aviso,
  compartir_mensaje, trial_expires_at
) VALUES (
  @PROD_USUARIO_ID, '<slug-final>', '<nombre>', '<descripcion>',
  '<tema>', 1,
  <pedidos_activos>, <envio_activo>, <envio_costo>,
  <envio_gratis_desde>, '<whatsapp>',
  <trans_activo>, <terminal_activo>,
  <promo_hab>, <stock_min>,
  '<compartir_mensaje>',
  NULL   -- SIN trial en producción
);
SET @PROD_RESTAURANTE_ID = LAST_INSERT_ID();
*/

-- ── Insertar categorías con nuevo restaurante_id ─────────────
-- (copiar de export, reemplazar restaurante_id=@DEMO por @PROD)

-- ── Insertar productos con nuevos categoria_id ───────────────
-- (copiar de export, reemplazar categoria_id=OLD por NEW según mapa)

-- ── Insertar recompensas_config ──────────────────────────────
/*
INSERT INTO recompensas_config (restaurante_id, activo, compras_necesarias, tipo, valor)
VALUES (@PROD_RESTAURANTE_ID, <activo>, <compras>, '<tipo>', <valor>);
*/

-- ============================================================
-- PASO 3 — En la BD DEMO: limpiar el slot del prospecto
-- (solo ejecutar DESPUÉS de verificar que prod funciona correctamente)
-- ============================================================
/*
-- Esto borra en cascada: categorias, productos, pedidos, clientes, etc.
DELETE FROM restaurantes WHERE id = @DEMO_RESTAURANTE_ID;
DELETE FROM usuarios WHERE email = '<email_del_prospecto>';
*/

-- ============================================================
-- PASO 4 — Actualizar la carpeta del prospecto en el servidor
-- ============================================================
/*
-- La URL del menú en prod puede ser:
--   nodosmx.com/menu/?r=<slug-final>
-- O si el cliente tiene su propio dominio:
--   mirestaurante.com/menu/
--
-- Si se mueven fotos de demos/uploads/ a menu/uploads/:
-- Actualizar foto_principal en prod a la ruta sin prefijo 'demos/'
UPDATE productos p
JOIN categorias c ON c.id = p.categoria_id
SET p.foto_principal = REPLACE(p.foto_principal, 'demos/', '')
WHERE c.restaurante_id = @PROD_RESTAURANTE_ID
  AND p.foto_principal LIKE 'demos/%';
*/
