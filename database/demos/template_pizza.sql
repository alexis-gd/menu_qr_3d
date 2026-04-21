-- ============================================================
-- template_pizza.sql — Plantilla rubro: Pizzería
-- Ejecutar DESPUÉS de init_demo_db.sql
-- ============================================================

SET NAMES utf8mb4;

-- ── Usuario plantilla ─────────────────────────────────────────
INSERT IGNORE INTO usuarios (nombre, email, password_hash, rol)
VALUES ('Plantilla Pizza', 'template-pizza@demo.local',
        '$2y$10$WVB2c93.E1xjxstJNDxsMemN2LRsvhBC9ptUAiawLeUkUE/AOmoWK', 'admin'); -- pass: demo1234

SET @uid_piz = LAST_INSERT_ID();
SELECT @uid_piz := id FROM usuarios WHERE email = 'template-pizza@demo.local';

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
  @uid_piz, 'template-pizza', 'Pizzería Da Romano',
  'Pizzas artesanales al horno de leña. Masa madre, ingredientes frescos y el sabor de Italia.',
  'moderno', 1,
  'demos/logo_pizza.jpg',
  1, 1, 40.00,
  350.00, '9611000000',
  1, 0,
  1, 5,
  '🍕 ¡Pide tu pizza favorita en línea! Entrega a domicilio disponible.', NULL
);

SET @rid_piz = LAST_INSERT_ID();
SELECT @rid_piz := id FROM restaurantes WHERE slug = 'template-pizza';

-- ── Recompensas config ────────────────────────────────────────
INSERT IGNORE INTO recompensas_config (restaurante_id, activo, compras_necesarias, tipo, valor)
VALUES (@rid_piz, 1, 10, 'descuento_porcentaje', 15.00);

-- ── Categorías ────────────────────────────────────────────────
INSERT INTO categorias (restaurante_id, nombre, icono, orden) VALUES
  (@rid_piz, 'Pizzas',    'mdiPizza',        1),
  (@rid_piz, 'Calzones',  'mdiFoodVariant',  2),
  (@rid_piz, 'Entradas',  'mdiBone',         3),
  (@rid_piz, 'Bebidas',   'mdiCupWater',     4);

SET @cat_pizzas  = (SELECT id FROM categorias WHERE restaurante_id = @rid_piz AND nombre = 'Pizzas');
SET @cat_calz    = (SELECT id FROM categorias WHERE restaurante_id = @rid_piz AND nombre = 'Calzones');
SET @cat_entrad  = (SELECT id FROM categorias WHERE restaurante_id = @rid_piz AND nombre = 'Entradas');
SET @cat_bebspiz = (SELECT id FROM categorias WHERE restaurante_id = @rid_piz AND nombre = 'Bebidas');

-- ── Productos — Pizzas ────────────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, es_destacado, tiene_personalizacion, orden) VALUES
  (@cat_pizzas, 'Margarita Chica (25cm)',
   'La clásica italiana: salsa de tomate San Marzano, mozzarella fresca y albahaca.',
   120.00, 'demos/pizza_margarita.jpg', 0, 0, 1),
  (@cat_pizzas, 'Margarita Grande (35cm)',
   'La clásica italiana en tamaño familiar: salsa de tomate San Marzano, mozzarella fresca y albahaca.',
   190.00, 'demos/pizza_margarita.jpg', 0, 0, 2),
  (@cat_pizzas, 'Pizza BBQ Pollo Chica (25cm)',
   'Pollo ahumado, tocino, cebolla morada, salsa BBQ y queso mozzarella.',
   150.00, 'demos/pizza_margarita.jpg', 1, 0, 3),
  (@cat_pizzas, 'Pizza BBQ Pollo Grande (35cm)',
   'Pollo ahumado, tocino, cebolla morada, salsa BBQ y queso mozzarella. Tamaño familiar.',
   230.00, 'demos/pizza_margarita.jpg', 0, 0, 4),
  (@cat_pizzas, 'Cuatro Quesos Chica (25cm)',
   'Mozzarella, gouda, parmesano y queso azul. Para los amantes del queso.',
   145.00, 'demos/pizza_margarita.jpg', 0, 0, 5),
  (@cat_pizzas, 'Cuatro Quesos Grande (35cm)',
   'Mozzarella, gouda, parmesano y queso azul. Tamaño familiar.',
   215.00, 'demos/pizza_margarita.jpg', 0, 0, 6),
  (@cat_pizzas, 'Pepperoni Grande (35cm)',
   'Salsa de tomate, mozzarella y pepperoni importado. El favorito de todos.',
   210.00, 'demos/pizza_margarita.jpg', 1, 0, 7);

