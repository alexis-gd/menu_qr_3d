-- Script de creación de la base de datos para fase 1.
-- Ejecutar utilizando phpMyAdmin o el cliente MySQL.

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- Tabla mínima necesaria para pruebas inciales. El esquema completo está
-- documentado en CONTEXTO_BASE_DE_DATOS.md, pero en fase 1 basta con
-- crear sólo lo que se vaya a usar (usuarios/restaurantes/categorias/...)

-- ------------------------------------------------------------
-- TABLA: usuarios
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre          VARCHAR(100)  NOT NULL,
  email           VARCHAR(200)  NOT NULL UNIQUE,
  password_hash   VARCHAR(255)  NOT NULL,
  rol             ENUM('superadmin','admin') NOT NULL DEFAULT 'admin',
  activo          TINYINT(1)    NOT NULL DEFAULT 1,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: restaurantes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS restaurantes (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id      INT UNSIGNED  NOT NULL,
  slug            VARCHAR(100)  NOT NULL UNIQUE,
  nombre          VARCHAR(200)  NOT NULL,
  descripcion     TEXT,
  logo_url        VARCHAR(500),
  color_primario  VARCHAR(7)    DEFAULT '#FF6B35',
  activo          TINYINT(1)    NOT NULL DEFAULT 1,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Continuar creando el resto de las tablas del esquema

-- ------------------------------------------------------------
-- TABLA: mesas
CREATE TABLE IF NOT EXISTS mesas (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id  INT UNSIGNED  NOT NULL,
  numero          VARCHAR(20)   NOT NULL,
  qr_generado     TINYINT(1)    NOT NULL DEFAULT 0,
  activo          TINYINT(1)    NOT NULL DEFAULT 1,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_mesa_restaurante (restaurante_id, numero),
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: categorias
CREATE TABLE IF NOT EXISTS categorias (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id  INT UNSIGNED  NOT NULL,
  nombre          VARCHAR(100)  NOT NULL,
  icono           VARCHAR(50),
  orden           SMALLINT      NOT NULL DEFAULT 0,
  activo          TINYINT(1)    NOT NULL DEFAULT 1,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: productos
CREATE TABLE IF NOT EXISTS productos (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id    INT UNSIGNED  NOT NULL,
  nombre          VARCHAR(200)  NOT NULL,
  descripcion     TEXT,
  precio          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  foto_principal  VARCHAR(500),
  modelo_glb_path VARCHAR(500),
  tiene_ar        TINYINT(1)    NOT NULL DEFAULT 0,
  es_destacado    TINYINT(1)    NOT NULL DEFAULT 0,
  disponible      TINYINT(1)    NOT NULL DEFAULT 1,
  activo          TINYINT(1)    NOT NULL DEFAULT 1,
  orden           SMALLINT      NOT NULL DEFAULT 0,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: fotos_producto
CREATE TABLE IF NOT EXISTS fotos_producto (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id     INT UNSIGNED  NOT NULL,
  ruta            VARCHAR(500)  NOT NULL,
  url_publica     VARCHAR(500)  NOT NULL,
  orden           SMALLINT      NOT NULL DEFAULT 0,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: meshy_jobs
CREATE TABLE IF NOT EXISTS meshy_jobs (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id     INT UNSIGNED  NOT NULL,
  meshy_task_id   VARCHAR(200)  NOT NULL,
  status          ENUM('pending','processing','succeeded','failed') NOT NULL DEFAULT 'pending',
  intentos        SMALLINT      NOT NULL DEFAULT 0,
  error_msg       TEXT,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: sesiones_admin
CREATE TABLE IF NOT EXISTS sesiones_admin (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id      INT UNSIGNED  NOT NULL,
  token           VARCHAR(64)   NOT NULL UNIQUE,
  expira_en       DATETIME      NOT NULL,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
