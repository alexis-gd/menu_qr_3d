-- Fase 11: Programa de Recompensas y Referidos
-- Ejecutar en LOCAL primero, luego en QA/PROD antes de subir el código.
--
-- Tablas nuevas:
--   recompensas_config  → configuración por restaurante
--   clientes            → historial de compras por teléfono
--   referidos           → códigos de referido y relación entre clientes
--
-- Sin ALTER TABLE en tablas existentes (pedidos ya tiene telefono).

-- ─── 1. Configuración de recompensas por restaurante ───────────────────────
CREATE TABLE IF NOT EXISTS recompensas_config (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id      INT UNSIGNED NOT NULL,
    activo              TINYINT(1)   NOT NULL DEFAULT 0
        COMMENT 'Toggle: habilita/deshabilita el programa para este restaurante',
    compras_necesarias  SMALLINT     NOT NULL DEFAULT 10
        COMMENT 'Número de pedidos para ganar la recompensa',
    tipo                ENUM('descuento_porcentaje','descuento_fijo') NOT NULL DEFAULT 'descuento_fijo',
    valor               DECIMAL(8,2) NOT NULL DEFAULT 0.00
        COMMENT 'Valor del descuento (% o monto fijo según tipo)',
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_restaurante (restaurante_id),
    CONSTRAINT fk_rc_restaurante FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configuración del programa de recompensas por restaurante';

-- ─── 2. Historial de clientes por teléfono ─────────────────────────────────
CREATE TABLE IF NOT EXISTS clientes (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id      INT UNSIGNED NOT NULL,
    telefono            VARCHAR(20)  NOT NULL,
    total_compras       INT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Pedidos confirmados acumulados (se incrementa al confirmar pedido)',
    recompensas_ganadas INT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Veces que completó el ciclo de N compras',
    ultima_compra       DATETIME     NULL DEFAULT NULL,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cliente_tel (restaurante_id, telefono),
    CONSTRAINT fk_cli_restaurante FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historial acumulado de compras por número de teléfono';

-- ─── 3. Referidos ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS referidos (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id          INT UNSIGNED NOT NULL,
    telefono                VARCHAR(20)  NOT NULL
        COMMENT 'Teléfono del cliente que tiene el código de referido',
    codigo_ref              VARCHAR(12)  NOT NULL
        COMMENT 'Código único generado al primer pedido (ej: REF-A1B2C3)',
    referido_por_telefono   VARCHAR(20)  NULL DEFAULT NULL
        COMMENT 'Teléfono de quien refirió a este cliente (NULL si llegó solo)',
    beneficio_aplicado      TINYINT(1)  NOT NULL DEFAULT 0
        COMMENT '1 cuando el primer pedido del referido ya fue contabilizado',
    created_at              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ref_tel   (restaurante_id, telefono),
    UNIQUE KEY uq_codigo    (codigo_ref),
    CONSTRAINT fk_ref_restaurante FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Códigos de referido y relación entre clientes';
