-- ============================================================
-- template_cafe.sql — Plantilla rubro: Cafetería / Coffee Shop
-- Ejecutar DESPUÉS de init_demo_db.sql
-- ============================================================

SET NAMES utf8mb4;

-- ── Usuario plantilla ─────────────────────────────────────────
INSERT IGNORE INTO usuarios (nombre, email, password_hash, rol)
VALUES ('Plantilla Café', 'template-cafe@demo.local',
        '$2y$10$WVB2c93.E1xjxstJNDxsMemN2LRsvhBC9ptUAiawLeUkUE/AOmoWK', 'admin'); -- pass: demo1234

SET @uid_cafe = LAST_INSERT_ID();
SELECT @uid_cafe := id FROM usuarios WHERE email = 'template-cafe@demo.local';

-- ── Restaurante plantilla ─────────────────────────────────────
INSERT IGNORE INTO restaurantes (
  usuario_id, slug, nombre, descripcion, tema, activo,
  logo_url,
  pedidos_activos, pedidos_envio_activo, pedidos_envio_costo,
  pedidos_envio_gratis_desde, pedidos_whatsapp,
  pedidos_trans_activo, pedidos_terminal_activo,
  codigos_promo_habilitado, stock_minimo_aviso,
  compartir_mensaje, trial_expires_at
) VALUES (
  @uid_cafe, 'template-cafe', 'La Croissanterie',
  'Cafés de especialidad, frappés artesanales y deliciosos alimentos. Tu momento favorito del día.',
  'rosa', 1,
  'demos/logo_cafe.jpg',
  1, 0, 0,
  NULL, '9611000000',
  1, 1,
  1, 5,
  '☕ ¡Mira nuestra carta! Café de especialidad y antojitos irresistibles.', NULL
);

SET @rid_cafe = LAST_INSERT_ID();
SELECT @rid_cafe := id FROM restaurantes WHERE slug = 'template-cafe';

-- ── Recompensas config ────────────────────────────────────────
INSERT IGNORE INTO recompensas_config (restaurante_id, activo, compras_necesarias, tipo, valor)
VALUES (@rid_cafe, 1, 10, 'descuento_fijo', 40.00);

-- ── Categorías ────────────────────────────────────────────────
INSERT INTO categorias (restaurante_id, nombre, icono, orden) VALUES
  (@rid_cafe, 'Cafés',    'mdiCoffee',       1),
  (@rid_cafe, 'Frappés',  'mdiCupWater',     2),
  (@rid_cafe, 'Alimentos','mdiFoodVariant',  3),
  (@rid_cafe, 'Postres',  'mdiCakeVariant',  4);

SET @cat_cafes  = (SELECT id FROM categorias WHERE restaurante_id = @rid_cafe AND nombre = 'Cafés');
SET @cat_fraps  = (SELECT id FROM categorias WHERE restaurante_id = @rid_cafe AND nombre = 'Frappés');
SET @cat_alims  = (SELECT id FROM categorias WHERE restaurante_id = @rid_cafe AND nombre = 'Alimentos');
SET @cat_posts  = (SELECT id FROM categorias WHERE restaurante_id = @rid_cafe AND nombre = 'Postres');

-- ── Productos — Cafés ─────────────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, es_destacado, tiene_personalizacion, orden) VALUES
  (@cat_cafes, 'Americano',
   'Espresso doble con agua caliente. Limpio y directo, para empezar el día.',
   38.00, 'demos/cafe_americano.jpg', 0, 0, 1),
  (@cat_cafes, 'Cappuccino',
   'Espresso con leche vaporizada y espuma cremosa. Clásico italiano.',
   52.00, 'demos/cafe_americano.jpg', 1, 0, 2),
  (@cat_cafes, 'Latte de Vainilla',
   'Espresso con leche vaporizada y jarabe de vainilla. Suave y aromático.',
   62.00, 'demos/cafe_americano.jpg', 0, 0, 3),
  (@cat_cafes, 'Mocha',
   'Espresso con chocolate belga y leche vaporizada. El placer de dos mundos.',
   65.00, 'demos/cafe_americano.jpg', 0, 0, 4),
  (@cat_cafes, 'Café de Olla',
   'Café de olla tradicional con canela y piloncillo. Como lo hacía la abuela.',
   32.00, 'demos/cafe_americano.jpg', 0, 0, 5),
  (@cat_cafes, 'Cold Brew',
   'Café extraído en frío por 12 horas. Suave, concentrado y sin amargor. Con hielo.',
   68.00, 'demos/cafe_americano.jpg', 1, 0, 6);

-- ── Productos — Frappés ───────────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, es_destacado, tiene_personalizacion, orden) VALUES
  (@cat_fraps, 'Frappé de Oreo',
   'Café, leche, Oreo triturada y crema batida. El favorito de todos.',
   78.00, 'demos/frappe_oreo.jpg', 1, 0, 1),
  (@cat_fraps, 'Frappé de Nutella',
   'Café, leche, Nutella y crema batida con avellanas. Indulgencia pura.',
   82.00, 'demos/frappe_oreo.jpg', 0, 0, 2),
  (@cat_fraps, 'Frappé de Caramelo',
   'Café, leche, sirope de caramelo y crema batida. Dulce y cremoso.',
   75.00, 'demos/frappe_oreo.jpg', 0, 0, 3),
  (@cat_fraps, 'Frappé Matcha',
   'Matcha japonés, leche de almendra y crema batida. Para los aventureros del sabor.',
   80.00, 'demos/frappe_oreo.jpg', 0, 0, 4);

