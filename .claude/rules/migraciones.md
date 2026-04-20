# .claude/rules/migraciones.md — Reglas de migraciones BD

## Obligatorio antes de cualquier cambio de esquema

Toda tabla nueva, columna nueva o cambio de esquema requiere un archivo SQL en `database/migrations/` **ANTES** de escribir código PHP/Vue que lo use.

## Nombrado

`faseN_descripcion.sql` — ej: `fase24_nueva_feature.sql`

El número de fase sigue la secuencia existente (última: fase23).

## Idempotencia — obligatorio

Usar `IF NOT EXISTS` / `IF EXISTS` para que la migración sea segura de re-ejecutar:

```sql
ALTER TABLE restaurantes
  ADD COLUMN IF NOT EXISTS nueva_columna VARCHAR(100) NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS nueva_tabla (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ...
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Orden de aplicación — obligatorio

**local → QA → prod**. Nunca aplicar en QA/prod sin validar en local primero.

Si falla algo en QA con "Unknown column 'X'": falta ejecutar la migración en ese entorno.

## BD demo

Las migraciones de esquema que afecten la BD prod (`nodosmxc_menu_qr_3d`) **también deben aplicarse** a la BD demo (`nodosmxc_menu_demos`) si la demo usa las mismas tablas.

Si la columna va en `init_demo_db.sql` (schema inicial), actualizarlo también.

## El archivo va al repo

La migración SQL va al repo. Las credenciales nunca.
