-- Fase 10: Estado de Tienda
-- Agrega campos para cierre manual y horarios de atención

ALTER TABLE restaurantes
  ADD COLUMN tienda_cerrada_manual TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Toggle para cerrar el menú manualmente (independiente del horario)',
  ADD COLUMN tienda_horarios JSON NULL DEFAULT NULL
    COMMENT 'Horario semanal: {"lunes":{"activo":true,"apertura":"08:00","cierre":"22:00"}, ...}';
