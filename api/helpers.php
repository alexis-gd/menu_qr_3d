<?php
// Funciones reutilizables para la API.

/**
 * Envia una respuesta JSON adecuada y termina la ejecucion.
 *
 * @param mixed $data  Lo que se convertira con json_encode
 * @param int   $code  Codigo HTTP (por defecto 200)
 */
function json_response($data, $code = 200)
{
    header('Content-Type: application/json; charset=utf-8', true, $code);
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo json_encode($data);
    exit;
}

/**
 * Emite la cookie de sesion admin (HttpOnly, SameSite=Strict).
 * Secure se activa automaticamente cuando la conexion es HTTPS.
 *
 * @param string $token  Valor del token (ADMIN_TOKEN)
 */
function set_auth_cookie($token)
{
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (($_SERVER['SERVER_PORT'] ?? 80) == 443);

    setcookie('token', $token, [
        'expires'  => time() + 86400 * 7,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
        'secure'   => $secure,
    ]);
}

/**
 * Borra la cookie de sesion admin.
 */
function clear_auth_cookie()
{
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (($_SERVER['SERVER_PORT'] ?? 80) == 443);

    setcookie('token', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
        'secure'   => $secure,
    ]);
}

/**
 * Comprueba si una columna existe en la tabla indicada.
 * Usa cache por request para evitar consultar el schema repetidamente.
 */
function db_column_exists(PDO $pdo, string $table, string $column): bool
{
    static $cache = [];

    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT 1
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
               AND COLUMN_NAME = :column
             LIMIT 1'
        );
        $stmt->execute([
            ':table' => $table,
            ':column' => $column,
        ]);
        $cache[$key] = (bool) $stmt->fetchColumn();
    } catch (\Throwable $e) {
        $cache[$key] = false;
    }

    return $cache[$key];
}

/**
 * Envia notificaciones push a todos los dispositivos suscritos del restaurante.
 * Falla silenciosamente si la libreria no esta instalada o VAPID no esta configurado.
 *
 * @param PDO    $pdo            Conexion activa
 * @param int    $restaurante_id ID del restaurante
 * @param string $numero_pedido  Folio del pedido (ej: "20260330-AB1C")
 */
function notify_new_order(PDO $pdo, int $restaurante_id, string $numero_pedido): void
{
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($autoload)) return;

    $pub  = defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : '';
    $priv = defined('VAPID_PRIVATE_KEY') ? VAPID_PRIVATE_KEY : '';
    $sub  = defined('VAPID_SUBJECT') ? VAPID_SUBJECT : '';
    if (!$pub || !$priv || !$sub) return;

    require_once $autoload;

    try {
        $webPush = new \Minishlink\WebPush\WebPush([
            'VAPID' => [
                'subject' => $sub,
                'publicKey' => $pub,
                'privateKey' => $priv,
            ],
        ]);

        $stmt = $pdo->prepare(
            'SELECT endpoint, subscription_data FROM push_subscriptions WHERE restaurante_id = :rid'
        );
        $stmt->execute([':rid' => $restaurante_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) return;

        $stmtRest = $pdo->prepare(
            'SELECT logo_url FROM restaurantes WHERE id = :id LIMIT 1'
        );
        $stmtRest->execute([':id' => $restaurante_id]);
        $rest = $stmtRest->fetch(PDO::FETCH_ASSOC);
        $logoUrl = !empty($rest['logo_url']) ? UPLOADS_URL . $rest['logo_url'] : null;

        $payload = json_encode([
            'title' => 'Nuevo pedido',
            'body'  => "Pedido #{$numero_pedido} - abrelo en el panel",
            'url'   => '/menu/admin/dashboard',
            'icon'  => $logoUrl,
            'badge' => $logoUrl,
        ]);

        $failedEndpoints = [];

        foreach ($rows as $row) {
            $data = json_decode($row['subscription_data'], true);
            if (!$data || empty($data['endpoint']) || empty($data['keys']['p256dh']) || empty($data['keys']['auth'])) {
                continue;
            }
            $subscription = \Minishlink\WebPush\Subscription::create([
                'endpoint'  => $data['endpoint'],
                'publicKey' => $data['keys']['p256dh'],
                'authToken' => $data['keys']['auth'],
            ]);
            $webPush->queueNotification($subscription, $payload);
        }

        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                $failedEndpoints[] = $report->getEndpoint();
            }
        }

        foreach ($failedEndpoints as $ep) {
            $pdo->prepare('DELETE FROM push_subscriptions WHERE endpoint = :ep')
                ->execute([':ep' => $ep]);
        }
    } catch (\Throwable $e) {
        // Silencioso: push no debe afectar la creacion del pedido
    }
}

/**
 * Crea un token de sesión firmado que incluye uid y rid.
 * Formato: base64(uid:rid) + '.' + hmac(24 chars)
 */
function create_session_token(int $uid, ?int $rid): string
{
    $payload = base64_encode("$uid:" . ($rid ?? ''));
    $sig     = substr(hash_hmac('sha256', $payload, ADMIN_TOKEN), 0, 24);
    return "$payload.$sig";
}

/**
 * Valida el token y retorna ['uid' => int|null, 'rid' => int|null].
 * Acepta token estático legacy (ADMIN_TOKEN) para instancias de un solo restaurante.
 * Retorna null si el token es inválido.
 */
function parse_session_token(string $token): ?array
{
    // Backwards compat: token estático sin info de usuario
    if ($token === ADMIN_TOKEN) {
        return ['uid' => null, 'rid' => null];
    }
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) return null;
    [$payload, $sig] = $parts;
    $expected = substr(hash_hmac('sha256', $payload, ADMIN_TOKEN), 0, 24);
    if (!hash_equals($expected, $sig)) return null;
    $decoded = base64_decode($payload);
    if ($decoded === false || !str_contains($decoded, ':')) return null;
    [$uid, $rid] = explode(':', $decoded, 2);
    return [
        'uid' => $uid !== '' ? (int)$uid : null,
        'rid' => $rid !== '' ? (int)$rid : null,
    ];
}

/**
 * Comprueba si la peticion esta autenticada via cookie HttpOnly.
 * En caso de fallar finaliza con 401.
 * Retorna array ['uid' => int|null, 'rid' => int|null].
 */
function require_auth(): array
{
    $token   = $_COOKIE['token'] ?? null;
    $session = $token ? parse_session_token($token) : null;

    if ($session === null) {
        json_response(['error' => 'No autorizado'], 401);
    }
    return $session;
}
