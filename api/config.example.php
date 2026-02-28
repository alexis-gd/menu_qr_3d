<?php
// Copiar este archivo a config.php y rellenar con valores reales en local/servidor.

// --- Base de datos ----------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'nodosmxc_menu_qr_3d');
define('DB_USER', 'root');
define('DB_PASS', '');

// URL base del servidor PHP/Apache (sin slash final).
// En desarrollo local con XAMPP usar: http://menu.local
// En producción cambiar a: https://tudominio.com
define('BASE_URL', 'http://menu.local');

// URL pública de la carpeta uploads (sin slash final).
// Se deriva de BASE_URL; cambiar si uploads está en otra ubicación.
define('UPLOADS_URL', BASE_URL . '/uploads/');

// --- Meshy API ------------------------------------------------------------
// Necesitas registrarte en https://meshy.ai y obtener una API key.
// Se usará para generar modelos 3D a partir de imágenes.
define('MESHY_API_KEY', 'pon_aqui_tu_api_key');

// Token estático para autenticación de administrador. Se puede dejar por defecto
// y cambiar más tarde (no es seguro, es solo para la fase inicial).
define('ADMIN_TOKEN', 'mi_token_secreto_cambia_esto');

// ---------------------------------------------------------------------------
// Conexión PDO genérica. La variable $pdo queda disponible globalmente.
// Las excepciones se muestran crudamente porque sólo es para desarrollo/local.
// ---------------------------------------------------------------------------
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME),
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8', true, 500);
    echo json_encode(['error' => 'No se pudo conectar a la base de datos', 'message' => $e->getMessage()]);
    exit;
}
