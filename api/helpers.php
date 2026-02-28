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
    
    // DEBUG: ver estructura de $_SERVER
    error_log('DEBUG $_SERVER keys: ' . json_encode(array_keys($_SERVER)));
    
    // Intenta getallheaders() primero
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        error_log('DEBUG getallheaders: ' . json_encode($headers));
        if (!empty($headers['Authorization'])) {
            $auth = $headers['Authorization'];
        }
    }
    
    // Si no, intenta $_SERVER
    if (!$auth && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
        error_log('DEBUG HTTP_AUTHORIZATION found');
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
    }
    
    // Si aún no, intenta alternativa
    if (!$auth && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        error_log('DEBUG REDIRECT_HTTP_AUTHORIZATION found');
        $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    
    error_log('DEBUG final auth: ' . $auth);
    
    if ($auth) {
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return trim($matches[1]);
        }
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
        json_response(['error' => 'No autorizado', 'token_received' => $token], 401);
    }
}
