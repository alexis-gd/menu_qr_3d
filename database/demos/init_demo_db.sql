-- ============================================================
-- init_demo_db.sql — Schema completo para BD de demos
-- Ejecutar UNA vez en phpMyAdmin sobre nodosmxc_menu_demos
-- Idempotente: usa IF NOT EXISTS en todo
-- Incluye todas las fases 1–22 en una sola pasada
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ────────────────────────────────────────────────────────────
-- TABLA: usuarios
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS usuarios (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100)  NOT NULL,
  email         VARCHAR(200)  NOT NULL UNIQUE,
  password_hash VARCHAR(255)  NOT NULL,
  rol           ENUM('superadmin','admin') NOT NULL DEFAULT 'admin',
  activo        TINYINT(1)    NOT NULL DEFAULT 1,
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLA: restaurantes (schema completo fase 1–22)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS restaurantes (
  id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id                  INT UNSIGNED  NOT NULL,
  slug                        VARCHAR(100)  NOT NULL UNIQUE,
  nombre                      VARCHAR(200)  NOT NULL,
  descripcion                 TEXT,
  logo_url                    VARCHAR(500),
  color_primario              VARCHAR(7)    DEFAULT '#FF6B35',
  tema                        VARCHAR(20)   NOT NULL DEFAULT 'calido',
  -- QR impreso (fase 23)
  qr_frase                    VARCHAR(120)  NULL DEFAULT NULL,
  qr_frase_activa             TINYINT(1)    NOT NULL DEFAULT 1,
  qr_wifi_nombre              VARCHAR(100)  NULL DEFAULT NULL,
  qr_wifi_clave               VARCHAR(100)  NULL DEFAULT NULL,
  qr_wifi_activo              TINYINT(1)    NOT NULL DEFAULT 0,
  activo                      TINYINT(1)    NOT NULL DEFAULT 1,
  compartir_mensaje           TEXT,
  -- Pedidos y pagos
  pedidos_activos             TINYINT(1)    NOT NULL DEFAULT 0,
  pedidos_envio_activo        TINYINT(1)    NOT NULL DEFAULT 1,
  pedidos_envio_costo         DECIMAL(10,2) NOT NULL DEFAULT 0,
  pedidos_envio_gratis_desde  DECIMAL(10,2) NULL DEFAULT NULL,
  pedidos_whatsapp            VARCHAR(20),
  pedidos_trans_clabe         VARCHAR(18),
  pedidos_trans_cuenta        VARCHAR(30),
  pedidos_trans_titular       VARCHAR(100),
  pedidos_trans_banco         VARCHAR(100),
  pedidos_trans_activo        TINYINT(1)    NOT NULL DEFAULT 0,
  pedidos_terminal_activo     TINYINT(1)    NOT NULL DEFAULT 0,
  pedidos_programar_activo    TINYINT(1)    NOT NULL DEFAULT 0,
  -- Estado de tienda (fase 10)
  tienda_cerrada_manual       TINYINT(1)    NOT NULL DEFAULT 0,
  tienda_horarios             JSON          NULL DEFAULT NULL,
  -- Features (fases 12, 15)
  stock_minimo_aviso          SMALLINT      NOT NULL DEFAULT 5,
  codigos_promo_habilitado    TINYINT(1)    NOT NULL DEFAULT 1,
  -- Trial (fase 22)
  trial_expires_at            TIMESTAMP     NULL DEFAULT NULL
    COMMENT 'NULL = sin restricción (plantilla o cliente real). Con fecha = prospecto en demo.',
  created_at                  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLA: mesas
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS mesas (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id INT UNSIGNED NOT NULL,
  numero         VARCHAR(20)  NOT NULL,
  qr_generado    TINYINT(1)   NOT NULL DEFAULT 0,
  activo         TINYINT(1)   NOT NULL DEFAULT 1,
  created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_mesa_restaurante (restaurante_id, numero),
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLA: categorias
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categorias (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id INT UNSIGNED NOT NULL,
  nombre         VARCHAR(100) NOT NULL,
  icono          VARCHAR(100) DEFAULT NULL,
  orden          SMALLINT     NOT NULL DEFAULT 0,
  activo         TINYINT(1)   NOT NULL DEFAULT 1,
  created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLA: productos (schema completo fases 1–11)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS productos (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id         INT UNSIGNED  NOT NULL,
  nombre               VARCHAR(200)  NOT NULL,
  descripcion          TEXT,
  precio               DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  foto_principal       VARCHAR(500),
  modelo_glb_path      VARCHAR(500),
  tiene_ar             TINYINT(1)    NOT NULL DEFAULT 0,
  es_destacado         TINYINT(1)    NOT NULL DEFAULT 0,
  disponible           TINYINT(1)    NOT NULL DEFAULT 1,
  activo               TINYINT(1)    NOT NULL DEFAULT 1,
  stock                INT UNSIGNED  NULL DEFAULT NULL,
  orden                SMALLINT      NOT NULL DEFAULT 0,
  -- Personalización por pasos (fase 7)
  tiene_personalizacion TINYINT(1)   NOT NULL DEFAULT 0,
  aviso_complemento    TEXT          DEFAULT NULL,
  aviso_categoria_id   INT UNSIGNED  DEFAULT NULL,
  created_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLA: fotos_producto
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS fotos_producto (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id INT UNSIGNED NOT NULL,
  ruta        VARCHAR(500) NOT NULL,
  url_publica VARCHAR(500) NOT NULL,
  orden       SMALLINT     NOT NULL DEFAULT 0,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLA: meshy_jobs
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS meshy_jobs (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id   INT UNSIGNED NOT NULL,
  meshy_task_id VARCHAR(200) NOT NULL,
  status        ENUM('pending','processing','succeeded','failed') NOT NULL DEFAULT 'pending',
  intentos      SMALLINT     NOT NULL DEFAULT 0,
  error_msg     TEXT,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLA: sesiones_admin
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sesiones_admin (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  token      VARCHAR(64)  NOT NULL UNIQUE,
  expira_en  DATETIME     NOT NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLA: pedidos (schema completo fases 1–20a)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pedidos (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id        INT UNSIGNED  NOT NULL,
  numero_pedido         VARCHAR(20)   NOT NULL,
  nombre_cliente        VARCHAR(100)  NOT NULL,
  telefono              VARCHAR(20),
  tipo_entrega          ENUM('recoger','envio') NOT NULL DEFAULT 'recoger',
  direccion             TEXT,
  metodo_pago           ENUM('efectivo','transferencia','terminal') NOT NULL DEFAULT 'efectivo',
  denominacion          DECIMAL(10,2),
  mesa                  VARCHAR(20),
  subtotal              DECIMAL(10,2) NOT NULL DEFAULT 0,
  costo_envio           DECIMAL(10,2) NOT NULL DEFAULT 0,
  total                 DECIMAL(10,2) NOT NULL DEFAULT 0,
  -- Descuentos (fases 13–14)
  descuento_recompensa  DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
  descuento_promo       DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
  codigo_promo          VARCHAR(20)   NULL DEFAULT NULL,
  -- Ajuste manual (fase 19c)
  ajuste_manual         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ajuste_nota           VARCHAR(100)  NULL DEFAULT NULL,
  -- Pedido programado (fase 20a)
  fecha_programada      DATE          NULL,
  hora_programada       TIME          NULL,
  -- Recompensas (fase 17)
  contada_en_recompensas TINYINT      NOT NULL DEFAULT 0,
  status                ENUM('nuevo','visto','en_preparacion','listo','entregado','cancelado') NOT NULL DEFAULT 'nuevo',
  created_at            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pedido_folio (restaurante_id, numero_pedido),
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLA: pedido_items
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pedido_items (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id       INT UNSIGNED  NOT NULL,
  producto_id     INT UNSIGNED,
  nombre_producto VARCHAR(200)  NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  cantidad        SMALLINT      NOT NULL DEFAULT 1,
  observacion     TEXT,
  subtotal        DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLA: pedido_item_opciones (fase 7)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pedido_item_opciones (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_item_id INT UNSIGNED  NOT NULL,
  grupo_nombre   VARCHAR(100)  NOT NULL,
  opcion_nombre  VARCHAR(100)  NOT NULL,
  precio_extra   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (pedido_item_id) REFERENCES pedido_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLAS: producto_grupos y producto_opciones (fase 7)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS producto_grupos (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id           INT UNSIGNED NOT NULL,
  nombre                VARCHAR(100) NOT NULL,
  tipo                  ENUM('radio','checkbox') NOT NULL DEFAULT 'radio',
  obligatorio           TINYINT(1)   NOT NULL DEFAULT 1,
  min_selecciones       SMALLINT     NOT NULL DEFAULT 1,
  max_selecciones       SMALLINT     NOT NULL DEFAULT 1,
  max_dinamico_grupo_id INT UNSIGNED DEFAULT NULL,
  orden                 SMALLINT     NOT NULL DEFAULT 0,
  activo                TINYINT(1)   NOT NULL DEFAULT 1,
  created_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  FOREIGN KEY (max_dinamico_grupo_id) REFERENCES producto_grupos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS producto_opciones (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  grupo_id     INT UNSIGNED  NOT NULL,
  nombre       VARCHAR(100)  NOT NULL,
  precio_extra DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  max_override SMALLINT      DEFAULT NULL,
  orden        SMALLINT      NOT NULL DEFAULT 0,
  activo       TINYINT(1)    NOT NULL DEFAULT 1,
  created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (grupo_id) REFERENCES producto_grupos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLAS: recompensas_config, clientes, referidos (fase 11)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS recompensas_config (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id     INT UNSIGNED NOT NULL,
  activo             TINYINT(1)   NOT NULL DEFAULT 0,
  compras_necesarias SMALLINT     NOT NULL DEFAULT 10,
  tipo               ENUM('descuento_porcentaje','descuento_fijo') NOT NULL DEFAULT 'descuento_fijo',
  valor              DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_restaurante (restaurante_id),
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clientes (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id      INT UNSIGNED NOT NULL,
  telefono            VARCHAR(20)  NOT NULL,
  total_compras       INT UNSIGNED NOT NULL DEFAULT 0,
  recompensas_ganadas INT UNSIGNED NOT NULL DEFAULT 0,
  ultima_compra       DATETIME     NULL DEFAULT NULL,
  created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cliente_tel (restaurante_id, telefono),
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS referidos (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id        INT UNSIGNED NOT NULL,
  telefono              VARCHAR(20)  NOT NULL,
  codigo_ref            VARCHAR(12)  NOT NULL,
  referido_por_telefono VARCHAR(20)  NULL DEFAULT NULL,
  beneficio_aplicado    TINYINT(1)   NOT NULL DEFAULT 0,
  created_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ref_tel (restaurante_id, telefono),
  UNIQUE KEY uq_codigo  (codigo_ref),
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLA: codigos_promo (fases 13, 18, 19b)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS codigos_promo (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id       INT UNSIGNED NOT NULL,
  codigo               VARCHAR(20)  NOT NULL,
  descripcion          VARCHAR(100) NULL DEFAULT NULL,
  tipo                 ENUM('descuento_porcentaje','descuento_fijo','envio_gratis') NOT NULL DEFAULT 'descuento_porcentaje',
  valor                DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  activo               TINYINT(1)   NOT NULL DEFAULT 1,
  usos                 INT UNSIGNED NOT NULL DEFAULT 0,
  usos_maximo          INT UNSIGNED NULL DEFAULT NULL,
  telefono_restringido VARCHAR(20)  NULL DEFAULT NULL,
  created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_codigo_rest (restaurante_id, codigo),
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLA: push_subscriptions (fase 21)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS push_subscriptions (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  restaurante_id    INT NOT NULL,
  endpoint          VARCHAR(700) NOT NULL,
  subscription_data JSON NOT NULL,
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_endpoint (endpoint(500)),
  INDEX idx_restaurante (restaurante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- ÍNDICES (fase 7)
-- ────────────────────────────────────────────────────────────
CREATE INDEX IF NOT EXISTS idx_grupos_producto ON producto_grupos(producto_id, activo, orden);
CREATE INDEX IF NOT EXISTS idx_opciones_grupo  ON producto_opciones(grupo_id, activo, orden);
CREATE INDEX IF NOT EXISTS idx_item_opciones   ON pedido_item_opciones(pedido_item_id);

SET foreign_key_checks = 1;
