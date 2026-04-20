-- Fase 22: Sistema de período de prueba para demos
-- Aplica en AMBAS BDs: prod y demo.
-- En prod: los restaurantes reales tienen trial_expires_at = NULL (sin restricción).
-- En demo: los prospectos tienen la fecha de vencimiento; las plantillas tienen NULL.
-- Idempotente: ADD COLUMN IF NOT EXISTS

ALTER TABLE restaurantes
  ADD COLUMN IF NOT EXISTS trial_expires_at TIMESTAMP NULL DEFAULT NULL
    COMMENT 'Fecha de vencimiento del trial. NULL = sin restricción (cliente real o plantilla template).';
