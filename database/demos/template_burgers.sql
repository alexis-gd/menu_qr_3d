-- ============================================================
-- template_burgers.sql — Plantilla rubro: Burgers & Cortes
-- Ejecutar DESPUÉS de init_demo_db.sql
-- ============================================================

SET NAMES utf8mb4;

-- ── Usuario plantilla ─────────────────────────────────────────
INSERT IGNORE INTO usuarios (nombre, email, password_hash, rol)
VALUES ('Plantilla Burgers', 'template-burgers@demo.local',
        '$2y$10$demoPlaceholderHashNoUsable00000000000000000000000000000u', 'admin');

SET @uid_burg = LAST_INSERT_ID();
SELECT @uid_burg := id FROM usuarios WHERE email = 'template-burgers@demo.local';

-- ── Restaurante plantilla ─────────────────────────────────────
INSERT IGNORE INTO restaurantes (
  usuario_id, slug, nombre, descripcion, tema, activo,
  pedidos_activos, pedidos_envio_activo, pedidos_envio_costo,
  pedidos_envio_gratis_desde, pedidos_whatsapp,
  pedidos_trans_activo, pedidos_terminal_activo,
  codigos_promo_habilitado, stock_minimo_aviso,
  compartir_mensaje, trial_expires_at
) VALUES (
  @uid_burg, 'template-burgers', 'El Corte',
  'Las mejores hamburguesas artesanales y cortes de res. Carne al punto que tú quieras.',
  'oscuro', 1,
  1, 1, 50.00,
  400.00, '9611000000',
  1, 1,
  1, 5,
  '🍔 ¡Mira nuestra carta digital! Cortes y hamburguesas de primera.', NULL
);

SET @rid_burg = LAST_INSERT_ID();
SELECT @rid_burg := id FROM restaurantes WHERE slug = 'template-burgers';

-- ── Recompensas config ────────────────────────────────────────
INSERT IGNORE INTO recompensas_config (restaurante_id, activo, compras_necesarias, tipo, valor)
VALUES (@rid_burg, 1, 8, 'descuento_fijo', 50.00);

-- ── Categorías ────────────────────────────────────────────────
INSERT INTO categorias (restaurante_id, nombre, icono, orden) VALUES
  (@rid_burg, 'Hamburguesas',     'mdiHamburger',   1),
  (@rid_burg, 'Cortes',           'mdiFoodSteak',   2),
  (@rid_burg, 'Acompañamientos',  'mdiFoodVariant', 3),
  (@rid_burg, 'Bebidas',          'mdiGlassMugVariant', 4);

SET @cat_burgs  = (SELECT id FROM categorias WHERE restaurante_id = @rid_burg AND nombre = 'Hamburguesas');
SET @cat_corts  = (SELECT id FROM categorias WHERE restaurante_id = @rid_burg AND nombre = 'Cortes');
SET @cat_acomp  = (SELECT id FROM categorias WHERE restaurante_id = @rid_burg AND nombre = 'Acompañamientos');
SET @cat_bbs    = (SELECT id FROM categorias WHERE restaurante_id = @rid_burg AND nombre = 'Bebidas');

-- ── Productos — Hamburguesas ──────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, es_destacado, tiene_personalizacion, orden) VALUES
  (@cat_burgs, 'Hamburguesa Clásica',
   'Carne de res 180g, lechuga, tomate, cebolla y pepinillos en pan brioche tostado. Con papas incluidas.',
   130.00, 'demos/burger_clasica.jpg', 0, 0, 1),
  (@cat_burgs, 'Hamburguesa BBQ',
   'Carne 200g con salsa BBQ ahumada, queso cheddar, tocino crujiente y aros de cebolla. Con papas.',
   155.00, 'demos/burger_bbq.jpg', 1, 0, 2),
  (@cat_burgs, 'Hamburguesa Doble',
   'Doble carne 2x180g, doble queso, doble tocino. Para los que no se conforman con poco.',
   180.00, 'demos/burger_doble.jpg', 0, 0, 3),
  (@cat_burgs, 'Hamburguesa Hawaiana',
   'Carne 180g con piña asada, queso suizo, jamón y salsa teriyaki. Un clásico tropical.',
   150.00, 'demos/burger_hawaiana.jpg', 0, 0, 4);

-- ── Productos — Cortes ────────────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, es_destacado, tiene_personalizacion, orden) VALUES
  (@cat_corts, 'Arrachera 250g',
   'Arrachera marinada en chimichurri a las brasas. Con guarnición de papas y ensalada.',
   280.00, 'demos/corte_arrachera.jpg', 1, 0, 1),
  (@cat_corts, 'Sirloin 250g',
   'Corte de lomo fino, jugoso y tierno. Con papas fritas y chimichurri.',
   270.00, 'demos/corte_sirloin.jpg', 0, 0, 2),
  (@cat_corts, 'Ribeye 300g',
   'El rey de los cortes. Veteado natural, máximo sabor. Con papas y ensalada cesar.',
   390.00, 'demos/corte_ribeye.jpg', 1, 0, 3),
  (@cat_corts, 'T-Bone 400g',
   'Lo mejor de dos mundos: lomo y filete en un solo corte. Para los amantes de la carne.',
   430.00, 'demos/corte_tbone.jpg', 0, 0, 4);

