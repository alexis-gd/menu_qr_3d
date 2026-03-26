-- Fase 17: marcar si un pedido fue contado en el acumulado de recompensas
-- Permite revertir el conteo al cancelar un pedido
-- Idempotente: usa IF NOT EXISTS

ALTER TABLE pedidos
  ADD COLUMN IF NOT EXISTS contada_en_recompensas TINYINT NOT NULL DEFAULT 0
    COMMENT '1 si este pedido sumó 1 a clientes.total_compras';

-- Backfill pedidos: marcar como contados solo los no cancelados.
UPDATE pedidos
SET contada_en_recompensas = 1
WHERE status != 'cancelado'
  AND telefono IS NOT NULL
  AND telefono != '';

-- Recalcular total_compras en clientes contando solo pedidos no cancelados.
-- Reemplaza el conteo anterior (que incluía cancelados).
UPDATE clientes c
JOIN (
    SELECT
        p.restaurante_id,
        REGEXP_REPLACE(p.telefono, '[^0-9]', '') AS telefono,
        COUNT(*) AS compras_validas
    FROM pedidos p
    WHERE p.status != 'cancelado'
      AND p.telefono IS NOT NULL
      AND LENGTH(REGEXP_REPLACE(p.telefono, '[^0-9]', '')) >= 8
    GROUP BY p.restaurante_id, REGEXP_REPLACE(p.telefono, '[^0-9]', '')
) AS sub ON c.restaurante_id = sub.restaurante_id AND c.telefono = sub.telefono
SET c.total_compras = sub.compras_validas;
