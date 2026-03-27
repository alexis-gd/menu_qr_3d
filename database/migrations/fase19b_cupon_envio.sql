-- Fase 19B: Cupón de envío gratis + restricción por número de teléfono
-- tipo: nuevo valor 'envio_gratis' anula el costo de envío del pedido
-- telefono_restringido: si no es NULL, solo el cliente con ese teléfono puede canjearlo
-- usos_maximo ya existe desde fase18 — no se agrega aquí
-- Idempotente: ADD COLUMN IF NOT EXISTS, MODIFY ENUM es seguro (agrega valor al final)

-- Los valores existentes son 'descuento_porcentaje' y 'descuento_fijo'
-- Solo agregamos 'envio_gratis' al final para no invalidar registros existentes
ALTER TABLE codigos_promo
  MODIFY COLUMN tipo
    ENUM('descuento_porcentaje','descuento_fijo','envio_gratis') NOT NULL DEFAULT 'descuento_porcentaje'
    COMMENT 'descuento_porcentaje: % del subtotal; descuento_fijo: monto fijo; envio_gratis: anula costo_envio';

ALTER TABLE codigos_promo
  ADD COLUMN IF NOT EXISTS telefono_restringido VARCHAR(20) NULL DEFAULT NULL
    COMMENT 'Si se especifica, solo el cliente con este número puede canjearlo. NULL = cualquier cliente.';