-- ── Productos — Alimentos ─────────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, es_destacado, orden) VALUES
  (@cat_alims, 'Sándwich Club',
   'Pan ciabatta con pollo a la plancha, tocino, lechuga, tomate y mayonesa de chipotle.',
   95.00, 'demos/sandwich_club.jpg', 1, 1),
  (@cat_alims, 'Croissant de Jamón y Queso',
   'Croissant francés recién horneado con jamón serrano y queso gouda. Con ensalada.',
   80.00, 'demos/sandwich_club.jpg', 0, 2),
  (@cat_alims, 'Waffles con Fruta',
   'Waffles esponjosos con fresas, plátano, arándanos y miel de maple. Delicioso brunch.',
   98.00, 'demos/sandwich_club.jpg', 0, 3),
  (@cat_alims, 'Avocado Toast',
   'Pan artesanal tostado con aguacate, huevo pochado, semillas y flor de sal.',
   90.00, 'demos/sandwich_club.jpg', 0, 4);

-- ── Productos — Postres ───────────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, es_destacado, orden) VALUES
  (@cat_posts, 'Cheesecake de Fresa',
   'Base de galleta, crema de queso suave y coulis de fresa fresca. Rebanada.',
   70.00, 'demos/tiramisu.jpg', 1, 1),
  (@cat_posts, 'Brownie de Chocolate',
   'Brownie húmedo con chips de chocolate semiamargo y nuez. Tibio con nieve de vainilla.',
   60.00, 'demos/tiramisu.jpg', 0, 2),
  (@cat_posts, 'Tiramisú',
   'El clásico italiano con mascarpone, espresso y cacao en polvo. Porción individual.',
   75.00, 'demos/tiramisu.jpg', 0, 3),
  (@cat_posts, 'Cinnamon Roll',
   'Rollo de canela recién horneado con glaseado de queso crema. Tibio y esponjoso.',
   55.00, 'demos/tiramisu.jpg', 0, 4);

-- ── Personalización: 1 producto por categoría ─────────────────

-- Cafés → Cappuccino: Tamaño + Tipo de leche
SET @prod_capp = (SELECT id FROM productos WHERE categoria_id = @cat_cafes AND nombre = 'Cappuccino');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_capp;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_capp, 'Tamaño',        'radio', 1, 1, 1, 1),
  (@prod_capp, 'Tipo de leche', 'radio', 1, 1, 1, 2);

SET @grp_cafe1 = (SELECT id FROM producto_grupos WHERE producto_id = @prod_capp AND nombre = 'Tamaño');
SET @grp_cafe2 = (SELECT id FROM producto_grupos WHERE producto_id = @prod_capp AND nombre = 'Tipo de leche');

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_cafe1, 'Chico (8oz)',    0.00, 1),
  (@grp_cafe1, 'Grande (12oz)', 12.00, 2);

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_cafe2, 'Entera',       0.00, 1),
  (@grp_cafe2, 'Deslactosada', 0.00, 2),
  (@grp_cafe2, 'Almendra',     8.00, 3),
  (@grp_cafe2, 'Avena',        8.00, 4);

-- Frappés → Frappé de Oreo: Tamaño
SET @prod_oreo = (SELECT id FROM productos WHERE categoria_id = @cat_fraps AND nombre = 'Frappé de Oreo');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_oreo;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_oreo, 'Tamaño', 'radio', 1, 1, 1, 1);
SET @grp_cafe3 = LAST_INSERT_ID();

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_cafe3, 'Regular (16oz)',  0.00, 1),
  (@grp_cafe3, 'Grande (24oz)',  15.00, 2);

-- Alimentos → Sándwich Club: Tipo de pan
SET @prod_sandwich = (SELECT id FROM productos WHERE categoria_id = @cat_alims AND nombre = 'Sándwich Club');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_sandwich;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_sandwich, 'Tipo de pan', 'radio', 1, 1, 1, 1);
SET @grp_cafe4 = LAST_INSERT_ID();

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_cafe4, 'Ciabatta', 0.00, 1),
  (@grp_cafe4, 'Integral', 0.00, 2),
  (@grp_cafe4, 'Baguette', 0.00, 3);

-- Postres → Cheesecake de Fresa: Acompañamiento
SET @prod_cheesecake = (SELECT id FROM productos WHERE categoria_id = @cat_posts AND nombre = 'Cheesecake de Fresa');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_cheesecake;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_cheesecake, 'Acompañamiento', 'checkbox', 0, 0, 2, 1);
SET @grp_cafe5 = LAST_INSERT_ID();

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_cafe5, 'Nieve de vainilla',  20.00, 1),
  (@grp_cafe5, 'Crema batida',        0.00, 2),
  (@grp_cafe5, 'Fresas adicionales', 15.00, 3);
