-- Fase 9: Envío gratis por monto mínimo de compra
-- NULL = sin umbral (envío siempre al costo fijo)
-- > 0  = envío gratis cuando subtotal >= este valor

ALTER TABLE restaurantes
  ADD COLUMN pedidos_envio_gratis_desde DECIMAL(10,2) NULL DEFAULT NULL
    AFTER pedidos_envio_costo;
