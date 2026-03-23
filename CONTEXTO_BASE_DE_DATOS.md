# CONTEXTO BASE DE DATOS: menu_qr_3d
> Lee este archivo junto con CONTEXTO_PROYECTO.md al inicio de cada chat.
> Contiene el esquema completo, relaciones, y reglas de la base de datos.

---

## 1. MOTOR Y CONFIGURACIÓN

- Motor: **MySQL 5.7+** (el que incluye cPanel)
- Charset: **utf8mb4** en todas las tablas
- Collation: **utf8mb4_unicode_ci**
- El nombre real de la BD en cPanel sigue el formato `usuario_nombrebd`

---

## 2. SCRIPT COMPLETO DE CREACIÓN

```sql
-- ============================================================
-- BASE DE DATOS: menu_qr_3d
-- Ejecutar en orden. Las FK requieren que las tablas padre existan.
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- TABLA: usuarios
-- Admins del panel. Cada restaurante tiene su propio usuario.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre          VARCHAR(100)  NOT NULL,
  email           VARCHAR(200)  NOT NULL UNIQUE,
  password_hash   VARCHAR(255)  NOT NULL,     -- password_hash() de PHP
  rol             ENUM('superadmin','admin') NOT NULL DEFAULT 'admin',
  activo          TINYINT(1)    NOT NULL DEFAULT 1,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: restaurantes
-- Un usuario puede tener uno o varios restaurantes.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS restaurantes (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id      INT UNSIGNED  NOT NULL,
  slug            VARCHAR(100)  NOT NULL UNIQUE,  -- para URL del QR: ?r=slug
  nombre          VARCHAR(200)  NOT NULL,
  descripcion     TEXT,
  logo_url        VARCHAR(500),                   -- ruta relativa en /uploads/logos/, ej: logos/logo_1_1234.jpg
  color_primario  VARCHAR(7)    DEFAULT '#FF6B35', -- color hex para UI del menú
  tema            VARCHAR(50)   DEFAULT 'calido',  -- tema visual: calido|oscuro|moderno|rapida|rosa
  qr_frase        VARCHAR(255)  DEFAULT 'Delicioso desde el primer vistazo', -- frase bajo el QR
  qr_frase_activa TINYINT(1)    NOT NULL DEFAULT 1,  -- mostrar/ocultar la frase
  qr_wifi_nombre  VARCHAR(100),                   -- nombre de red WiFi del restaurante
  qr_wifi_clave   VARCHAR(100),                   -- contraseña WiFi
  qr_wifi_activo  TINYINT(1)    NOT NULL DEFAULT 0, -- mostrar sección WiFi en la card QR
  pedidos_activos        TINYINT(1)    NOT NULL DEFAULT 0, -- toggle sistema de pedidos
  pedidos_envio_activo        TINYINT(1)    NOT NULL DEFAULT 0,  -- opción de entrega a domicilio
  pedidos_envio_costo         DECIMAL(10,2) DEFAULT 0.00,        -- costo de envío
  pedidos_envio_gratis_desde  DECIMAL(10,2) NULL DEFAULT NULL,   -- umbral para envío gratis (NULL = desactivado)
  pedidos_whatsapp            VARCHAR(20),                       -- número WA sin + ni espacios
  pedidos_terminal_activo     TINYINT(1)    NOT NULL DEFAULT 0,  -- toggle terminal a domicilio (solo envío)
  pedidos_trans_activo        TINYINT(1)    NOT NULL DEFAULT 0,  -- toggle para mostrar opción de transferencia
  pedidos_trans_clabe    VARCHAR(18),                      -- CLABE para transferencia
  pedidos_trans_cuenta   VARCHAR(20),
  pedidos_trans_titular  VARCHAR(100),
  pedidos_trans_banco    VARCHAR(100),
  compartir_mensaje      TEXT,                             -- texto personalizable del botón "Compartir"
  tienda_cerrada_manual  TINYINT(1)    NOT NULL DEFAULT 0,  -- 1 = menú cerrado manualmente (override horarios)
  tienda_horarios        JSON          NULL DEFAULT NULL,    -- horario semanal: {"lunes":{"activo":true,"apertura":"08:00","cierre":"22:00"}, ...}
    -- NULL = sin horarios configurados = tienda siempre abierta (salvo cierre manual)
  stock_minimo_aviso     SMALLINT      NOT NULL DEFAULT 5,   -- umbral para badge "Últimas N piezas" (0 = desactivado)
  activo          TINYINT(1)    NOT NULL DEFAULT 1,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Si ya existe la tabla (BD en producción), agregar columnas con ALTER:
-- ALTER TABLE restaurantes ADD COLUMN tema VARCHAR(50) DEFAULT 'calido' AFTER color_primario;
-- ALTER TABLE restaurantes ADD COLUMN qr_frase VARCHAR(255) DEFAULT 'Delicioso desde el primer vistazo' AFTER tema;
-- ALTER TABLE restaurantes ADD COLUMN qr_frase_activa TINYINT(1) NOT NULL DEFAULT 1 AFTER qr_frase;
-- ALTER TABLE restaurantes ADD COLUMN qr_wifi_nombre VARCHAR(100) AFTER qr_frase_activa;
-- ALTER TABLE restaurantes ADD COLUMN qr_wifi_clave VARCHAR(100) AFTER qr_wifi_nombre;
-- ALTER TABLE restaurantes ADD COLUMN qr_wifi_activo TINYINT(1) NOT NULL DEFAULT 0 AFTER qr_wifi_clave;
-- Fase 6 — Sistema de Pedidos:
-- ALTER TABLE restaurantes ADD COLUMN pedidos_activos TINYINT(1) NOT NULL DEFAULT 0 AFTER qr_wifi_activo;
-- ALTER TABLE restaurantes ADD COLUMN pedidos_envio_activo TINYINT(1) NOT NULL DEFAULT 0 AFTER pedidos_activos;
-- ALTER TABLE restaurantes ADD COLUMN pedidos_envio_costo DECIMAL(10,2) DEFAULT 0.00 AFTER pedidos_envio_activo;
-- ALTER TABLE restaurantes ADD COLUMN pedidos_whatsapp VARCHAR(20) AFTER pedidos_envio_costo;
-- ALTER TABLE restaurantes ADD COLUMN pedidos_trans_clabe VARCHAR(18) AFTER pedidos_whatsapp;
-- ALTER TABLE restaurantes ADD COLUMN pedidos_trans_cuenta VARCHAR(20) AFTER pedidos_trans_clabe;
-- ALTER TABLE restaurantes ADD COLUMN pedidos_trans_titular VARCHAR(100) AFTER pedidos_trans_cuenta;
-- ALTER TABLE restaurantes ADD COLUMN pedidos_trans_banco VARCHAR(100) AFTER pedidos_trans_titular;
-- ALTER TABLE restaurantes ADD COLUMN pedidos_trans_activo TINYINT(1) NOT NULL DEFAULT 0 AFTER pedidos_trans_banco;
-- ALTER TABLE restaurantes ADD COLUMN compartir_mensaje TEXT AFTER pedidos_trans_activo;
-- Fase 9:
-- ALTER TABLE restaurantes ADD COLUMN pedidos_envio_gratis_desde DECIMAL(10,2) NULL DEFAULT NULL AFTER pedidos_envio_costo;
-- ALTER TABLE restaurantes ADD COLUMN pedidos_terminal_activo TINYINT(1) NOT NULL DEFAULT 0 AFTER pedidos_envio_gratis_desde;
-- ALTER TABLE pedidos MODIFY COLUMN metodo_pago ENUM('efectivo','transferencia','terminal') NOT NULL DEFAULT 'efectivo';
-- Fase 10:
-- ALTER TABLE restaurantes ADD COLUMN IF NOT EXISTS tienda_cerrada_manual TINYINT(1) NOT NULL DEFAULT 0;
-- ALTER TABLE restaurantes ADD COLUMN IF NOT EXISTS tienda_horarios JSON NULL DEFAULT NULL;
-- Fase 12:
-- ALTER TABLE restaurantes ADD COLUMN IF NOT EXISTS stock_minimo_aviso SMALLINT NOT NULL DEFAULT 5;
-- Fase 14 (descuentos en pedidos):
-- ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS descuento_recompensa DECIMAL(8,2) NOT NULL DEFAULT 0.00;
-- ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS descuento_promo DECIMAL(8,2) NOT NULL DEFAULT 0.00;
-- ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS codigo_promo VARCHAR(20) DEFAULT NULL;

-- ------------------------------------------------------------
-- TABLA: mesas
-- Cada restaurante tiene N mesas, cada una con su QR único.
-- El QR apunta a: /menu/?r={restaurante_slug}&mesa={numero}
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mesas (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id  INT UNSIGNED  NOT NULL,
  numero          VARCHAR(20)   NOT NULL,          -- "1", "2", "VIP", "Terraza-1"
  qr_generado     TINYINT(1)    NOT NULL DEFAULT 0, -- si ya se generó el QR
  activo          TINYINT(1)    NOT NULL DEFAULT 1,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_mesa_restaurante (restaurante_id, numero),
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: categorias
-- Agrupan los productos del menú (Entradas, Platos fuertes, etc.)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id  INT UNSIGNED  NOT NULL,
  nombre          VARCHAR(100)  NOT NULL,
  icono           VARCHAR(100),                    -- nombre de export MDI (ej: 'mdiPizza'). Antes era emoji unicode.
  orden           SMALLINT      NOT NULL DEFAULT 0, -- orden de aparición en menú
  activo          TINYINT(1)    NOT NULL DEFAULT 1,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: productos
-- Cada platillo del menú. El campo modelo_glb_path es la ruta
-- relativa dentro de /uploads/modelos/ (no URL completa).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS productos (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id          INT UNSIGNED  NOT NULL,
  nombre                VARCHAR(200)  NOT NULL,
  descripcion           TEXT,
  precio                DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  foto_principal        VARCHAR(500),                    -- ruta relativa /uploads/fotos/{id}/principal.jpg
  modelo_glb_path       VARCHAR(500),                    -- ruta relativa /uploads/modelos/modelo_{id}_{ts}.glb
  tiene_ar              TINYINT(1)    NOT NULL DEFAULT 0, -- 1 = modelo 3D listo y disponible
  es_destacado          TINYINT(1)    NOT NULL DEFAULT 0, -- para mostrar en sección especial
  disponible            TINYINT(1)    NOT NULL DEFAULT 1,
    -- 1 = producto activo y a la venta (normal)
    -- 0 = "Próximamente" — visible en el menú público pero sin botón de compra
    --     (diferente de activo=0 que es borrado lógico y oculta el producto)
  tiene_personalizacion TINYINT(1)    NOT NULL DEFAULT 0, -- 1 = usa PersonalizacionModal (Fase 7)
  aviso_complemento     TEXT          DEFAULT NULL,        -- texto del aviso de complemento (ej: "¿Bebida?")
  aviso_categoria_id    INT UNSIGNED  DEFAULT NULL,        -- categoría a la que lleva el botón "Ver"
  stock                 SMALLINT      DEFAULT NULL,
    -- NULL = sin control de stock (siempre disponible)
    -- 0    = stock agotado → menú público muestra overlay "No disponible"
    -- > 0  = unidades disponibles
  activo                TINYINT(1)    NOT NULL DEFAULT 1, -- borrado lógico
  orden                 SMALLINT      NOT NULL DEFAULT 0,
  created_at            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: fotos_producto
-- Las fotos adicionales de un producto (las que se mandan a Meshy).
-- La foto_principal queda en productos.foto_principal.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS fotos_producto (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id     INT UNSIGNED  NOT NULL,
  ruta            VARCHAR(500)  NOT NULL,           -- ruta relativa /uploads/fotos/{producto_id}/foto.jpg
  url_publica     VARCHAR(500)  NOT NULL,           -- URL absoluta para enviar a Meshy API
  orden           SMALLINT      NOT NULL DEFAULT 0,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: meshy_jobs
-- Cola de trabajos de conversión 3D. Un producto puede tener
-- múltiples intentos si falló anteriormente.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS meshy_jobs (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id     INT UNSIGNED  NOT NULL,
  meshy_task_id   VARCHAR(200)  NOT NULL,           -- ID devuelto por Meshy al crear el task
  status          ENUM('pending','processing','succeeded','failed') NOT NULL DEFAULT 'pending',
  intentos        SMALLINT      NOT NULL DEFAULT 0,  -- cuántas veces el cron lo consultó
  error_msg       TEXT,                             -- mensaje de error si falla
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: sesiones_admin
-- Tokens de sesión para el panel admin.
-- Simple: token aleatorio con expiración.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sesiones_admin (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id      INT UNSIGNED  NOT NULL,
  token           VARCHAR(64)   NOT NULL UNIQUE,    -- bin2hex(random_bytes(32))
  expira_en       DATETIME      NOT NULL,           -- NOW() + 8 horas
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: pedidos
-- Pedidos realizados por clientes (Fase 6).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pedidos (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id  INT UNSIGNED  NOT NULL,
  numero_pedido   VARCHAR(20)   NOT NULL,           -- formato YYYYMMDD-XXXX (ej: 20260305-0001)
  nombre_cliente  VARCHAR(100)  NOT NULL,
  telefono        VARCHAR(20),
  tipo_entrega    ENUM('recoger','envio') NOT NULL DEFAULT 'recoger',
  direccion       VARCHAR(200),
  metodo_pago     ENUM('efectivo','transferencia','terminal') NOT NULL DEFAULT 'efectivo',
  denominacion    DECIMAL(10,2),                    -- con cuánto paga (solo efectivo)
  mesa            VARCHAR(20),
  subtotal             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  costo_envio          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  descuento_recompensa DECIMAL(8,2)  NOT NULL DEFAULT 0.00,  -- descuento aplicado por recompensa de sellos
  descuento_promo      DECIMAL(8,2)  NOT NULL DEFAULT 0.00,  -- descuento aplicado por código de promotor
  codigo_promo         VARCHAR(20)   DEFAULT NULL,            -- código de promotor utilizado (snapshot)
  total                DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status          ENUM('nuevo','visto','en_preparacion','listo','entregado','cancelado') NOT NULL DEFAULT 'nuevo',
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: pedido_items
-- Líneas de cada pedido. nombre_producto es un snapshot al
-- momento del pedido (el producto puede editarse después).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pedido_items (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id       INT UNSIGNED  NOT NULL,
  producto_id     INT UNSIGNED,                     -- nullable: producto puede borrarse después
  nombre_producto VARCHAR(200)  NOT NULL,           -- snapshot del nombre al momento del pedido
  precio_unitario DECIMAL(10,2) NOT NULL,
  cantidad        SMALLINT      NOT NULL DEFAULT 1,
  observacion     VARCHAR(100),
  subtotal        DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: producto_grupos (Fase 7)
-- Grupos de opciones de un producto (ej: "Tamaño", "Base", "Extras")
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS producto_grupos (
  id                    INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  producto_id           INT UNSIGNED  NOT NULL,
  nombre                VARCHAR(100)  NOT NULL,
  tipo                  ENUM('radio','checkbox') NOT NULL DEFAULT 'radio',
  obligatorio           TINYINT(1)    NOT NULL DEFAULT 1,
  min_selecciones       SMALLINT      NOT NULL DEFAULT 1,
  max_selecciones       SMALLINT      NOT NULL DEFAULT 1,
  max_dinamico_grupo_id INT UNSIGNED  DEFAULT NULL,  -- FK al grupo radio que define el max
  orden                 SMALLINT      NOT NULL DEFAULT 0,
  activo                TINYINT(1)    NOT NULL DEFAULT 1,
  created_at            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  FOREIGN KEY (max_dinamico_grupo_id) REFERENCES producto_grupos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: producto_opciones (Fase 7)
-- Opciones dentro de un grupo (ej: "Mediano", "Arroz de sushi", "Salmón extra +$30")
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS producto_opciones (
  id           INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  grupo_id     INT UNSIGNED  NOT NULL,
  nombre       VARCHAR(100)  NOT NULL,
  precio_extra DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  max_override SMALLINT      DEFAULT NULL, -- si esta opción radio se elige, define el max del grupo dinámico
  orden        SMALLINT      NOT NULL DEFAULT 0,
  activo       TINYINT(1)    NOT NULL DEFAULT 1,
  created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (grupo_id) REFERENCES producto_grupos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: pedido_item_opciones (Fase 7)
-- Snapshot de opciones seleccionadas por línea de pedido
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pedido_item_opciones (
  id             INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  pedido_item_id INT UNSIGNED  NOT NULL,
  grupo_nombre   VARCHAR(100)  NOT NULL,   -- snapshot al momento del pedido
  opcion_nombre  VARCHAR(100)  NOT NULL,   -- snapshot
  precio_extra   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (pedido_item_id) REFERENCES pedido_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: recompensas_config (Fase 11)
-- Configuración del sistema de sellos/recompensas por restaurante.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS recompensas_config (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id    INT UNSIGNED  NOT NULL UNIQUE,
  activo            TINYINT(1)    NOT NULL DEFAULT 0,   -- toggle para activar el sistema
  compras_necesarias SMALLINT     NOT NULL DEFAULT 10,  -- sellos para completar un ciclo
  descripcion_recompensa VARCHAR(200) DEFAULT 'Pedido gratis',  -- qué obtiene el cliente
  tipo_descuento    ENUM('fijo','porcentaje','gratis') NOT NULL DEFAULT 'gratis',
  valor_descuento   DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
  created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: clientes (Fase 11)
-- Historial de compras por número de teléfono por restaurante.
-- Se crea/actualiza en cada pedido confirmado.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS clientes (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id  INT UNSIGNED  NOT NULL,
  telefono        VARCHAR(20)   NOT NULL,
  total_compras   SMALLINT      NOT NULL DEFAULT 0,  -- total acumulado de pedidos confirmados
  recompensas_ganadas SMALLINT  NOT NULL DEFAULT 0,  -- cuántos ciclos ya se canjearon
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_cliente_restaurante (restaurante_id, telefono),
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: codigos_promo (Fase 13)
-- Códigos de descuento creados por el restaurante para promotores.
-- El restaurante crea el código, lo entrega al promotor.
-- El promotor lo da a sus clientes, que lo ingresan en checkout.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS codigos_promo (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurante_id  INT UNSIGNED  NOT NULL,
  codigo          VARCHAR(20)   NOT NULL,
  descripcion     VARCHAR(200)  DEFAULT NULL,     -- ej: "Promotor Juan - 10% descuento"
  tipo            ENUM('fijo','porcentaje') NOT NULL DEFAULT 'fijo',
  valor           DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
  usos            INT UNSIGNED  NOT NULL DEFAULT 0,  -- contador acumulado de usos
  activo          TINYINT(1)    NOT NULL DEFAULT 1,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_codigo_restaurante (restaurante_id, codigo),
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
```

