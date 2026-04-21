-- Fase 25: Columna referencia en pedidos
-- Referencia adicional de entrega (ej: "edificio azul, junto a la farmacia")
-- Se envía junto a direccion cuando tipo_entrega = 'envio'

ALTER TABLE pedidos
  ADD COLUMN IF NOT EXISTS referencia VARCHAR(150) NULL DEFAULT NULL
  AFTER direccion;
