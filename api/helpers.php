<?php
// Funciones reutilizables para la API.

/**
 * Envía una respuesta JSON adecuada y termina la ejecución.
 *
 * @param mixed $data  Lo que se convertirá con json_encode
 * @param int   $code  Código HTTP (por defecto 200)
 */
function json_response($data, $code = 200)
{
    header('Content-Type: application/json; charset=utf-8', true, $code);
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo json_encode($data);
    exit;
}

/**
 * Emite la cookie de sesión admin (HttpOnly, SameSite=Strict).
 * Secure se activa automáticamente cuando la conexión es HTTPS.
 *
 * @param string $token  Valor del token (ADMIN_TOKEN)
 */
function set_auth_cookie($token)
{
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (($_SERVER['SERVER_PORT'] ?? 80) == 443);

    setcookie('token', $token, [
        'expires'  => time() + 86400 * 7, // 7 días
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
        'secure'   => $secure,
    ]);
}

/**
 * Borra la cookie de sesión admin.
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
 * Envía notificaciones push a todos los dispositivos suscritos del restaurante.
 * Falla silenciosamente si la librería no está instalada o VAPID no está configurado.
 *
 * @param PDO    $pdo            Conexión activa
 * @param int    $restaurante_id ID del restaurante
 * @param string $numero_pedido  Folio del pedido (ej: "20260330-AB1C")
 */
function notify_new_order(PDO $pdo, int $restaurante_id, string $numero_pedido): void
{
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($autoload)) return;

    $pub  = defined('VAPID_PUBLIC_KEY')  ? VAPID_PUBLIC_KEY  : '';
    $priv = defined('VAPID_PRIVATE_KEY') ? VAPID_PRIVATE_KEY : '';
    $sub  = defined('VAPID_SUBJECT')     ? VAPID_SUBJECT     : '';
    if (!$pub || !$priv || !$sub) return;

    require_once $autoload;

    try {
        $webPush = new \Minishlink\WebPush\WebPush([
            'VAPID' => [
                'subject'    => $sub,
                'publicKey'  => $pub,
                'privateKey' => $priv,
            ],
        ]);

        $stmt = $pdo->prepare(
            'SELECT endpoint, subscription_data FROM push_subscriptions WHERE restaurante_id = :rid'
        );
        $stmt->execute([':rid' => $restaurante_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) return;

        $payload = json_encode([
            'title' => '🛎️ Nuevo pedido',
            'body'  => "Pedido #{$numero_pedido} — ¡ábrelo en el panel!",
            'url'   => '/menu/admin/dashboard',
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

        // Limpiar suscripciones caducadas/inválidas
        foreach ($failedEndpoints as $ep) {
            $pdo->prepare('DELETE FROM push_subscriptions WHERE endpoint = :ep')
                ->execute([':ep' => $ep]);
        }
    } catch (\Throwable $e) {
        // Silencioso — push no debe afectar la creación del pedido
    }
}

/**
 * Comprueba si la petición está autenticada con ADMIN_TOKEN via cookie HttpOnly.
 * En caso de fallar finaliza con 401.
 */
function require_auth()
{
    $token = $_COOKIE['token'] ?? null;

    if (!$token || $token !== ADMIN_TOKEN) {
        json_response(['error' => 'No autorizado'], 401);
    }
}
