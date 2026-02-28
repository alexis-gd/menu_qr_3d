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
  logo_url        VARCHAR(500),                   -- ruta relativa en /uploads/
  color_primario  VARCHAR(7)    DEFAULT '#FF6B35', -- color hex para UI del menú
  activo          TINYINT(1)    NOT NULL DEFAULT 1,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  icono           VARCHAR(50),                     -- emoji o nombre de ícono
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
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id    INT UNSIGNED  NOT NULL,
  nombre          VARCHAR(200)  NOT NULL,
  descripcion     TEXT,
  precio          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  foto_principal  VARCHAR(500),                    -- ruta relativa /uploads/fotos/{id}/principal.jpg
  modelo_glb_path VARCHAR(500),                    -- ruta relativa /uploads/modelos/modelo_{id}_{ts}.glb
  tiene_ar        TINYINT(1)    NOT NULL DEFAULT 0, -- 1 = modelo 3D listo y disponible
  es_destacado    TINYINT(1)    NOT NULL DEFAULT 0, -- para mostrar en sección especial
  disponible      TINYINT(1)    NOT NULL DEFAULT 1, -- disponibilidad del platillo hoy
  activo          TINYINT(1)    NOT NULL DEFAULT 1, -- borrado lógico
  orden           SMALLINT      NOT NULL DEFAULT 0,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
                       └──< meshy_jobs (producto_id)

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
