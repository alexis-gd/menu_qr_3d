-- ============================================================
-- template_taqueria.sql — Plantilla rubro: Taquería / Antojitos
-- Ejecutar DESPUÉS de init_demo_db.sql
-- trial_expires_at = NULL → esta es una plantilla, no un demo activo
-- El script create_demo.php la copia para cada prospecto
-- ============================================================

SET NAMES utf8mb4;

-- ── Usuario plantilla ─────────────────────────────────────────
INSERT IGNORE INTO usuarios (nombre, email, password_hash, rol)
VALUES ('Plantilla Taquería', 'template-taqueria@demo.local',
        '$2y$10$demoPlaceholderHashNoUsable00000000000000000000000000000u', 'admin');

SET @uid_taq = LAST_INSERT_ID();
-- Si ya existía (IGNORE), recuperar el ID
SELECT @uid_taq := id FROM usuarios WHERE email = 'template-taqueria@demo.local';

-- ── Restaurante plantilla ─────────────────────────────────────
INSERT IGNORE INTO restaurantes (
  usuario_id, slug, nombre, descripcion, tema, activo,
  pedidos_activos, pedidos_envio_activo, pedidos_envio_costo,
  pedidos_envio_gratis_desde, pedidos_whatsapp,
  pedidos_trans_activo, pedidos_terminal_activo,
  codigos_promo_habilitado, stock_minimo_aviso,
  compartir_mensaje, trial_expires_at
) VALUES (
  @uid_taq, 'template-taqueria', 'La Taquería',
  'Auténticos tacos y antojitos mexicanos. ¡El sabor de siempre!',
  'calido', 1,
  1, 1, 30.00,
  200.00, '9611000000',
  1, 0,
  1, 5,
  '¡Mira nuestro menú digital! 🌮', NULL
);

SET @rid_taq = LAST_INSERT_ID();
SELECT @rid_taq := id FROM restaurantes WHERE slug = 'template-taqueria';

-- ── Recompensas config ────────────────────────────────────────
INSERT IGNORE INTO recompensas_config (restaurante_id, activo, compras_necesarias, tipo, valor)
VALUES (@rid_taq, 1, 10, 'descuento_porcentaje', 10.00);

-- ── Categorías ────────────────────────────────────────────────
INSERT INTO categorias (restaurante_id, nombre, icono, orden) VALUES
  (@rid_taq, 'Tacos',       'mdiTaco',       1),
  (@rid_taq, 'Quesadillas', 'mdiFoodVariant', 2),
  (@rid_taq, 'Bebidas',     'mdiCupWater',   3),
  (@rid_taq, 'Extras',      'mdiStar',       4);

SET @cat_tacos  = (SELECT id FROM categorias WHERE restaurante_id = @rid_taq AND nombre = 'Tacos');
SET @cat_qsas   = (SELECT id FROM categorias WHERE restaurante_id = @rid_taq AND nombre = 'Quesadillas');
SET @cat_bebs   = (SELECT id FROM categorias WHERE restaurante_id = @rid_taq AND nombre = 'Bebidas');
SET @cat_extras = (SELECT id FROM categorias WHERE restaurante_id = @rid_taq AND nombre = 'Extras');

-- ── Productos — Tacos ─────────────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, es_destacado, orden) VALUES
  (@cat_tacos, 'Taco de Bistec',
   'Bistec de res asado a las brasas, cebolla y cilantro. Con tortilla de maíz.',
   25.00, 'demos/taco_pastor.jpg', 1, 1),
  (@cat_tacos, 'Taco al Pastor',
   'Cerdo marinado en achiote con piña, cebolla y cilantro.',
   22.00, 'demos/taco_pastor.jpg', 1, 2),
  (@cat_tacos, 'Taco de Chorizo',
   'Chorizo artesanal con papas doradas, cebolla y salsa verde.',
   22.00, 'demos/taco_pastor.jpg', 0, 3),
  (@cat_tacos, 'Taco de Pollo',
   'Pechuga de pollo a la plancha con chile, cebolla morada y limón.',
   22.00, 'demos/taco_pastor.jpg', 0, 4),
  (@cat_tacos, 'Taco de Canasta',
   'El clásico taco de canasta: frijol, chicharrón o papa. Precio por pieza.',
   15.00, 'demos/taco_pastor.jpg', 0, 5),
  (@cat_tacos, 'Taco de Nopales',
   'Nopales asados con queso panela, cebolla y chile jalapeño. Opción vegetariana.',
   20.00, 'demos/taco_pastor.jpg', 0, 6);

