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
 *
 * @return string|null
 */
function get_bearer_token()
{
    $headers = getallheaders();
    if (!empty($headers['Authorization'])) {
        if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
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
    if ($token !== ADMIN_TOKEN) {
        json_response(['error' => 'No autorizado'], 401);
    }
}