---

## 3. DIAGRAMA DE RELACIONES

```
usuarios
  └──< restaurantes (usuario_id)
         ├──< mesas (restaurante_id)
         └──< categorias (restaurante_id)
                └──< productos (categoria_id)
                       ├──< fotos_producto (producto_id)
                       ├──< meshy_jobs (producto_id)
                       └──< producto_grupos (producto_id)
                              └──< producto_opciones (grupo_id)

restaurantes
  ├──< pedidos (restaurante_id)
  │      └──< pedido_items (pedido_id)
  │             └──< pedido_item_opciones (pedido_item_id)  ← snapshot Fase 7
  ├──< recompensas_config (restaurante_id)  ← 1:1, Fase 11
  ├──< clientes (restaurante_id)            ← historial por teléfono, Fase 11
  └──< codigos_promo (restaurante_id)       ← códigos de promotor, Fase 13

usuarios
  └──< sesiones_admin (usuario_id)
```

---

## 4. ÍNDICES IMPORTANTES

```sql
-- Para búsquedas frecuentes del menú público
CREATE INDEX idx_restaurante_slug ON restaurantes(slug);
CREATE INDEX idx_categoria_restaurante ON categorias(restaurante_id, activo, orden);
CREATE INDEX idx_producto_categoria ON productos(categoria_id, activo, disponible, orden);
CREATE INDEX idx_meshy_status ON meshy_jobs(status, intentos);

-- Para el cron que busca jobs pendientes
CREATE INDEX idx_meshy_pendientes ON meshy_jobs(status) WHERE status IN ('pending', 'processing');
```

