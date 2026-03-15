-- ============================================================
-- MIGRACIÓN FASE 7 — Personalización por pasos
-- Ejecutar en orden. Verificar que la BD esté activa antes.
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- 1. Columnas nuevas en productos
-- ------------------------------------------------------------
ALTER TABLE productos
  ADD COLUMN IF NOT EXISTS tiene_personalizacion TINYINT(1)    NOT NULL DEFAULT 0    AFTER disponible,
  ADD COLUMN IF NOT EXISTS aviso_complemento     TEXT          DEFAULT NULL           AFTER tiene_personalizacion,
  ADD COLUMN IF NOT EXISTS aviso_categoria_id    INT UNSIGNED  DEFAULT NULL           AFTER aviso_complemento;

-- ------------------------------------------------------------
-- 2. Grupos de opciones de un producto
--    (ej: "Tamaño", "Base", "Ingredientes", "Extras")
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS producto_grupos (
  id                    INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  producto_id           INT UNSIGNED  NOT NULL,
  nombre                VARCHAR(100)  NOT NULL,
  tipo                  ENUM('radio','checkbox') NOT NULL DEFAULT 'radio',
  obligatorio           TINYINT(1)    NOT NULL DEFAULT 1,
  min_selecciones       SMALLINT      NOT NULL DEFAULT 1,
  max_selecciones       SMALLINT      NOT NULL DEFAULT 1,
  max_dinamico_grupo_id INT UNSIGNED  DEFAULT NULL,  -- FK al grupo radio que define el max
  orden                 SMALLINT      NOT NULL DEFAULT 0,
  activo                TINYINT(1)    NOT NULL DEFAULT 1,
  created_at            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  FOREIGN KEY (max_dinamico_grupo_id) REFERENCES producto_grupos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. Opciones dentro de un grupo
--    (ej: "Mediano", "Arroz de sushi", "Salmón extra +$30")
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS producto_opciones (
  id           INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  grupo_id     INT UNSIGNED  NOT NULL,
  nombre       VARCHAR(100)  NOT NULL,
  precio_extra DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  max_override SMALLINT      DEFAULT NULL, -- si esta opción radio se elige, define el max del grupo dinámico referenciado
  orden        SMALLINT      NOT NULL DEFAULT 0,
  activo       TINYINT(1)    NOT NULL DEFAULT 1,
  created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (grupo_id) REFERENCES producto_grupos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. Snapshot de opciones elegidas por línea de pedido
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pedido_item_opciones (
  id             INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  pedido_item_id INT UNSIGNED  NOT NULL,
  grupo_nombre   VARCHAR(100)  NOT NULL,   -- snapshot del nombre al momento del pedido
  opcion_nombre  VARCHAR(100)  NOT NULL,   -- snapshot
  precio_extra   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (pedido_item_id) REFERENCES pedido_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. Índices para performance
-- ------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_grupos_producto   ON producto_grupos(producto_id, activo, orden);
CREATE INDEX IF NOT EXISTS idx_opciones_grupo    ON producto_opciones(grupo_id, activo, orden);
CREATE INDEX IF NOT EXISTS idx_item_opciones     ON pedido_item_opciones(pedido_item_id);

SET foreign_key_checks = 1;

-- ============================================================
-- DATOS DE PRUEBA — Poke Bowl Hawaiiano
-- (Ajusta el producto_id y categoria_id a los reales de tu BD)
-- Descomenta y ejecuta si quieres probar de inmediato.
-- ============================================================

/*
-- Paso 1: Marcar el producto como personalizable
-- UPDATE productos SET tiene_personalizacion = 1,
--   aviso_complemento = '¿Quieres agregar una bebida? 🥤',
--   aviso_categoria_id = (SELECT id FROM categorias WHERE nombre LIKE '%Bebida%' LIMIT 1)
-- WHERE nombre LIKE '%Poke%' LIMIT 1;

-- Paso 2: Insertar grupos (ajusta @prod_id)
SET @prod_id = (SELECT id FROM productos WHERE nombre LIKE '%Poke%' LIMIT 1);

INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden) VALUES
  (@prod_id, 'Tamaño',       'radio',    1, 1, 1, 0),
  (@prod_id, 'Base',         'radio',    1, 1, 1, 1),
  (@prod_id, 'Ingredientes', 'checkbox', 1, 1, 5, 2),
  (@prod_id, 'Extras',       'checkbox', 0, 0, 10, 3);

-- Paso 3: Actualizar max_dinamico_grupo_id en Ingredientes (depende de Tamaño)
SET @g_tamaño = (SELECT id FROM producto_grupos WHERE producto_id=@prod_id AND nombre='Tamaño');
SET @g_ingr   = (SELECT id FROM producto_grupos WHERE producto_id=@prod_id AND nombre='Ingredientes');
UPDATE producto_grupos SET max_dinamico_grupo_id = @g_tamaño WHERE id = @g_ingr;

-- Paso 4: Opciones de Tamaño (con max_override para Ingredientes)
SET @g_tamaño = (SELECT id FROM producto_grupos WHERE producto_id=@prod_id AND nombre='Tamaño');
INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, max_override, orden) VALUES
  (@g_tamaño, 'Chico',   0.00, 3, 0),
  (@g_tamaño, 'Mediano', 0.00, 5, 1),
  (@g_tamaño, 'Grande',  0.00, 8, 2);

-- Paso 5: Opciones de Base
SET @g_base = (SELECT id FROM producto_grupos WHERE producto_id=@prod_id AND nombre='Base');
INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@g_base, 'Arroz de sushi', 0.00, 0),
  (@g_base, 'Fideos',          0.00, 1),
  (@g_base, 'Arroz + Fideos',  0.00, 2);

-- Paso 6: Opciones de Ingredientes
SET @g_ingr = (SELECT id FROM producto_grupos WHERE producto_id=@prod_id AND nombre='Ingredientes');
INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@g_ingr, 'Edamame',    0.00, 0),
  (@g_ingr, 'Aguacate',   0.00, 1),
  (@g_ingr, 'Pepino',     0.00, 2),
  (@g_ingr, 'Zanahoria',  0.00, 3),
  (@g_ingr, 'Mango',      0.00, 4),
  (@g_ingr, 'Cebolla morada', 0.00, 5),
  (@g_ingr, 'Maíz',       0.00, 6),
  (@g_ingr, 'Algas',      0.00, 7),
  (@g_ingr, 'Pepino encurtido', 0.00, 8),
  (@g_ingr, 'Tomate',     0.00, 9);

-- Paso 7: Opciones de Extras
SET @g_extras = (SELECT id FROM producto_grupos WHERE producto_id=@prod_id AND nombre='Extras');
INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, orden) VALUES
  (@g_extras, 'Salmón extra',   30.00, 0),
  (@g_extras, 'Atún extra',     30.00, 1),
  (@g_extras, 'Camarón extra',  35.00, 2),
  (@g_extras, 'Salsa ponzu',     0.00, 3),
  (@g_extras, 'Sriracha',        0.00, 4),
  (@g_extras, 'Eel sauce',       0.00, 5),
  (@g_extras, 'Mayonesa spicy',  0.00, 6);
*/
