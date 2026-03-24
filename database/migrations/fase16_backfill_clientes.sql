-- Fase 16: Backfill de historial de clientes desde pedidos existentes
-- Ejecutar en LOCAL primero, luego en QA/PROD.
--
-- Propósito:
--   La tabla `clientes` se creó en Fase 11 vacía.
--   Los pedidos anteriores ya tienen `telefono` guardado en `pedidos`.
--   Esta migración popula `clientes.total_compras` contando esos pedidos históricos.
--
-- Idempotente: usa ON DUPLICATE KEY UPDATE.
-- No toca `recompensas_ganadas` (se deja en 0 — el cliente aún no ha canjeado nada).
-- Solo cuenta pedidos con teléfono válido (>= 8 dígitos numéricos).

INSERT INTO clientes (restaurante_id, telefono, total_compras, ultima_compra)
SELECT
    p.restaurante_id,
    REGEXP_REPLACE(p.telefono, '[^0-9]', '') AS telefono,
    COUNT(*)                                  AS total_compras,
    MAX(p.created_at)                         AS ultima_compra
FROM pedidos p
WHERE p.telefono IS NOT NULL
  AND LENGTH(REGEXP_REPLACE(p.telefono, '[^0-9]', '')) >= 8
GROUP BY p.restaurante_id, REGEXP_REPLACE(p.telefono, '[^0-9]', '')
ON DUPLICATE KEY UPDATE
    total_compras = GREATEST(clientes.total_compras, VALUES(total_compras)),
    ultima_compra = GREATEST(clientes.ultima_compra, VALUES(ultima_compra));
