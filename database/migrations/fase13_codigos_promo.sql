-- Fase 13: Códigos promocionales para promotores
-- Ejecutar en LOCAL primero, luego en QA/PROD antes de subir el código.
--
-- El restaurante crea códigos (ej: JUAN10, PROMO2026),
-- se los da a promotores y el cliente los ingresa en el checkout.
-- Cada código tiene su propio descuento (fijo o porcentaje).

-- ─── 1. Tabla de códigos ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS codigos_promo (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id  INT UNSIGNED NOT NULL,
    codigo          VARCHAR(20)  NOT NULL,
    descripcion     VARCHAR(100) NULL DEFAULT NULL
        COMMENT 'Referencia interna, ej: Promotor Juan García',
    tipo            ENUM('descuento_fijo','descuento_porcentaje') NOT NULL DEFAULT 'descuento_fijo',
    valor           DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    usos            INT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Veces que el código fue usado en un pedido confirmado',
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_codigo_rest (restaurante_id, codigo),
    CONSTRAINT fk_cp_rest FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Códigos promocionales creados por el restaurante';

-- ─── 2. Registrar qué código se usó en cada pedido ─────────────────────────
ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS codigo_promo VARCHAR(20) NULL DEFAULT NULL
        COMMENT 'Código promocional aplicado en este pedido (NULL si ninguno)';
