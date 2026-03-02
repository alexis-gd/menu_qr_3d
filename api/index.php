<?php
// Router principal de la API. Todas las rutas se determinan por $_GET['route'].
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Respuesta por defecto en JSON
header('Content-Type: application/json; charset=utf-8');

$route = $_GET['route'] ?? '';

switch ($route) {
    case 'menu':
        $slug = $_GET['restaurante'] ?? null;
        if (!$slug) {
            json_response(['error' => 'restaurante requerido'], 400);
        }

        $stmt = $pdo->prepare(
            'SELECT
                r.nombre AS restaurante_nombre,
                r.descripcion AS restaurante_descripcion,
                r.logo_url,
                r.color_primario,
                r.tema,
                c.id AS cat_id,
                c.nombre AS cat_nombre,
                c.icono AS cat_icono,
                c.orden AS cat_orden,
                p.id AS prod_id,
                p.nombre AS prod_nombre,
                p.descripcion AS prod_descripcion,
                p.precio,
                p.foto_principal,
                p.modelo_glb_path,
                p.tiene_ar,
                p.es_destacado,
                p.disponible
             FROM restaurantes r
             JOIN categorias c ON c.restaurante_id = r.id AND c.activo = 1
             JOIN productos p ON p.categoria_id = c.id AND p.activo = 1
             WHERE r.slug = :slug AND r.activo = 1
             ORDER BY c.orden ASC, p.orden ASC, p.nombre ASC'
        );
        $stmt->execute([':slug' => $slug]);
        $rows = $stmt->fetchAll();

        if (!$rows) {
            json_response(['error' => 'Restaurante no encontrado'], 404);
        }

        $restauranteData = [
            'nombre'         => $rows[0]['restaurante_nombre'],
            'descripcion'    => $rows[0]['restaurante_descripcion'],
            'logo_url'       => $rows[0]['logo_url'] ? UPLOADS_URL . $rows[0]['logo_url'] : null,
            'color_primario' => $rows[0]['color_primario'],
            'tema'           => $rows[0]['tema'] ?? 'calido',
        ];

        $categoriasMap = [];
        foreach ($rows as $row) {
            $catId = $row['cat_id'];
            if (!isset($categoriasMap[$catId])) {
                $categoriasMap[$catId] = [
                    'id'       => $catId,
                    'nombre'   => $row['cat_nombre'],
                    'icono'    => $row['cat_icono'],
                    'orden'    => $row['cat_orden'],
                    'productos' => [],
                ];
            }
            $categoriasMap[$catId]['productos'][] = [
                'id'              => $row['prod_id'],
                'nombre'          => $row['prod_nombre'],
                'descripcion'     => $row['prod_descripcion'],
                'precio'          => (float) $row['precio'],
                'foto_principal'  => $row['foto_principal'] ? UPLOADS_URL . $row['foto_principal'] : null,
                'modelo_glb_url'  => $row['modelo_glb_path'] ? UPLOADS_URL . 'modelos/' . $row['modelo_glb_path'] : null,
                'tiene_ar'        => (bool) $row['tiene_ar'],
                'es_destacado'    => (bool) $row['es_destacado'],
                'disponible'      => (bool) $row['disponible'],
            ];
        }

        json_response([
            'restaurante' => $restauranteData,
            'categorias'  => array_values($categoriasMap),
        ]);
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
            $stmt = $pdo->prepare('SELECT id, slug, nombre, descripcion, logo_url, color_primario, tema FROM restaurantes WHERE activo = 1 ORDER BY nombre');
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
            $stmt->execute([
                ':usuario_id' => 1,
                ':slug' => $slug,
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
            ]);

            $id = $pdo->lastInsertId();
            json_response(['id' => (int)$id], 201);
        }

        // PUT: actualizar datos del restaurante (nombre, descripcion, tema, color_primario)
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            require_auth();
            $id = $_GET['id'] ?? null;
            if (!$id) {
                json_response(['error' => 'id requerido'], 400);
            }
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $allowed = ['nombre', 'descripcion', 'tema', 'color_primario'];
            $fields = [];
            $params = [':id' => (int)$id];
            foreach ($allowed as $f) {
                if (isset($body[$f])) {
                    $fields[] = "$f = :$f";
                    $params[":$f"] = $body[$f];
                }
            }
            if ($fields) {
                $pdo->prepare('UPDATE restaurantes SET ' . implode(',', $fields) . ' WHERE id = :id')
                    ->execute($params);
            }
            json_response(['success' => true]);
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
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $id = $_GET['id'] ?? null;
            if (!$id) json_response(['error'=>'id requerido'],400);
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $fields = [];
            $params = [':id' => (int)$id];
            foreach (['nombre', 'icono', 'orden'] as $f) {
                if (isset($body[$f])) {
                    $fields[] = "$f = :$f";
                    $params[":$f"] = $body[$f];
                }
            }
            if ($fields) {
                $pdo->prepare('UPDATE categorias SET ' . implode(',', $fields) . ' WHERE id = :id')
                    ->execute($params);
            }
            json_response(['success'=>true]);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $id = $_GET['id'] ?? null;
            if (!$id) json_response(['error'=>'id requerido'],400);
            $pdo->prepare('UPDATE categorias SET activo=0 WHERE id=:id')->execute([':id'=>$id]);
            json_response(['success'=>true]);
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
            foreach ($products as &$prod) {
                $prod['precio']          = (float) $prod['precio'];
                $prod['tiene_ar']        = (bool)  $prod['tiene_ar'];
                $prod['es_destacado']    = (bool)  $prod['es_destacado'];
                $prod['disponible']      = (bool)  $prod['disponible'];
                $prod['foto_principal']  = $prod['foto_principal'] ? UPLOADS_URL . $prod['foto_principal'] : null;
            }
            unset($prod);
            json_response(['productos' => $products]);
        }
        // POST crear
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $categoria_id = $body['categoria_id'] ?? null;
            $nombre = $body['nombre'] ?? null;
            $descripcion = $body['descripcion'] ?? null;
            $precio = $body['precio'] ?? 0;
            $es_destacado = !empty($body['es_destacado']) ? 1 : 0;
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
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
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

        $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
        $allowed_ext  = ['jpg', 'jpeg', 'png', 'webp'];

        $saved = [];
        $urls = [];
        foreach ($_FILES['fotos']['tmp_name'] as $idx => $tmp) {
            // Validar MIME real (no el que manda el cliente)
            $mime = mime_content_type($tmp);
            if (!in_array($mime, $allowed_mime, true)) {
                continue; // ignorar archivo inválido silenciosamente
            }

            // Construir nombre seguro: solo ID, índice y extensión permitida
            $original_ext = strtolower(pathinfo($_FILES['fotos']['name'][$idx], PATHINFO_EXTENSION));
            if (!in_array($original_ext, $allowed_ext, true)) {
                continue;
            }
            $safe_name = sprintf('foto_%d_%d_%d.%s', intval($producto_id), $idx, time(), $original_ext);

            $dest = "$dir/$safe_name";
            if (move_uploaded_file($tmp, $dest)) {
                $foto_rel = "fotos/$producto_id/$safe_name";  // relativo a /uploads/
                $ruta_rel = "uploads/fotos/$producto_id/$safe_name"; // relativo a webroot
                $url = BASE_URL . '/' . $ruta_rel;
                $pdo->prepare('INSERT INTO fotos_producto (producto_id, ruta, url_publica, orden) VALUES (:pid,:ruta,:url,0)')
                    ->execute([':pid'=>$producto_id,':ruta'=>$ruta_rel,':url'=>$url]);
                // Asignar como foto_principal si el producto aún no tiene una
                if (empty($saved)) {
                    $pdo->prepare('UPDATE productos SET foto_principal = :path WHERE id = :pid AND (foto_principal IS NULL OR foto_principal = "")')
                        ->execute([':path' => $foto_rel, ':pid' => intval($producto_id)]);
                }
                $saved[] = $url;
            }
        }
        json_response(['uploaded' => $saved]);
        break;

    case 'mesas':
        require_auth();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $restaurante_id = $_GET['restaurante_id'] ?? null;
            if (!$restaurante_id) {
                json_response(['error' => 'restaurante_id requerido'], 400);
            }
            $stmt = $pdo->prepare(
                'SELECT m.id, m.numero, m.qr_generado,
                        r.slug AS restaurante_slug, r.nombre AS restaurante_nombre
                 FROM mesas m
                 JOIN restaurantes r ON r.id = m.restaurante_id
                 WHERE m.restaurante_id = :rid AND m.activo = 1
                 ORDER BY CAST(m.numero AS UNSIGNED), m.numero'
            );
            $stmt->execute([':rid' => $restaurante_id]);
            $rows = $stmt->fetchAll();
            $slug   = $rows ? $rows[0]['restaurante_slug']   : '';
            $nombre = $rows ? $rows[0]['restaurante_nombre'] : '';
            $mesas  = array_map(fn($r) => [
                'id'          => $r['id'],
                'numero'      => $r['numero'],
                'qr_generado' => (bool) $r['qr_generado'],
            ], $rows);
            json_response([
                'mesas'              => $mesas,
                'restaurante_slug'   => $slug,
                'restaurante_nombre' => $nombre,
            ]);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $restaurante_id = $body['restaurante_id'] ?? null;
            $numero = trim($body['numero'] ?? '');
            if (!$restaurante_id || $numero === '') {
                json_response(['error' => 'restaurante_id y numero requeridos'], 400);
            }
            try {
                $stmt = $pdo->prepare('INSERT INTO mesas (restaurante_id, numero) VALUES (:rid, :num)');
                $stmt->execute([':rid' => $restaurante_id, ':num' => $numero]);
                json_response(['id' => $pdo->lastInsertId()], 201);
            } catch (PDOException $e) {
                json_response(['error' => 'El número de mesa ya existe en este restaurante'], 409);
            }
        }
        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $id = $_GET['id'] ?? null;
            if (!$id) json_response(['error' => 'id requerido'], 400);
            $pdo->prepare('UPDATE mesas SET activo = 0 WHERE id = :id')->execute([':id' => $id]);
            json_response(['ok' => true]);
        }
        json_response(['error' => 'Método no soportado'], 405);
        break;

    case 'upload-glb':
        require_auth();
        $producto_id = $_POST['producto_id'] ?? null;
        if (!$producto_id) {
            json_response(['error' => 'producto_id requerido'], 400);
        }
        if (!isset($_FILES['modelo']) || $_FILES['modelo']['error'] !== UPLOAD_ERR_OK) {
            json_response(['error' => 'No se recibió el archivo o hubo un error al subirlo'], 400);
        }

        $tmp = $_FILES['modelo']['tmp_name'];

        // Validar que sea un GLB real leyendo los magic bytes ("glTF")
        $handle = fopen($tmp, 'rb');
        $magic  = fread($handle, 4);
        fclose($handle);
        if ($magic !== 'glTF') {
            json_response(['error' => 'El archivo no es un GLB válido'], 400);
        }

        $ext = strtolower(pathinfo($_FILES['modelo']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'glb') {
            json_response(['error' => 'Solo se aceptan archivos .glb'], 400);
        }

        $modelDir = __DIR__ . '/../uploads/modelos';
        if (!is_dir($modelDir)) mkdir($modelDir, 0755, true);

        $filename = sprintf('modelo_%d_%d.glb', intval($producto_id), time());
        $dest     = $modelDir . '/' . $filename;

        if (!move_uploaded_file($tmp, $dest)) {
            json_response(['error' => 'Error al guardar el archivo en el servidor'], 500);
        }

        $pdo->prepare('UPDATE productos SET modelo_glb_path = :path, tiene_ar = 1 WHERE id = :pid')
            ->execute([':path' => $filename, ':pid' => intval($producto_id)]);

        json_response(['success' => true, 'modelo_glb_url' => UPLOADS_URL . 'modelos/' . $filename]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Ruta no encontrada']);
        break;
}