---

## 5. DATOS INICIALES (SEED)

```sql
-- Usuario superadmin inicial
INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (
  'Admin Principal',
  'admin@tudominio.com',
  -- Generar con: echo password_hash('TuPasswordSeguro123', PASSWORD_DEFAULT);
  '$2y$10$REEMPLAZAR_CON_HASH_REAL',
  'superadmin'
);

-- Restaurante de prueba
INSERT INTO restaurantes (usuario_id, slug, nombre, descripcion, color_primario) VALUES (
  1, 'restaurante-demo', 'Restaurante Demo', 'Menú de demostración', '#FF6B35'
);

-- Categorías de prueba
INSERT INTO categorias (restaurante_id, nombre, icono, orden) VALUES
  (1, 'Entradas', '🥗', 1),
  (1, 'Platos Fuertes', '🍖', 2),
  (1, 'Postres', '🍰', 3),
  (1, 'Bebidas', '🥤', 4);
```

---

## 6. CONSULTAS CLAVE DEL SISTEMA

### Menú público completo (la más usada)
```sql
SELECT
  r.nombre AS restaurante_nombre,
  r.logo_url,
  r.color_primario,
  c.id AS cat_id,
  c.nombre AS cat_nombre,
  c.icono AS cat_icono,
  c.orden AS cat_orden,
  p.id AS prod_id,
  p.nombre AS prod_nombre,
  p.descripcion,
  p.precio,
  p.foto_principal,
  p.modelo_glb_path,
  p.tiene_ar,
  p.es_destacado,
  p.disponible
FROM restaurantes r
JOIN categorias c ON c.restaurante_id = r.id AND c.activo = 1
JOIN productos p ON p.categoria_id = c.id AND p.activo = 1
WHERE r.slug = :slug AND r.activo = 1
ORDER BY c.orden ASC, p.orden ASC, p.nombre ASC;
```

