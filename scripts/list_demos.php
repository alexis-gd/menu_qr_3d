<?php
/**
 * list_demos.php — Lista demos creadas desde demo_registros
 *
 * Uso:
 *   php scripts/list_demos.php
 *   php scripts/list_demos.php --estado=activa
 *   php scripts/list_demos.php --estado=expirada
 */

$DEMO_DB = [
    'host' => 'localhost',
    'name' => 'nodosmxc_menu_demos',
    'user' => 'root',
    'pass' => '',
];

$opts = getopt('', ['estado::']);
$estado = isset($opts['estado']) ? trim((string) $opts['estado']) : '';

try {
    $pdo = new PDO(
        "mysql:host={$DEMO_DB['host']};dbname={$DEMO_DB['name']};charset=utf8mb4",
        $DEMO_DB['user'],
        $DEMO_DB['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    echo "\nNo se pudo conectar a la BD demo: {$e->getMessage()}\n\n";
    exit(1);
}

$sql = '
    SELECT
        dr.id,
        dr.estado,
        dr.template,
        dr.slug,
        dr.nombre,
        dr.email,
        dr.whatsapp,
        dr.trial_dias,
        dr.trial_expires_at,
        dr.created_at,
        r.id AS restaurante_id
    FROM demo_registros dr
    LEFT JOIN restaurantes r ON r.id = dr.restaurante_id
';

$params = [];
if ($estado !== '') {
    $sql .= ' WHERE dr.estado = :estado';
    $params[':estado'] = $estado;
}

$sql .= ' ORDER BY dr.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

if (!$rows) {
    echo "\nSin demos registradas";
    if ($estado !== '') echo " con estado '$estado'";
    echo ".\n\n";
    exit(0);
}

echo "\nREGISTRO DE DEMOS\n";
echo str_repeat('=', 100) . "\n";
printf("%-5s %-10s %-10s %-20s %-22s %-12s %-12s\n", 'ID', 'Estado', 'Template', 'Slug', 'Email', 'Expira', 'RestID');
echo str_repeat('-', 100) . "\n";

foreach ($rows as $row) {
    $expira = $row['trial_expires_at'] ? date('d/m/Y', strtotime($row['trial_expires_at'])) : '-';
    printf(
        "%-5s %-10s %-10s %-20s %-22s %-12s %-12s\n",
        $row['id'],
        $row['estado'],
        $row['template'],
        mb_strimwidth($row['slug'], 0, 20, ''),
        mb_strimwidth($row['email'], 0, 22, ''),
        $expira,
        $row['restaurante_id'] ?? '-'
    );
}

echo str_repeat('=', 100) . "\n\n";