-- ── Productos — Acompañamientos ───────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, orden) VALUES
  (@cat_acomp, 'Papas a la Francesa',
   'Papas fritas crujientes con sal de mar. Porción grande.',
   55.00, 'demos/papas.jpg', 1),
  (@cat_acomp, 'Aros de Cebolla',
   'Cebolla empanizada en tempura ligero. Crujientes por fuera, suaves por dentro.',
   65.00, 'demos/aros_cebolla.jpg', 2),
  (@cat_acomp, 'Ensalada César',
   'Lechuga romana, crutones, queso parmesano y aderezo césar casero.',
   75.00, 'demos/ensalada_cesar.jpg', 3);

-- ── Productos — Bebidas ───────────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, orden) VALUES
  (@cat_bbs, 'Refresco 355ml',
   'Coca-Cola, Sprite o Fanta en lata.',
   30.00, 'demos/refresco.jpg', 1),
  (@cat_bbs, 'Agua Mineral',
   'Agua mineral Peñafiel 600ml.',
   25.00, 'demos/agua_mineral.jpg', 2),
  (@cat_bbs, 'Cerveza Nacional',
   'Tecate, Corona o Modelo. Fría y bien servida.',
   50.00, 'demos/cerveza.jpg', 3),
  (@cat_bbs, 'Limonada Natural',
   'Limonada fresca exprimida al momento, con o sin gas.',
   40.00, 'demos/limonada.jpg', 4);

-- ── Personalización: 1 producto por categoría ─────────────────

-- Hamburguesas → Hamburguesa BBQ: Término + Extras
SET @prod_bbqburg = (SELECT id FROM productos WHERE categoria_id = @cat_burgs AND nombre = 'Hamburguesa BBQ');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_bbqburg;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_bbqburg, 'Término de la carne', 'radio',    1, 1, 1, 1),
  (@prod_bbqburg, 'Extras',              'checkbox', 0, 0, 3, 2);

SET @grp_burg1 = (SELECT id FROM producto_grupos WHERE producto_id = @prod_bbqburg AND nombre = 'Término de la carne');
SET @grp_burg2 = (SELECT id FROM producto_grupos WHERE producto_id = @prod_bbqburg AND nombre = 'Extras');

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_burg1, 'Al punto',     0.00, 1),
  (@grp_burg1, 'Tres cuartos', 0.00, 2),
  (@grp_burg1, 'Bien cocida',  0.00, 3);

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_burg2, 'Queso extra', 15.00, 1),
  (@grp_burg2, 'Tocino extra', 15.00, 2),
  (@grp_burg2, 'Jalapeños',    0.00, 3);

-- Cortes → Arrachera 250g: Término del corte
SET @prod_arrachera = (SELECT id FROM productos WHERE categoria_id = @cat_corts AND nombre = 'Arrachera 250g');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_arrachera;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_arrachera, 'Término del corte', 'radio', 1, 1, 1, 1);
SET @grp_burg3 = LAST_INSERT_ID();

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_burg3, 'Rojo',         0.00, 1),
  (@grp_burg3, 'Medio rojo',   0.00, 2),
  (@grp_burg3, 'Al punto',     0.00, 3),
  (@grp_burg3, 'Tres cuartos', 0.00, 4),
  (@grp_burg3, 'Bien cocido',  0.00, 5);

-- Acompañamientos → Papas a la Francesa: Dips
SET @prod_papas = (SELECT id FROM productos WHERE categoria_id = @cat_acomp AND nombre = 'Papas a la Francesa');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_papas;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_papas, 'Dips', 'checkbox', 0, 0, 2, 1);
SET @grp_burg4 = LAST_INSERT_ID();

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_burg4, 'Salsa BBQ',  0.00, 1),
  (@grp_burg4, 'Cheddar',   15.00, 2),
  (@grp_burg4, 'Ranch',      0.00, 3);

-- Bebidas → Cerveza Nacional: Marca
SET @prod_cerveza = (SELECT id FROM productos WHERE categoria_id = @cat_bbs AND nombre = 'Cerveza Nacional');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_cerveza;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_cerveza, 'Marca', 'radio', 1, 1, 1, 1);
SET @grp_burg5 = LAST_INSERT_ID();

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_burg5, 'Tecate', 0.00, 1),
  (@grp_burg5, 'Corona', 0.00, 2),
  (@grp_burg5, 'Modelo', 0.00, 3);