-- ── Productos — Calzones ──────────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, es_destacado, orden) VALUES
  (@cat_calz, 'Calzone de Jamón y Queso',
   'Masa rellena de jamón serrano, mozzarella y ricotta. Servido con salsa marinara.',
   165.00, 'demos/calzone_jamon.jpg', 0, 1),
  (@cat_calz, 'Calzone Supremo',
   'Pepperoni, champiñones, pimiento, aceitunas y doble queso. El más completo.',
   190.00, 'demos/calzone_jamon.jpg', 1, 2);

-- ── Productos — Entradas ──────────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, orden) VALUES
  (@cat_entrad, 'Pan de Ajo',
   'Pan artesanal tostado con mantequilla de ajo y hierbas finas. 6 piezas.',
   60.00, 'demos/alitas.jpg', 1),
  (@cat_entrad, 'Alitas BBQ (8 piezas)',
   'Alitas de pollo horneadas y glaseadas con salsa BBQ ahumada.',
   95.00, 'demos/alitas.jpg', 2),
  (@cat_entrad, 'Ensalada César',
   'Lechuga romana, crutones de ajo, parmesano y aderezo césar casero.',
   75.00, 'demos/alitas.jpg', 3);

-- ── Productos — Bebidas ───────────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, orden) VALUES
  (@cat_bebspiz, 'Coca-Cola 2L',
   'Para compartir en familia. Bien fría.',
   55.00, 'demos/refresco.webp', 1),
  (@cat_bebspiz, 'Jugo Natural 500ml',
   'Naranja, zanahoria o mixto. Recién exprimido.',
   45.00, 'demos/refresco.webp', 2),
  (@cat_bebspiz, 'Agua Mineral 600ml',
   'Peñafiel sin gas o con gas.',
   25.00, 'demos/refresco.webp', 3);

-- ── Personalización: 1 producto por categoría ─────────────────

-- Pizzas → Pizza BBQ Pollo Chica (25cm): Tipo de orilla
SET @prod_bbqpiz = (SELECT id FROM productos WHERE categoria_id = @cat_pizzas AND nombre = 'Pizza BBQ Pollo Chica (25cm)');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_bbqpiz;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_bbqpiz, 'Tipo de orilla', 'radio', 1, 1, 1, 1);
SET @grp_piz1 = LAST_INSERT_ID();

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_piz1, 'Normal',           0.00, 1),
  (@grp_piz1, 'Orilla de queso', 25.00, 2),
  (@grp_piz1, 'Orilla de ajo',   15.00, 3);

-- Calzones → Calzone Supremo: Salsa para acompañar
SET @prod_calzone = (SELECT id FROM productos WHERE categoria_id = @cat_calz AND nombre = 'Calzone Supremo');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_calzone;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_calzone, 'Salsa para acompañar', 'radio', 1, 1, 1, 1);
SET @grp_piz2 = LAST_INSERT_ID();

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_piz2, 'Marinara',   0.00, 1),
  (@grp_piz2, 'Ranch',      0.00, 2),
  (@grp_piz2, 'Arrabbiata', 0.00, 3);

-- Entradas → Alitas BBQ (8 piezas): Salsa
SET @prod_alitas = (SELECT id FROM productos WHERE categoria_id = @cat_entrad AND nombre = 'Alitas BBQ (8 piezas)');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_alitas;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_alitas, 'Salsa', 'radio', 1, 1, 1, 1);
SET @grp_piz3 = LAST_INSERT_ID();

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_piz3, 'BBQ',        0.00, 1),
  (@grp_piz3, 'Buffalo',    0.00, 2),
  (@grp_piz3, 'Mango-Chile', 0.00, 3);

-- Bebidas → Jugo Natural 500ml: Sabor
SET @prod_jugo = (SELECT id FROM productos WHERE categoria_id = @cat_bebspiz AND nombre = 'Jugo Natural 500ml');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_jugo;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_jugo, 'Sabor', 'radio', 1, 1, 1, 1);
SET @grp_piz4 = LAST_INSERT_ID();

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_piz4, 'Naranja',   0.00, 1),
  (@grp_piz4, 'Zanahoria', 0.00, 2),
  (@grp_piz4, 'Mixto',     0.00, 3);