### Mesas activas de un restaurante
```sql
SELECT m.id, m.numero, m.qr_generado,
       r.slug AS restaurante_slug, r.nombre AS restaurante_nombre
FROM mesas m
JOIN restaurantes r ON r.id = m.restaurante_id
WHERE m.restaurante_id = :rid AND m.activo = 1
ORDER BY CAST(m.numero AS UNSIGNED), m.numero;
-- Ordenamiento inteligente: Mesa 1,2,10 antes que "Terraza","VIP"
```

### Jobs pendientes para el cron
```sql
SELECT id, producto_id, meshy_task_id, intentos
FROM meshy_jobs
WHERE status IN ('pending', 'processing')
AND intentos < 30
ORDER BY created_at ASC;
```

### Estado de conversión de un producto (admin)
```sql
SELECT
  p.id, p.nombre, p.tiene_ar, p.modelo_glb_path,
  j.status AS job_status,
  j.intentos,
  j.error_msg,
  j.updated_at AS ultimo_check
FROM productos p
LEFT JOIN meshy_jobs j ON j.producto_id = p.id
WHERE p.id = :producto_id
ORDER BY j.created_at DESC
LIMIT 1;
```

---

## 7. REGLAS DE NEGOCIO EN BD

- Un producto puede tener **múltiples intentos de conversión** en `meshy_jobs`. El cron solo procesa los que están en `pending` o `processing`.
- **`productos.tiene_ar = 1`** se setea al subir un `.glb` válido (endpoint `upload-glb`) o cuando el cron descarga exitosamente el `.glb` de Meshy.
- **`productos.modelo_glb_path`** es solo el **nombre del archivo** (ej: `modelo_1_1234.glb`). URL completa: `UPLOADS_URL . 'modelos/' . $modelo_glb_path`.
- **`productos.stock`**: NULL = sin control de stock; 0 = agotado (overlay "No disponible" en menú); > 0 = unidades disponibles. La API descuenta con `GREATEST(0, stock - cantidad)` al confirmar el pedido. El frontend también valida antes de agregar al carrito.
- **`productos.disponible`**: 0 = "Próximamente" (visible sin botón de compra); 1 = normal. Distinto de `activo=0` (borrado lógico, no aparece).
- **`restaurantes.stock_minimo_aviso`**: umbral para badge "Últimas N piezas" en menú público. 0 = badge desactivado.
- **Sistema de recompensas (sellos)**: `clientes.total_compras` se incrementa por cada pedido. `ciclos_completados = FLOOR(total_compras / compras_necesarias)`. `tiene_recompensa = ciclos_completados > recompensas_ganadas`. Al aplicar recompensa: `recompensas_ganadas += 1`. **Cuidado:** cambiar `compras_necesarias` afecta a todos los clientes inmediatamente (incluidos los que tenían sellos acumulados).
- **Códigos de promotor**: el restaurante crea códigos en `codigos_promo`. Cliente los ingresa en checkout. API valida y devuelve descuento. `codigos_promo.usos` se incrementa en cada uso. Sin límite de usos por defecto.
- **Descuentos en pedidos**: `descuento_recompensa` y `descuento_promo` se guardan como snapshot al momento del pedido. `total = subtotal + costo_envio - descuento_recompensa - descuento_promo`.
- **`productos.foto_principal`** es relativo a `/uploads/` (ej: `fotos/1/foto_1_0_1234.jpg`). URL completa: `UPLOADS_URL . $foto_principal`. Se asigna automáticamente al subir la primera foto.
- **`fotos_producto.ruta`** es relativo al webroot (ej: `uploads/fotos/1/foto.jpg`). Solo para referencia interna.
- `fotos_producto.url_publica` es URL absoluta (históricamente para Meshy API, ahora solo referencia).
- Borrado siempre lógico (`activo = 0`). Nunca DELETE en producción.
- `mesas.numero` es VARCHAR — permite "1", "VIP", "Terraza-2". Unique por restaurante.
- La URL del QR de una mesa: `{BASE_URL}/menu/?r={restaurante.slug}&mesa={mesa.numero}`
- Las sesiones expiran en 8 horas. El cron también puede limpiar sesiones viejas si se quiere.
- Borrado siempre es lógico (`activo = 0`). No se hace DELETE en producción.

