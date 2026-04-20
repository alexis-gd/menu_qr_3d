# .claude/rules/seguridad.md — Reglas de seguridad

## api/config.php — NUNCA al repo

- Contiene credenciales reales (DB, API keys, tokens)
- Está en `.gitignore`. Verificar antes de cualquier commit que lo involucre.
- El repo es **público**. Cualquier credencial expuesta es permanente (git guarda historial).
- Para configurar en servidor nuevo: copiar `api/config.example.php` → `api/config.php`

## Prepared statements — OBLIGATORIO

Siempre PDO con parámetros nombrados. Nunca interpolación en SQL:

```php
// ✅ Correcto
$stmt = db()->prepare('SELECT * FROM restaurantes WHERE slug = :slug AND activo = 1');
$stmt->execute([':slug' => $slug]);

// ❌ Nunca
$query = "SELECT * FROM restaurantes WHERE slug = '$slug'";
```

## Borrado lógico — NUNCA DELETE en producción

```php
// ✅ Borrado lógico
$stmt = db()->prepare('UPDATE productos SET activo = 0 WHERE id = :id');

// ❌ Nunca en producción
$stmt = db()->prepare('DELETE FROM productos WHERE id = :id');
```

## Cookies HttpOnly — autenticación del admin

- Token de sesión en cookie `HttpOnly; SameSite=Strict` (nunca en localStorage)
- `require_auth()` en `helpers.php` lee `$_COOKIE['token']`
- Todos los fetch del admin usan `credentials: 'include'`

## Validación en PHP — nunca confiar en frontend

- Validar tipos, rangos y existencia de registros en el servidor
- `array_key_exists($key, $body)` en lugar de `isset()` cuando el valor puede ser NULL explícito

## Uploads — solo archivos permitidos

- Fotos: validar MIME type real (no solo extensión)
- `.glb`: validar magic bytes `glTF` en los primeros 4 bytes
- Nunca ejecutar archivos subidos
