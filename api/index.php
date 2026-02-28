<?php
// Router principal de la API. Todas las rutas se determinan por $_GET['route'].
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Respuesta por defecto en JSON
header('Content-Type: application/json; charset=utf-8');

$route = $_GET['route'] ?? '';

switch ($route) {
    case 'menu':
        // Endpoint público que devuelve el menú.
        // En fase 1 devolvemos datos dummy.
        $dummy = [
            'restaurante' => 'Demo Restaurant',
            'slug' => 'demo',
            'categorias' => [
                [
                    'id' => 1,
                    'nombre' => 'Entradas',
                    'orden' => 0,
                    'productos' => [
                        [
                            'id' => 1,
                            'nombre' => 'Tacos al pastor',
                            'descripcion' => 'Tacos tradicionales con piña y cebolla.',
                            'precio' => 99.90,
                            'foto_principal' => BASE_URL . '/imgs/taco.jpg',
                            'tiene_ar' => 0,
                        ],
                    ],
                ],
            ],
        ];
        json_response($dummy);
        break;

    case 'login':
        // Login admin: espera JSON { email, password }
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $email = $body['email'] ?? null;
        $password = $body['password'] ?? null;

        if (!$email || !$password) {
            json_response(['error' => 'Faltan credenciales'], 400);
        }

        // Buscar usuario en BD
        $stmt = $pdo->prepare('SELECT id, password_hash, activo FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !$user['activo']) {
            json_response(['error' => 'Usuario no encontrado'], 401);
        }

        if (!password_verify($password, $user['password_hash'])) {
            json_response(['error' => 'Credenciales inválidas'], 401);
        }

        // Para simplicidad devolvemos el token estático definido en config.php
        json_response(['token' => ADMIN_TOKEN]);
        break;

    case 'restaurantes':
        // GET: lista restaurantes (auth requerida)
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require_auth();
            $stmt = $pdo->prepare('SELECT id, slug, nombre, descripcion, logo_url, color_primario FROM restaurantes WHERE activo = 1 ORDER BY nombre');
            $stmt->execute();
            $rows = $stmt->fetchAll();
            json_response(['restaurantes' => $rows]);
        }

        // POST: crear restaurante (auth requerida)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_auth();
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $nombre = $body['nombre'] ?? null;
            $slug = $body['slug'] ?? null;
            $descripcion = $body['descripcion'] ?? null;

            if (!$nombre || !$slug) {
                json_response(['error' => 'nombre y slug son requeridos'], 400);
            }

            $stmt = $pdo->prepare('INSERT INTO restaurantes (usuario_id, slug, nombre, descripcion, activo) VALUES (:usuario_id, :slug, :nombre, :descripcion, 1)');
            // Por ahora usamos usuario_id = 1 por simplicidad
            $stmt->execute([
                ':usuario_id' => 1,
                ':slug' => $slug,
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
            ]);

            $id = $pdo->lastInsertId();
            json_response(['id' => (int)$id], 201);
        }

        // Otros métodos no soportados
        json_response(['error' => 'Método no soportado'], 405);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Ruta no encontrada']);
        break;
}
