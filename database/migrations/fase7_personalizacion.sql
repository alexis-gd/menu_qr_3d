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

