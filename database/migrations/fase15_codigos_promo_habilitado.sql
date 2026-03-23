-- Fase 15: toggle para habilitar/deshabilitar sección de códigos de promotor
ALTER TABLE restaurantes
  ADD COLUMN IF NOT EXISTS codigos_promo_habilitado TINYINT(1) NOT NULL DEFAULT 1;
