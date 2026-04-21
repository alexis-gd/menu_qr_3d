-- ============================================================
-- template_mariscos.sql — Plantilla rubro: Mariscos / Seafood
-- Ejecutar DESPUÉS de init_demo_db.sql
-- ============================================================

SET NAMES utf8mb4;

-- ── Usuario plantilla ─────────────────────────────────────────
INSERT IGNORE INTO usuarios (nombre, email, password_hash, rol)
VALUES ('Plantilla Mariscos', 'template-mariscos@demo.local',
        '$2y$10$WVB2c93.E1xjxstJNDxsMemN2LRsvhBC9ptUAiawLeUkUE/AOmoWK', 'admin'); -- pass: demo1234

SET @uid_mar = LAST_INSERT_ID();
SELECT @uid_mar := id FROM usuarios WHERE email = 'template-mariscos@demo.local';

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
  @uid_mar, 'template-mariscos', 'El Barco Mariscos',
  'Los mejores mariscos frescos del golfo. Cocteles, ceviches y platillos de autor.',
  'calido', 1,
  'demos/logo_mariscos.jpg',
  1, 1, 45.00,
  350.00, '9611000000',
  0, 0,
  1, 5,
  '🦐 ¡Ven a probar los mejores mariscos! Mira nuestra carta digital.', NULL
);

SET @rid_mar = LAST_INSERT_ID();
SELECT @rid_mar := id FROM restaurantes WHERE slug = 'template-mariscos';

-- ── Recompensas config ────────────────────────────────────────
INSERT IGNORE INTO recompensas_config (restaurante_id, activo, compras_necesarias, tipo, valor)
VALUES (@rid_mar, 1, 8, 'descuento_fijo', 80.00);

-- ── Categorías ────────────────────────────────────────────────
INSERT INTO categorias (restaurante_id, nombre, icono, orden) VALUES
  (@rid_mar, 'Cocteles',  'mdiGlassCocktail', 1),
  (@rid_mar, 'ceviches',  'mdiFish',          2),
  (@rid_mar, 'Platillos', 'mdiFoodForkDrink', 3),
  (@rid_mar, 'Bebidas',   'mdiCupWater',      4);

SET @cat_cocts   = (SELECT id FROM categorias WHERE restaurante_id = @rid_mar AND nombre = 'Cocteles');
SET @cat_cebs    = (SELECT id FROM categorias WHERE restaurante_id = @rid_mar AND nombre = 'ceviches');
SET @cat_plats   = (SELECT id FROM categorias WHERE restaurante_id = @rid_mar AND nombre = 'Platillos');
SET @cat_bebsmar = (SELECT id FROM categorias WHERE restaurante_id = @rid_mar AND nombre = 'Bebidas');

-- ── Productos — Cocteles ──────────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, es_destacado, tiene_personalizacion, orden) VALUES
  (@cat_cocts, 'Coctel de Camarón Chico',
   'Camarón cocido en salsa valentina, jitomate, pepino, cebolla y aguacate. Vaso 350ml.',
   120.00, 'demos/coctel_camaron.jpeg', 0, 0, 1),
  (@cat_cocts, 'Coctel de Camarón Grande',
   'Camarón cocido en salsa valentina, jitomate, pepino, cebolla y aguacate. Tarro 600ml.',
   185.00, 'demos/coctel_camaron.jpeg', 1, 0, 2),
  (@cat_cocts, 'Coctel Mixto Grande',
   'Mezcla de camarón, pulpo, ostión y jaiba. El más completo. Tarro 600ml.',
   220.00, 'demos/coctel_camaron.jpeg', 1, 0, 3),
  (@cat_cocts, 'Tostadas de Atún (3 pzas)',
   'Atún fresco con aguacate, chipotle y mayonesa sobre tostada crujiente.',
   85.00, 'demos/coctel_camaron.jpeg', 0, 0, 4);

-- ── Productos — ceviches ──────────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, es_destacado, tiene_personalizacion, orden) VALUES
  (@cat_cebs, 'ceviche de Pescado',
   'Pescado sierra marinado en limón con pepino, jitomate, cebolla morada y chile serrano.',
   150.00, 'demos/ceviche_camaron.jpeg', 0, 0, 1),
  (@cat_cebs, 'ceviche de Camarón',
   'Camarón crudo marinado en limón con jitomate, pepino y aguacate. Bien frío.',
   175.00, 'demos/ceviche_camaron.jpeg', 1, 0, 2),
  (@cat_cebs, 'ceviche Mixto',
   'Combinación de pescado y camarón marinados con vegetales frescos y aderezo especial.',
   195.00, 'demos/ceviche_camaron.jpeg', 0, 0, 3);

