-- Fase 12: Umbral configurable para el badge "Últimas X piezas"
-- Ejecutar en LOCAL primero, luego en QA/PROD antes de subir el código.
--
-- Agrega a restaurantes:
--   stock_minimo_aviso → número de piezas por debajo del cual se muestra
--                        el badge naranja "Últimas X" en el menú público.
--                        0 = badge desactivado para este restaurante.

ALTER TABLE restaurantes
  ADD COLUMN IF NOT EXISTS stock_minimo_aviso SMALLINT NOT NULL DEFAULT 5
    COMMENT 'Piezas ≤ este número muestran badge "Últimas X" en el menú. 0 = badge desactivado.';