-- ── Productos — Quesadillas ───────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, es_destacado, orden) VALUES
  (@cat_qsas, 'Quesadilla Sencilla',
   'Tortilla de harina con queso Oaxaca derretido. La clásica.',
   35.00, 'demos/quesadilla_sencilla.jpg', 0, 1),
  (@cat_qsas, 'Quesadilla con Bistec',
   'Tortilla de harina rellena de bistec y queso Oaxaca. Incluye crema y guacamole.',
   65.00, 'demos/quesadilla_sencilla.jpg', 1, 2),
  (@cat_qsas, 'Quesadilla con Pollo',
   'Tortilla de harina con pollo a la plancha y queso Oaxaca. Incluye crema.',
   60.00, 'demos/quesadilla_sencilla.jpg', 0, 3),
  (@cat_qsas, 'Gordita de Chicharrón',
   'Masa de maíz rellena de chicharrón prensado con salsa roja. Bien frita.',
   35.00, 'demos/quesadilla_sencilla.jpg', 0, 4);

-- ── Productos — Bebidas ───────────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, orden) VALUES
  (@cat_bebs, 'Agua Fresca',
   'Agua fresca del día: Jamaica, Horchata o Limón. Vaso grande.',
   25.00, 'demos/agua_fresca.jpg', 1),
  (@cat_bebs, 'Refresco 600ml',
   'Coca-Cola, Sprite o Mundet en botella.',
   25.00, 'demos/refresco.jpg', 2),
  (@cat_bebs, 'Pozol Choco',
   'Tradicional bebida chiapaneca de cacao y maíz. Frío o natural.',
   30.00, 'demos/pozol.jpg', 3);

-- ── Productos — Extras ────────────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, orden) VALUES
  (@cat_extras, 'Guacamole',
   'Aguacate fresco molcajeteado con cilantro, cebolla y limón. Porción para 2.',
   35.00, 'demos/guacamole.jpg', 1),
  (@cat_extras, 'Salsa Verde / Roja',
   'Salsas caseras de molcajete. Especifica el picor: suave, medio o picante.',
   10.00, 'demos/guacamole.jpg', 2);

-- ── Personalización: 1 producto por categoría ─────────────────

-- Tacos → Taco al Pastor: ¿Cuántos tacos?
SET @prod_pastor = (SELECT id FROM productos WHERE categoria_id = @cat_tacos AND nombre = 'Taco al Pastor');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_pastor;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_pastor, '¿Cuántos tacos?', 'radio', 1, 1, 1, 1);
SET @grp_taq1 = LAST_INSERT_ID();

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_taq1, 'Orden (3 tacos)',         0.00, 1),
  (@grp_taq1, 'Media orden (5 tacos)',  20.00, 2),
  (@grp_taq1, 'Orden grande (8 tacos)', 45.00, 3);

-- Quesadillas → Quesadilla con Bistec: Tipo de tortilla
SET @prod_qsabistec = (SELECT id FROM productos WHERE categoria_id = @cat_qsas AND nombre = 'Quesadilla con Bistec');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_qsabistec;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_qsabistec, 'Tipo de tortilla', 'radio', 1, 1, 1, 1);
SET @grp_taq2 = LAST_INSERT_ID();

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_taq2, 'Harina', 0.00, 1),
  (@grp_taq2, 'Maíz',   0.00, 2);

-- Bebidas → Agua Fresca: Sabor
SET @prod_agua = (SELECT id FROM productos WHERE categoria_id = @cat_bebs AND nombre = 'Agua Fresca');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_agua;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_agua, 'Sabor', 'radio', 1, 1, 1, 1);
SET @grp_taq3 = LAST_INSERT_ID();

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_taq3, 'Jamaica',  0.00, 1),
  (@grp_taq3, 'Horchata', 0.00, 2),
  (@grp_taq3, 'Limón',    0.00, 3);

-- Extras → Salsa Verde / Roja: Nivel de picor
SET @prod_salsa = (SELECT id FROM productos WHERE categoria_id = @cat_extras AND nombre = 'Salsa Verde / Roja');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_salsa;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_salsa, 'Nivel de picor', 'radio', 1, 1, 1, 1);
SET @grp_taq4 = LAST_INSERT_ID();

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_taq4, 'Suave',   0.00, 1),
  (@grp_taq4, 'Medio',   0.00, 2),
  (@grp_taq4, 'Picante', 0.00, 3);
