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
        'expires'  => 0,           // cookie de sesión (desaparece al cerrar el browser)
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