---

## 8. CONEXIÓN PDO EN PHP

```php
// config.php — fragmento de conexión
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            DB_HOST,   // 'localhost'
            DB_NAME    // 'usuario_menudb'
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
```

**Regla:** Siempre usar **prepared statements** con parámetros nombrados (`:param`). Nunca interpolar variables en el SQL.

---

## 9. NOTAS DE MANTENIMIENTO

- Si Meshy falla más de 30 veces en un job, el status queda en `processing` pero `intentos >= 30` lo excluye del cron. El admin puede ver el error en `error_msg` y reintentar borrando el job y subiendo nuevas fotos.
- Para limpiar sesiones vencidas manualmente: `DELETE FROM sesiones_admin WHERE expira_en < NOW();`
- Los archivos `.glb` pueden pesar 3-10MB. Monitorear el espacio en disco del cPanel.
- Si se necesita regenerar un modelo 3D: poner `productos.tiene_ar = 0`, `modelo_glb_path = NULL`, crear nuevo registro en `meshy_jobs`.

## 10. REPOSITORIO

Todo el código de este proyecto vive en: https://github.com/alexis-gd/menu_qr_3d

El script SQL completo de esta sección debe mantenerse actualizado en el repo como `database/schema.sql` conforme evolucione el esquema. Cualquier alteración de tablas se documenta aquí antes de ejecutarse en producción.
