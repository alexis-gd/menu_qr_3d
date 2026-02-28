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
 * Extrae el token de autenticación (Bearer) del header Authorization.
 * Busca en múltiples fuentes (getallheaders, $_SERVER, etc).
 *
 * @return string|null
 */
function get_bearer_token()
{
    $auth = null;

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (!empty($headers['Authorization'])) {
            $auth = $headers['Authorization'];
        }
    }

    if (!$auth && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
    }

    if (!$auth && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }

    if ($auth && preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        return trim($matches[1]);
    }
    return null;
}

/**
 * Comprueba si la petición está autenticada con ADMIN_TOKEN.
 * Retorna true/false y en caso de false finalizará con 401.
 */
function require_auth()
{
    $token = get_bearer_token();
    
    // TEMPORAL: permitir token por query string para debug
    if (!$token && !empty($_GET['token'])) {
        $token = $_GET['token'];
    }
    
    $expected = ADMIN_TOKEN;
    
    if ($token !== $expected) {
        json_response(['error' => 'No autorizado'], 401);
    }
}
