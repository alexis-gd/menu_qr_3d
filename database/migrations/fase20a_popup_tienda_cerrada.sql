-- Fase 20a: Popup tienda cerrada + pedidos programados
-- Ejecutar en local → QA → prod

-- Toggle admin: mostrar botón "Programar pedido" en popup de tienda cerrada
ALTER TABLE restaurantes
  ADD COLUMN IF NOT EXISTS pedidos_programar_activo TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Mostrar botón Programar pedido en popup de tienda cerrada';

-- Scheduling en pedidos
ALTER TABLE pedidos
  ADD COLUMN IF NOT EXISTS fecha_programada DATE NULL
    COMMENT 'Fecha programada de entrega (null = pedido normal)' AFTER ajuste_nota,
  ADD COLUMN IF NOT EXISTS hora_programada TIME NULL
    COMMENT 'Hora programada de entrega' AFTER fecha_programada;
