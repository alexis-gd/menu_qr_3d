<?php
// Copiar este archivo a config.php y rellenar con valores reales en local/servidor.

// --- Base de datos ----------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'nodosmxc_menu_qr_3d');
define('DB_USER', 'root');
define('DB_PASS', '');

// URL base del proyecto (sin slash final).
// En desarrollo local con Vite dev server usar: http://localhost:5173
// En producción cambiar a: https://tudominio.com/menu (o similar)
define('BASE_URL', 'http://localhost:5173');

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
