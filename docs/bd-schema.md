# docs/bd-schema.md — Schema MySQL

## Motor y configuración
- Motor: **MySQL 5.7+** (cPanel)
- Charset: **utf8mb4**, Collation: **utf8mb4_unicode_ci** en todas las tablas
- Nombres de BD en cPanel siguen formato `usuario_nombrebd`

---

## Script de creación completo

```sql
SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- usuarios: admins del panel
CREATE TABLE IF NOT EXISTS usuarios (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  email         VARCHAR(200) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol           ENUM('superadmin','admin') NOT NULL DEFAULT 'admin',
  activo        TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- restaurantes
CREATE TABLE IF NOT EXISTS restaurantes (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id      INT UNSIGNED NOT NULL,
  slug            VARCHAR(100) NOT NULL UNIQUE,
  nombre          VARCHAR(200) NOT NULL,
  descripcion     TEXT,
  logo_url        VARCHAR(500),
  color_primario  VARCHAR(7) DEFAULT '#FF6B35',
  tema            VARCHAR(50) DEFAULT 'calido',
  qr_frase        VARCHAR(255) DEFAULT 'Delicioso desde el primer vistazo',
  qr_frase_activa TINYINT(1) NOT NULL DEFAULT 1,
  qr_wifi_nombre  VARCHAR(100),
  qr_wifi_clave   VARCHAR(100),
  qr_wifi_activo  TINYINT(1) NOT NULL DEFAULT 0,
  pedidos_activos              TINYINT(1) NOT NULL DEFAULT 0,
  pedidos_envio_activo         TINYINT(1) NOT NULL DEFAULT 0,
  pedidos_envio_costo          DECIMAL(10,2) DEFAULT 0.00,
  pedidos_envio_gratis_desde   DECIMAL(10,2) NULL DEFAULT NULL,
  pedidos_whatsapp             VARCHAR(20),
  pedidos_terminal_activo      TINYINT(1) NOT NULL DEFAULT 0,
  pedidos_trans_activo         TINYINT(1) NOT NULL DEFAULT 0,
  pedidos_trans_clabe          VARCHAR(18),
  pedidos_trans_cuenta         VARCHAR(20),
  pedidos_trans_titular        VARCHAR(100),
  pedidos_trans_banco          VARCHAR(100),
  compartir_mensaje            TEXT,
  tienda_cerrada_manual        TINYINT(1) NOT NULL DEFAULT 0,
  tienda_horarios              JSON NULL DEFAULT NULL,
  stock_minimo_aviso           SMALLINT NOT NULL DEFAULT 5,
  pedidos_programar_activo     TINYINT(1) NOT NULL DEFAULT 0,
  codigos_promo_habilitado     TINYINT(1) NOT NULL DEFAULT 1,
  trial_expires_at             TIMESTAMP NULL DEFAULT NULL,
  activo          TINYINT(1) NOT NULL DEFAULT 1,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- mesas
CREATE TABLE IF NOT EXISTS mesas (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id INT UNSIGNED NOT NULL,
  numero         VARCHAR(20) NOT NULL,
  qr_generado    TINYINT(1) NOT NULL DEFAULT 0,
  activo         TINYINT(1) NOT NULL DEFAULT 1,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_mesa_restaurante (restaurante_id, numero),
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- categorias
CREATE TABLE IF NOT EXISTS categorias (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id INT UNSIGNED NOT NULL,
  nombre         VARCHAR(100) NOT NULL,
  icono          VARCHAR(100),   -- nombre export MDI (ej: 'mdiPizza'). Antes era emoji unicode.
  orden          SMALLINT NOT NULL DEFAULT 0,
  activo         TINYINT(1) NOT NULL DEFAULT 1,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- productos
CREATE TABLE IF NOT EXISTS productos (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id          INT UNSIGNED NOT NULL,
  nombre                VARCHAR(200) NOT NULL,
  descripcion           TEXT,
  precio                DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  foto_principal        VARCHAR(500),   -- ruta relativa a /uploads/, ej: fotos/1/principal.jpg
  modelo_glb_path       VARCHAR(500),   -- solo nombre del archivo, ej: modelo_1_1234.glb
  tiene_ar              TINYINT(1) NOT NULL DEFAULT 0,
  es_destacado          TINYINT(1) NOT NULL DEFAULT 0,
  disponible            TINYINT(1) NOT NULL DEFAULT 1,   -- 0 = "Próximamente" (visible sin compra)
  tiene_personalizacion TINYINT(1) NOT NULL DEFAULT 0,
  aviso_complemento     TEXT DEFAULT NULL,
  aviso_categoria_id    INT UNSIGNED DEFAULT NULL,
  stock                 SMALLINT DEFAULT NULL,            -- NULL=sin control, 0=agotado, >0=disponibles
  activo                TINYINT(1) NOT NULL DEFAULT 1,
  orden                 SMALLINT NOT NULL DEFAULT 0,
  created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- fotos_producto
CREATE TABLE IF NOT EXISTS fotos_producto (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id INT UNSIGNED NOT NULL,
  ruta        VARCHAR(500) NOT NULL,      -- relativa al webroot
  url_publica VARCHAR(500) NOT NULL,      -- URL absoluta (históricamente para Meshy)
  orden       SMALLINT NOT NULL DEFAULT 0,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- meshy_jobs
CREATE TABLE IF NOT EXISTS meshy_jobs (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id   INT UNSIGNED NOT NULL,
  meshy_task_id VARCHAR(200) NOT NULL,
  status        ENUM('pending','processing','succeeded','failed') NOT NULL DEFAULT 'pending',
  intentos      SMALLINT NOT NULL DEFAULT 0,
  error_msg     TEXT,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- sesiones_admin
CREATE TABLE IF NOT EXISTS sesiones_admin (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  token      VARCHAR(64) NOT NULL UNIQUE,
  expira_en  DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- pedidos
CREATE TABLE IF NOT EXISTS pedidos (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id       INT UNSIGNED NOT NULL,
  numero_pedido        VARCHAR(20) NOT NULL,    -- YYYYMMDD-KBR4 (no secuencial)
  nombre_cliente       VARCHAR(100) NOT NULL,
  telefono             VARCHAR(20),
  tipo_entrega         ENUM('recoger','envio') NOT NULL DEFAULT 'recoger',
  direccion            VARCHAR(200),
  referencia           VARCHAR(150) NULL DEFAULT NULL,   -- Fase 25: referencia de entrega
  metodo_pago          ENUM('efectivo','transferencia','terminal') NOT NULL DEFAULT 'efectivo',
  denominacion         DECIMAL(10,2),
  mesa                 VARCHAR(20),
  subtotal             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  costo_envio          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  descuento_recompensa DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  descuento_promo      DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  codigo_promo         VARCHAR(20) DEFAULT NULL,
  total                DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ajuste_manual        DECIMAL(10,2) NOT NULL DEFAULT 0.00,   -- Fase 19: positivo=cargo, negativo=desc
  ajuste_nota          VARCHAR(100) NULL DEFAULT NULL,
  fecha_programada     DATE NULL DEFAULT NULL,
  hora_programada      TIME NULL DEFAULT NULL,
  contada_en_recompensas TINYINT(1) NOT NULL DEFAULT 0,       -- Fase 17
  status               ENUM('nuevo','visto','en_preparacion','listo','entregado','cancelado') NOT NULL DEFAULT 'nuevo',
  created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- pedido_items
CREATE TABLE IF NOT EXISTS pedido_items (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id       INT UNSIGNED NOT NULL,
  producto_id     INT UNSIGNED,
  nombre_producto VARCHAR(200) NOT NULL,   -- snapshot al momento del pedido
  precio_unitario DECIMAL(10,2) NOT NULL,
  cantidad        SMALLINT NOT NULL DEFAULT 1,
  observacion     VARCHAR(100),
  subtotal        DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- push_subscriptions (Fase 21)
CREATE TABLE IF NOT EXISTS push_subscriptions (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  restaurante_id   INT NOT NULL,
  endpoint         VARCHAR(700) NOT NULL,
  subscription_data JSON NOT NULL,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_endpoint (endpoint(500)),
  INDEX idx_restaurante (restaurante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- producto_grupos (Fase 7)
CREATE TABLE IF NOT EXISTS producto_grupos (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id           INT UNSIGNED NOT NULL,
  nombre                VARCHAR(100) NOT NULL,
  tipo                  ENUM('radio','checkbox') NOT NULL DEFAULT 'radio',
  obligatorio           TINYINT(1) NOT NULL DEFAULT 1,
  min_selecciones       SMALLINT NOT NULL DEFAULT 1,
  max_selecciones       SMALLINT NOT NULL DEFAULT 1,
  max_dinamico_grupo_id INT UNSIGNED DEFAULT NULL,
  orden                 SMALLINT NOT NULL DEFAULT 0,
  activo                TINYINT(1) NOT NULL DEFAULT 1,
  created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  FOREIGN KEY (max_dinamico_grupo_id) REFERENCES producto_grupos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- producto_opciones (Fase 7)
CREATE TABLE IF NOT EXISTS producto_opciones (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  grupo_id     INT UNSIGNED NOT NULL,
  nombre       VARCHAR(100) NOT NULL,
  precio_extra DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  max_override SMALLINT DEFAULT NULL,
  orden        SMALLINT NOT NULL DEFAULT 0,
  activo       TINYINT(1) NOT NULL DEFAULT 1,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (grupo_id) REFERENCES producto_grupos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- pedido_item_opciones (Fase 7) — snapshot de opciones seleccionadas
CREATE TABLE IF NOT EXISTS pedido_item_opciones (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_item_id INT UNSIGNED NOT NULL,
  grupo_nombre   VARCHAR(100) NOT NULL,
  opcion_nombre  VARCHAR(100) NOT NULL,
  precio_extra   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (pedido_item_id) REFERENCES pedido_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- recompensas_config (Fase 11)
CREATE TABLE IF NOT EXISTS recompensas_config (
  id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id         INT UNSIGNED NOT NULL UNIQUE,
  activo                 TINYINT(1) NOT NULL DEFAULT 0,
  compras_necesarias     SMALLINT NOT NULL DEFAULT 10,
  descripcion_recompensa VARCHAR(200) DEFAULT 'Pedido gratis',
  tipo_descuento         ENUM('fijo','porcentaje','gratis') NOT NULL DEFAULT 'gratis',
  valor_descuento        DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- clientes (Fase 11) — historial por teléfono por restaurante
CREATE TABLE IF NOT EXISTS clientes (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id      INT UNSIGNED NOT NULL,
  telefono            VARCHAR(20) NOT NULL,
  total_compras       SMALLINT NOT NULL DEFAULT 0,
  recompensas_ganadas SMALLINT NOT NULL DEFAULT 0,
  created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_cliente_restaurante (restaurante_id, telefono),
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- codigos_promo (Fase 13)
CREATE TABLE IF NOT EXISTS codigos_promo (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id       INT UNSIGNED NOT NULL,
  codigo               VARCHAR(20) NOT NULL,
  descripcion          VARCHAR(200) DEFAULT NULL,
  tipo                 ENUM('descuento_porcentaje','descuento_fijo','envio_gratis') NOT NULL DEFAULT 'descuento_porcentaje',
  valor                DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  usos                 INT UNSIGNED NOT NULL DEFAULT 0,
  usos_maximo          INT UNSIGNED NULL DEFAULT NULL,
  telefono_restringido VARCHAR(20) NULL DEFAULT NULL,
  activo               TINYINT(1) NOT NULL DEFAULT 1,
  created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_codigo_restaurante (restaurante_id, codigo),
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
```

