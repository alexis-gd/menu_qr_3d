-- Fase 24: Registro histórico de demos creadas
-- Aplica en la BD de demos: nodosmxc_menu_demos

CREATE TABLE IF NOT EXISTS demo_registros (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id    INT UNSIGNED NULL DEFAULT NULL,
  usuario_id        INT UNSIGNED NULL DEFAULT NULL,
  template          VARCHAR(30)  NOT NULL,
  slug              VARCHAR(100) NOT NULL,
  nombre            VARCHAR(200) NOT NULL,
  whatsapp          VARCHAR(20)  NULL DEFAULT NULL,
  email             VARCHAR(200) NOT NULL,
  trial_dias        SMALLINT     NOT NULL DEFAULT 7,
  trial_expires_at  TIMESTAMP    NULL DEFAULT NULL,
  estado            ENUM('activa','expirada','convertida','eliminada') NOT NULL DEFAULT 'activa',
  origen            VARCHAR(30)  NOT NULL DEFAULT 'create_demo.php',
  notas             TEXT         NULL DEFAULT NULL,
  created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_demo_estado (estado),
  INDEX idx_demo_slug (slug),
  INDEX idx_demo_email (email),
  INDEX idx_demo_trial (trial_expires_at),
  CONSTRAINT fk_demo_reg_rest FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE SET NULL,
  CONSTRAINT fk_demo_reg_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
