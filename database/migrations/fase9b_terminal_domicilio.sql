-- Fase 9b: Terminal a domicilio como método de pago
-- Solo disponible cuando el tipo de entrega es "envío a domicilio"

-- 1. Toggle en restaurantes
ALTER TABLE restaurantes
  ADD COLUMN pedidos_terminal_activo TINYINT(1) NOT NULL DEFAULT 0
    AFTER pedidos_envio_gratis_desde;

-- 2. Ampliar ENUM en pedidos para aceptar 'terminal'
ALTER TABLE pedidos
  MODIFY COLUMN metodo_pago ENUM('efectivo','transferencia','terminal') NOT NULL DEFAULT 'efectivo';
