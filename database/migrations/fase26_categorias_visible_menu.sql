-- Fase 26: Visibilidad publica de categorias
-- Permite ocultar una categoria del menu publico sin eliminarla del admin.

ALTER TABLE categorias
  ADD COLUMN IF NOT EXISTS visible_menu TINYINT(1) NOT NULL DEFAULT 1
  AFTER activo;
