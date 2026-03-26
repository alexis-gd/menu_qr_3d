-- Fase 18: Tope de canje para códigos de promotor
-- NULL = sin tope (comportamiento anterior se mantiene)
-- Ejecutar en LOCAL primero, luego QA/PROD antes de subir el código.

ALTER TABLE codigos_promo
    ADD COLUMN IF NOT EXISTS usos_maximo INT UNSIGNED NULL DEFAULT NULL
        COMMENT 'Máximo de canjes permitidos. NULL = sin límite.';
