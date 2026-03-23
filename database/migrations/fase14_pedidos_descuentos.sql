-- Fase 14: Registrar descuentos aplicados por pedido
-- Ejecutar en LOCAL primero, luego en QA/PROD.
--
-- Permite al restaurante ver en cada pedido cuánto se descontó
-- por recompensa de lealtad y por código de promotor.

ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS descuento_recompensa DECIMAL(8,2) NOT NULL DEFAULT 0.00
        COMMENT 'Monto descontado por recompensa de lealtad en este pedido',
    ADD COLUMN IF NOT EXISTS descuento_promo DECIMAL(8,2) NOT NULL DEFAULT 0.00
        COMMENT 'Monto descontado por código de promotor en este pedido';