---

## Diagrama de relaciones

```
usuarios
  └──< restaurantes (usuario_id)
         ├──< mesas (restaurante_id)
         ├──< categorias (restaurante_id)
         │      └──< productos (categoria_id)
         │             ├──< fotos_producto (producto_id)
         │             ├──< meshy_jobs (producto_id)
         │             └──< producto_grupos (producto_id)
         │                    └──< producto_opciones (grupo_id)
         ├──< pedidos (restaurante_id)
         │      └──< pedido_items (pedido_id)
         │             └──< pedido_item_opciones (pedido_item_id)
         ├──< push_subscriptions (restaurante_id)
         ├──< recompensas_config (restaurante_id)   ← 1:1
         ├──< clientes (restaurante_id)
         └──< codigos_promo (restaurante_id)

usuarios
  └──< sesiones_admin (usuario_id)
```

Columnas de trial/demo: `trial_expires_at` (NULL=sin restricción, fecha=prospecto demo). Ver `docs/demos.md`.

---

## Índices importantes

```sql
CREATE INDEX idx_restaurante_slug ON restaurantes(slug);
CREATE INDEX idx_categoria_restaurante ON categorias(restaurante_id, activo, orden);
CREATE INDEX idx_producto_categoria ON productos(categoria_id, activo, disponible, orden);
CREATE INDEX idx_meshy_status ON meshy_jobs(status, intentos);
CREATE UNIQUE INDEX IF NOT EXISTS uq_pedido_folio ON pedidos (restaurante_id, numero_pedido);
```
