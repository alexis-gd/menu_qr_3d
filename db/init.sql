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

-- Agregar más tablas según se necesite más adelante.

SET foreign_key_checks = 1;
