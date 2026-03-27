-- Fase 19A: Folio único no secuencial para pedidos
-- Cambia el formato de numero_pedido de YYYYMMDD-XXXX (secuencial) a YYYYMMDD-KBR4 (aleatorio)
-- No requiere cambio de columna (VARCHAR(20) ya existe y el nuevo formato tiene el mismo largo)
-- Solo agrega índice único para garantizar unicidad por restaurante
-- Idempotente: IF NOT EXISTS

CREATE UNIQUE INDEX IF NOT EXISTS uq_pedido_folio
    ON pedidos (restaurante_id, numero_pedido);
