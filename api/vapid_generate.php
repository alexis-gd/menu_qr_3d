<?php
/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  GENERADOR DE CLAVES VAPID — ejecutar UNA SOLA VEZ         ║
 * ║  Luego copiar los valores a config.php y BORRAR este archivo║
 * ╚══════════════════════════════════════════════════════════════╝
 *
 * Uso local:   php api/vapid_generate.php
 * Uso web:     Abre https://tudominio.com/menu/api/vapid_generate.php
 *              (solo si vendor/ ya está en el servidor)
 *              ¡BORRAR EL ARCHIVO DESPUÉS DE USARLO!
 */

require_once __DIR__ . '/vendor/autoload.php';

// En Windows/XAMPP, OpenSSL no encuentra su cnf automáticamente
// El servidor Linux (cPanel) no necesita esto — no tiene efecto fuera de Windows
$openssl_cnf_paths = [
    'C:\\xampp\\apache\\conf\\openssl.cnf',
    'C:\\xampp\\php\\extras\\openssl\\openssl.cnf',
    '/etc/ssl/openssl.cnf',
];
foreach ($openssl_cnf_paths as $cnf) {
    if (file_exists($cnf)) {
        putenv("OPENSSL_CONF=$cnf");
        break;
    }
}

$keys = \Minishlink\WebPush\VAPID::createVapidKeys();

$public  = $keys['publicKey'];
$private = $keys['privateKey'];

// ── Salida según contexto ──────────────────────────────────────────────────

if (PHP_SAPI === 'cli') {
    echo "=== CLAVES VAPID GENERADAS ===\n\n";
    echo "PUBLIC KEY:\n$public\n\n";
    echo "PRIVATE KEY:\n$private\n\n";
    echo "Copia estos valores en tu config.php:\n\n";
    echo "  'vapid_public_key'  => '$public',\n";
    echo "  'vapid_private_key' => '$private',\n";
    echo "  'vapid_subject'     => 'mailto:TU@EMAIL.COM',\n\n";
    echo "IMPORTANTE: Borra este archivo del servidor cuando termines.\n";
} else {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">
    <title>VAPID Keys</title>
    <style>
      body { font-family: monospace; padding: 32px; background: #1a1a1a; color: #e0e0e0; }
      h2 { color: #e8631a; }
      .box { background: #2a2a2a; padding: 16px; border-radius: 8px; margin: 12px 0; word-break: break-all; }
      .warn { background: #5a1a00; padding: 16px; border-radius: 8px; color: #ffb085; font-size: 1.1em; margin-top: 24px; }
    </style></head><body>';
    echo '<h2>🔑 Claves VAPID generadas</h2>';
    echo '<p>Copia estos valores en tu <code>config.php</code>:</p>';
    echo '<div class="box"><strong>vapid_public_key</strong><br>' . htmlspecialchars($public) . '</div>';
    echo '<div class="box"><strong>vapid_private_key</strong><br>' . htmlspecialchars($private) . '</div>';
    echo '<div class="box"><strong>vapid_subject</strong><br>mailto:TU@EMAIL.COM</div>';
    echo '<div class="warn">⚠️ <strong>BORRA ESTE ARCHIVO DEL SERVIDOR</strong> cuando termines de copiar los valores.<br>';
    echo 'Ruta: <code>public_html/menu/api/vapid_generate.php</code></div>';
    echo '</body></html>';
}