-- ── Productos — Platillos ─────────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, es_destacado, tiene_personalizacion, orden) VALUES
  (@cat_plats, 'Camarones a la Diabla',
   'Camarones jumbo en salsa de chile de árbol ahumado. Acompañados de arroz y ensalada.',
   230.00, 'demos/pulpo_brasas.webp', 1, 0, 1),
  (@cat_plats, 'Filete de Huachinango',
   'Filete a la plancha o al mojo de ajo, con arroz, ensalada y tortillas.',
   200.00, 'demos/pulpo_brasas.webp', 0, 0, 2),
  (@cat_plats, 'Mojarra Frita',
   'Mojarra entera frita crujiente. Con arroz, ensalada, aguacate y tortillas.',
   210.00, 'demos/pulpo_brasas.webp', 0, 0, 3),
  (@cat_plats, 'Pulpo a las Brasas',
   'Pulpo entero asado con aceite de oliva, ajo y limón. Tierno y ahumado.',
   280.00, 'demos/pulpo_brasas.webp', 1, 0, 4);

-- ── Productos — Bebidas ───────────────────────────────────────
INSERT INTO productos (categoria_id, nombre, descripcion, precio, foto_principal, orden) VALUES
  (@cat_bebsmar, 'Agua de Limón con Hierbabuena',
   'Limonada fresca con hojas de hierbabuena. Sin igual en el calor.',
   30.00, 'demos/michelada.webp', 1),
  (@cat_bebsmar, 'Tepache Artesanal',
   'Bebida fermentada de piña con piloncillo y canela. Natural y refrescante.',
   35.00, 'demos/michelada.webp', 2),
  (@cat_bebsmar, 'Michelada',
   'Cerveza con clamato, limón, sal y especias. La compañera perfecta del marisco.',
   75.00, 'demos/michelada.webp', 3),
  (@cat_bebsmar, 'Cerveza Fría',
   'Corona, Modelo o Tecate bien fría.',
   50.00, 'demos/michelada.webp', 4);

-- ── Personalización: 1 producto por categoría ─────────────────

-- Cocteles → Coctel de Camarón Grande: Nivel de picante
SET @prod_coctelgde = (SELECT id FROM productos WHERE categoria_id = @cat_cocts AND nombre = 'Coctel de Camarón Grande');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_coctelgde;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_coctelgde, 'Nivel de picante', 'radio', 1, 1, 1, 1);
SET @grp_mar1 = LAST_INSERT_ID();

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_mar1, 'Sin picante',   0.00, 1),
  (@grp_mar1, 'Suave',         0.00, 2),
  (@grp_mar1, 'Picante',       0.00, 3),
  (@grp_mar1, 'Extra picante', 0.00, 4);

-- ceviches → ceviche de Camarón: Marinado
SET @prod_cebcam = (SELECT id FROM productos WHERE categoria_id = @cat_cebs AND nombre = 'ceviche de Camarón');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_cebcam;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_cebcam, 'Marinado', 'radio', 1, 1, 1, 1);
SET @grp_mar2 = LAST_INSERT_ID();

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_mar2, 'Suave (poco limón)',  0.00, 1),
  (@grp_mar2, 'Normal',              0.00, 2),
  (@grp_mar2, 'Intenso (más limón)', 0.00, 3);

-- Platillos → Camarones a la Diabla: Nivel de picante
SET @prod_diabla = (SELECT id FROM productos WHERE categoria_id = @cat_plats AND nombre = 'Camarones a la Diabla');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_diabla;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_diabla, 'Nivel de picante', 'radio', 1, 1, 1, 1);
SET @grp_mar3 = LAST_INSERT_ID();

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_mar3, 'Suave',        0.00, 1),
  (@grp_mar3, 'Normal',       0.00, 2),
  (@grp_mar3, 'Bien picante', 0.00, 3);

-- Bebidas → Michelada: Tipo de cerveza
SET @prod_michelada = (SELECT id FROM productos WHERE categoria_id = @cat_bebsmar AND nombre = 'Michelada');
UPDATE productos SET tiene_personalizacion = 1 WHERE id = @prod_michelada;

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_michelada, 'Tipo de cerveza', 'radio', 1, 1, 1, 1);
SET @grp_mar4 = LAST_INSERT_ID();

INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@grp_mar4, 'Modelo', 0.00, 1),
  (@grp_mar4, 'Corona', 0.00, 2),
  (@grp_mar4, 'Tecate', 0.00, 3);
