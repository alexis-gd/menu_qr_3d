<?php
/**
 * Plantilla de configuración multi-entorno — config.example.php
 *
 * Este archivo SÍ se sube al repositorio (sin credenciales reales).
 *
 * INSTRUCCIONES PARA NUEVO SERVIDOR:
 *   1. Copia: config.example.php → config.php
 *   2. Copia: env.example.php    → env.php
 *   3. En env.php define el entorno: define('APP_ENV', 'qa'); // o 'prod'
 *   4. En config.php rellena las credenciales del entorno correspondiente
 *   5. Ambos archivos están en .gitignore — NUNCA subir al repositorio
 *
 * USO en cualquier archivo PHP:
 *   config('db.host')              → 'localhost'
 *   config('app.url')              → 'https://midominio.com'
 *   config('paths.uploads_url')    → 'https://midominio.com/menu/uploads/'
 *   config('services.admin_token') → 'mi_token_secreto'
 *   config('app.debug')            → true | false
 *   config('no.existe', 'default') → 'default'  (segundo argumento = default)
 */


// ══════════════════════════════════════════════════════════════════════════════
// 1. DETECCIÓN DE ENTORNO
// ══════════════════════════════════════════════════════════════════════════════

if (!defined('APP_ENV')) {
    if (file_exists(__DIR__ . '/env.php')) {
        require_once __DIR__ . '/env.php';
    }
    if (!defined('APP_ENV')) {
        $envValue = getenv('APP_ENV') ?: ($_SERVER['APP_ENV'] ?? 'local');
        define('APP_ENV', in_array($envValue, ['local', 'qa', 'prod'], true) ? $envValue : 'local');
    }
}


// ══════════════════════════════════════════════════════════════════════════════
// 2. CONFIGURACIONES POR ENTORNO
// ══════════════════════════════════════════════════════════════════════════════

$_CONFIGS = [

    // ── BASE: valores por defecto (heredados por todos los entornos) ──────
    '_base' => [
        'app' => [
            'name'     => 'Menu QR 3D',
            'version'  => '1.0.0',
            'env'      => APP_ENV,
            'debug'    => false,         // false en prod, true en local/qa
            'log'      => false,
            'timezone' => 'America/Mexico_City',
            'url'      => '',            // URL base del servidor (sin slash final)
        ],
        'db' => [
            'host'    => 'localhost',    // en cPanel siempre es localhost
            'charset' => 'utf8mb4',
            'name'    => '',
            'user'    => '',
            'pass'    => '',
        ],
        'paths' => [
            'storage'     => __DIR__ . '/../uploads',
            'fotos'       => __DIR__ . '/../uploads/fotos',
            'modelos'     => __DIR__ . '/../uploads/modelos',
            'uploads_url' => '',         // URL pública de uploads (con slash final)
        ],
        'services' => [
            'admin_token'   => '',       // token estático para autenticación del panel
            'meshy_api_key' => '',       // API key de meshy.ai para modelos 3D
        ],
    ],

    // ── LOCAL: XAMPP en máquina de desarrollo ─────────────────────────────
    'local' => [
        'app' => [
            'debug' => true,
            'log'   => true,
            'url'   => 'http://menu.local',
        ],
        'db' => [
            'name' => 'menu_qr_3d',      // nombre de BD en XAMPP local
            'user' => 'root',
            'pass' => '',                // sin contraseña en XAMPP por defecto
        ],
        'paths' => [
            'uploads_url' => 'http://menu.local/uploads/',
        ],
        'services' => [
            'admin_token' => 'CAMBIA_ESTE_TOKEN_LOCAL',
        ],
    ],

    // ── QA: servidor de pruebas ────────────────────────────────────────────
    'qa' => [
        'app' => [
            'debug' => true,             // true en QA para ver errores
            'log'   => true,
            'url'   => 'https://tu-dominio-qa.com',
        ],
        'db' => [
            'name' => 'cpanel_nombre_bd_qa',
            'user' => 'cpanel_usuario_bd_qa',
            'pass' => 'CONTRASENA_QA',
        ],
        'paths' => [
            'uploads_url' => 'https://tu-dominio-qa.com/menu/uploads/',
        ],
        'services' => [
            'admin_token'   => 'CAMBIA_ESTE_TOKEN_QA',
            'meshy_api_key' => '',
        ],
    ],

    // ── PROD: cliente final ────────────────────────────────────────────────
    'prod' => [
        'app' => [
            'debug' => false,            // NUNCA true en producción
            'log'   => false,
            'url'   => 'https://dominio-cliente.com',
        ],
        'db' => [
            'name' => 'cpanel_nombre_bd_prod',
            'user' => 'cpanel_usuario_bd_prod',
            'pass' => 'CONTRASENA_PROD',
        ],
        'paths' => [
            'uploads_url' => 'https://dominio-cliente.com/menu/uploads/',
        ],
        'services' => [
            'admin_token'   => 'CAMBIA_ESTE_TOKEN_PROD',
            'meshy_api_key' => 'MESHY_API_KEY_REAL',
        ],
    ],

];


