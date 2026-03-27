-- Fase 19C: Ajuste manual de total en pedidos existentes (solo admin)
-- ajuste_manual: negativo = descuento (amigos, errores), positivo = cargo extra
--   El campo 'total' original no se toca → las recompensas se calculan sin cambios
--   El total real que se cobra = total + ajuste_manual (calculado como total_final en el GET)
-- ajuste_nota: razón del ajuste para auditoría interna
-- Idempotente: ADD COLUMN IF NOT EXISTS

ALTER TABLE pedidos
  ADD COLUMN IF NOT EXISTS ajuste_manual DECIMAL(10,2) NOT NULL DEFAULT 0.00
    COMMENT 'Ajuste post-venta al total. Negativo=descuento, Positivo=cargo extra. No afecta recompensas.',
  ADD COLUMN IF NOT EXISTS ajuste_nota VARCHAR(100) NULL DEFAULT NULL
    COMMENT 'Motivo del ajuste manual (solo visible para el admin)';
