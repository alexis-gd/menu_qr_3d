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

    case 'categorias':
        require_auth();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $restaurante_id = $_GET['restaurante_id'] ?? null;
            if (!$restaurante_id) {
                json_response(['error' => 'restaurante_id requerido'], 400);
            }
            $stmt = $pdo->prepare('SELECT * FROM categorias WHERE restaurante_id = :rid AND activo = 1 ORDER BY orden');
            $stmt->execute([':rid' => $restaurante_id]);
            $rows = $stmt->fetchAll();
            json_response(['categorias' => $rows]);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $restaurante_id = $body['restaurante_id'] ?? null;
            $nombre = $body['nombre'] ?? null;
            $icono = $body['icono'] ?? null;
            $orden = $body['orden'] ?? 0;
            if (!$restaurante_id || !$nombre) {
                json_response(['error' => 'restaurante_id y nombre requeridos'], 400);
            }
            $stmt = $pdo->prepare('INSERT INTO categorias (restaurante_id,nombre,icono,orden,activo) VALUES (:rid,:n,:i,:o,1)');
            $stmt->execute([':rid'=>$restaurante_id,':n'=>$nombre,':i'=>$icono,':o'=>$orden]);
            json_response(['id'=>$pdo->lastInsertId()],201);
        }
        json_response(['error'=>'Método no soportado'],405);
        break;

    case 'productos':
        require_auth();
        // GET
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $restaurante_id = $_GET['restaurante_id'] ?? null;
            if (!$restaurante_id) {
                json_response(['error' => 'restaurante_id requerido'], 400);
            }
            $stmt = $pdo->prepare(
                'SELECT p.* FROM productos p
                 JOIN categorias c ON p.categoria_id = c.id
                 WHERE c.restaurante_id = :rid AND p.activo = 1
                 ORDER BY p.orden'
            );
            $stmt->execute([':rid' => $restaurante_id]);
            $products = $stmt->fetchAll();
            // attach last mesh job status
            foreach ($products as &$prod) {
                if (!$prod['tiene_ar']) {
                    $j = $pdo->prepare('SELECT status FROM meshy_jobs WHERE producto_id=:pid ORDER BY created_at DESC LIMIT 1');
                    $j->execute([':pid'=>$prod['id']]);
                    $row = $j->fetch();
                    $prod['mesh_status'] = $row['status'] ?? null;
                } else {
                    $prod['mesh_status'] = 'succeeded';
                }
            }
            json_response(['productos' => $products]);
        }
        // POST crear
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $categoria_id = $body['categoria_id'] ?? null;
            $nombre = $body['nombre'] ?? null;
            $descripcion = $body['descripcion'] ?? null;
            $precio = $body['precio'] ?? 0;
            $es_destacado = $body['es_destacado'] ? 1 : 0;
            $disponible = isset($body['disponible']) ? ($body['disponible'] ? 1 : 0) : 1;
            if (!$categoria_id || !$nombre) {
                json_response(['error'=>'categoria_id y nombre requeridos'],400);
            }
            $stmt = $pdo->prepare('INSERT INTO productos (categoria_id,nombre,descripcion,precio,es_destacado,disponible,activo) VALUES (:cid,:n,:d,:p,:ed,:disp,1)');
            $stmt->execute([':cid'=>$categoria_id,':n'=>$nombre,':d'=>$descripcion,':p'=>$precio,':ed'=>$es_destacado,':disp'=>$disponible]);
            json_response(['id'=>$pdo->lastInsertId()],201);
        }
        // PUT actualizar
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            parse_str(file_get_contents('php://input'), $body);
            $id = $_GET['id'] ?? null;
            if (!$id) {
                json_response(['error'=>'id es requerido'],400);
            }
            $fields = [];
            $params = [':id'=>$id];
            foreach (['categoria_id','nombre','descripcion','precio','es_destacado','disponible','orden'] as $f) {
                if (isset($body[$f])) {
                    $fields[] = "$f = :$f";
                    $params[":$f"] = $body[$f];
                }
            }
            if ($fields) {
                $sql = 'UPDATE productos SET '.implode(',', $fields)." WHERE id = :id";
                $pdo->prepare($sql)->execute($params);
            }
            json_response(['success'=>true]);
        }
        // DELETE lógico
        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $id = $_GET['id'] ?? null;
            if (!$id) json_response(['error'=>'id requerido'],400);
            $pdo->prepare('UPDATE productos SET activo=0 WHERE id=:id')->execute([':id'=>$id]);
            json_response(['success'=>true]);
        }
        json_response(['error'=>'Método no soportado'],405);
        break;

    case 'job-status':
        require_auth();
        $pid = $_GET['producto_id'] ?? null;
        if (!$pid) {
            json_response(['error'=>'producto_id requerido'],400);
        }
        $stmt = $pdo->prepare('SELECT * FROM meshy_jobs WHERE producto_id=:pid ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([':pid'=>$pid]);
        $job = $stmt->fetch();
        json_response(['job'=>$job]);
        break;

    case 'upload-fotos':
        require_auth();
        // Espera: producto_id y archivos en $_FILES['fotos']
        $producto_id = $_POST['producto_id'] ?? null;
        if (!$producto_id) {
            json_response(['error'=>'producto_id requerido'],400);
        }
        if (!isset($_FILES['fotos'])) {
            json_response(['error'=>'No se enviaron fotos'],400);
        }
        $dir = __DIR__ . '/../uploads/fotos/' . intval($producto_id);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $saved = [];
        $urls = [];
        foreach ($_FILES['fotos']['tmp_name'] as $idx => $tmp) {
            $name = basename($_FILES['fotos']['name'][$idx]);
            $dest = "$dir/$name";
            if (move_uploaded_file($tmp, $dest)) {
                // insertar registro
                $rel = "uploads/fotos/$producto_id/$name";
                $url = BASE_URL . '/' . $rel;
                $pdo->prepare('INSERT INTO fotos_producto (producto_id, ruta, url_publica, orden) VALUES (:pid,:ruta,:url,0)')
                    ->execute([':pid'=>$producto_id,':ruta'=>$rel,':url'=>$url]);
                $saved[] = $url;
                $urls[] = $url;
            }
        }
        // Disparar job a Meshy sólo si hay URLs
        if ($urls) {
            $payload = json_encode(['images' => $urls]);
            $ch = curl_init('https://api.meshy.ai/openapi/v1/image-to-3d');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . MESHY_API_KEY
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            $resp = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if ($resp !== false) {
                $data = json_decode($resp, true);
                if (isset($data['task_id'])) {
                    $stmt = $pdo->prepare('INSERT INTO meshy_jobs (producto_id, meshy_task_id, status) VALUES (:pid, :tid, "pending")');
                    $stmt->execute([':pid'=>$producto_id, ':tid'=>$data['task_id']]);
                }
            }
        }

        json_response(['uploaded'=>$saved]);
        break;


    default:
        http_response_code(404);
        echo json_encode(['error' => 'Ruta no encontrada']);
        break;
}