// ══════════════════════════════════════════════════════════════════════════════
// 3. MERGE: base + entorno activo
// ══════════════════════════════════════════════════════════════════════════════

$_activeConfig = array_replace_recursive(
    $_CONFIGS['_base'],
    $_CONFIGS[APP_ENV] ?? $_CONFIGS['local']
);
unset($_CONFIGS);


// ══════════════════════════════════════════════════════════════════════════════
// 4. FUNCIÓN GLOBAL config()
// ══════════════════════════════════════════════════════════════════════════════

function config(string $key, mixed $default = null): mixed
{
    global $_activeConfig;
    $parts   = explode('.', $key, 3);
    $current = $_activeConfig;
    foreach ($parts as $part) {
        if (!is_array($current) || !array_key_exists($part, $current)) {
            return $default;
        }
        $current = $current[$part];
    }
    return $current;
}


// ══════════════════════════════════════════════════════════════════════════════
// 5. RUNTIME PHP
// ══════════════════════════════════════════════════════════════════════════════

date_default_timezone_set(config('app.timezone', 'America/Mexico_City'));

if (config('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}


// ══════════════════════════════════════════════════════════════════════════════
// 6. CONSTANTES GLOBALES (compatibilidad con código existente)
// ══════════════════════════════════════════════════════════════════════════════

define('DB_HOST',       config('db.host'));
define('DB_NAME',       config('db.name'));
define('DB_USER',       config('db.user'));
define('DB_PASS',       config('db.pass'));
define('BASE_URL',      config('app.url'));
define('UPLOADS_URL',   config('paths.uploads_url'));
define('MESHY_API_KEY', config('services.meshy_api_key'));
define('ADMIN_TOKEN',   config('services.admin_token'));


// ══════════════════════════════════════════════════════════════════════════════
// 7. VALIDACIÓN MÍNIMA
// ══════════════════════════════════════════════════════════════════════════════

(function () {
    $required = ['db.name', 'db.user', 'services.admin_token', 'app.url', 'paths.uploads_url'];
    $missing  = array_filter($required, fn($k) => empty(config($k)));

    if ($missing) {
        header('Content-Type: application/json; charset=utf-8', true, 500);
        $detail = config('app.debug') ? array_values($missing) : [];
        echo json_encode(['error' => 'Configuración incompleta', 'claves' => $detail]);
        exit;
    }
})();


// ══════════════════════════════════════════════════════════════════════════════
// 8. CONEXIÓN PDO
// ══════════════════════════════════════════════════════════════════════════════

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, config('db.charset', 'utf8mb4')),
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8', true, 500);
    $msg = config('app.debug') ? $e->getMessage() : 'Error de conexión a la base de datos';
    echo json_encode(['error' => $msg]);
    exit;
}
