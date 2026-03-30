<?php
// Router principal de la API. Todas las rutas se determinan por $_GET['route'].
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Respuesta por defecto en JSON
header('Content-Type: application/json; charset=utf-8');

// ── Helpers de imagen ──────────────────────────────────────────────────────────

/**
 * Convierte una imagen subida a WebP y la guarda en $dest_path.
 * Redimensiona si supera $max_w px de ancho (mantiene proporción).
 * Devuelve true en éxito, false si GD no está disponible o falla.
 */
function save_as_webp(string $src, string $dest_path, int $max_w = 1200, int $quality = 85): bool {
    if (!function_exists('imagecreatetruecolor')) return false;
    $mime = mime_content_type($src);
    $img = match($mime) {
        'image/jpeg' => @imagecreatefromjpeg($src),
        'image/png'  => @imagecreatefrompng($src),
        'image/webp' => @imagecreatefromwebp($src),
        default      => false,
    };
    if (!$img) return false;
    $w = imagesx($img); $h = imagesy($img);
    if ($w > $max_w) {
        $nh = (int)round($h * $max_w / $w);
        $resized = imagecreatetruecolor($max_w, $nh);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $max_w, $nh, $w, $h);
        imagedestroy($img);
        $img = $resized;
    }
    $ok = imagewebp($img, $dest_path, $quality);
    imagedestroy($img);
    return $ok;
}

/**
 * Genera un thumbnail cuadrado (crop centrado) en WebP.
 */
function save_thumb_webp(string $src, string $dest_path, int $size = 300, int $quality = 82): bool {
    if (!function_exists('imagecreatetruecolor')) return false;
    $mime = mime_content_type($src);
    $img = match($mime) {
        'image/jpeg' => @imagecreatefromjpeg($src),
        'image/png'  => @imagecreatefrompng($src),
        'image/webp' => @imagecreatefromwebp($src),
        default      => false,
    };
    if (!$img) return false;
    $w = imagesx($img); $h = imagesy($img);
    $side = min($w, $h);
    $sx = (int)(($w - $side) / 2);
    $sy = (int)(($h - $side) / 2);
    $thumb = imagecreatetruecolor($size, $size);
    imagecopyresampled($thumb, $img, 0, 0, $sx, $sy, $size, $size, $side, $side);
    imagedestroy($img);
    $ok = imagewebp($thumb, $dest_path, $quality);
    imagedestroy($thumb);
    return $ok;
}
// ──────────────────────────────────────────────────────────────────────────────

$route = $_GET['route'] ?? '';

switch ($route) {
    case 'menu':
        $slug = $_GET['restaurante'] ?? null;

        $baseQuery = 'SELECT
                r.id AS restaurante_id,
                r.nombre AS restaurante_nombre,
                r.descripcion AS restaurante_descripcion,
                r.logo_url,
                r.color_primario,
                r.tema,
                r.pedidos_activos,
                r.pedidos_envio_activo,
                r.pedidos_envio_costo,
                r.pedidos_envio_gratis_desde,
                r.pedidos_whatsapp,
                r.pedidos_trans_clabe,
                r.pedidos_trans_cuenta,
                r.pedidos_trans_titular,
                r.pedidos_trans_banco,
                r.pedidos_trans_activo,
                r.pedidos_terminal_activo,
                r.tienda_cerrada_manual,
                r.tienda_horarios,
                r.stock_minimo_aviso,
                r.codigos_promo_habilitado,
                r.pedidos_programar_activo,
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
                p.disponible,
                p.stock,
                p.tiene_personalizacion,
                p.aviso_complemento,
                p.aviso_categoria_id
             FROM restaurantes r
             LEFT JOIN categorias c ON c.restaurante_id = r.id AND c.activo = 1
             LEFT JOIN productos p ON p.categoria_id = c.id AND p.activo = 1';

        if ($slug) {
            $stmt = $pdo->prepare($baseQuery . ' WHERE r.slug = :slug AND r.activo = 1 ORDER BY c.orden ASC, p.orden ASC, p.nombre ASC');
            $stmt->execute([':slug' => $slug]);
        } else {
            // Single-tenant: devuelve el primer restaurante activo
            $stmt = $pdo->prepare($baseQuery . ' WHERE r.activo = 1 ORDER BY r.id ASC, c.orden ASC, p.orden ASC, p.nombre ASC');
            $stmt->execute();
        }
        $rows = $stmt->fetchAll();

        if (!$rows) {
            json_response(['error' => 'Restaurante no encontrado'], 404);
        }

        // Con LEFT JOIN puede haber fila con cat_id NULL si no hay categorías aún
        $tieneContenido = !empty($rows[0]['cat_id']);

        // Calcular si la tienda está abierta según horario + toggle manual
        function isTiendaAbierta(array $row): bool {
            if ($row['tienda_cerrada_manual']) return false;
            $h = $row['tienda_horarios'] ? json_decode($row['tienda_horarios'], true) : null;
            if (!$h) return true; // sin horarios configurados = siempre abierta
            // Validar que el JSON tiene estructura correcta (llaves de días, no índices numéricos)
            $diasValidos = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
            $tieneEstructuraValida = !empty(array_intersect(array_keys($h), $diasValidos));
            if (!$tieneEstructuraValida) return true; // horarios corruptos = ignorar = abierta
            $dias = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
            $dia  = $dias[(int) date('w')];
            $hora = date('H:i');
            $d = $h[$dia] ?? null;
            if (!$d || !$d['activo']) return false;
            return $hora >= $d['apertura'] && $hora <= $d['cierre'];
        }

        $restauranteData = [
            'id'                    => (int) $rows[0]['restaurante_id'],
            'nombre'                => $rows[0]['restaurante_nombre'],
            'descripcion'           => $rows[0]['restaurante_descripcion'],
            'logo_url'              => $rows[0]['logo_url'] ? UPLOADS_URL . $rows[0]['logo_url'] : null,
            'color_primario'        => $rows[0]['color_primario'],
            'tema'                  => $rows[0]['tema'] ?? 'calido',
            'pedidos_activos'       => (bool) $rows[0]['pedidos_activos'],
            'pedidos_envio_activo'        => (bool) $rows[0]['pedidos_envio_activo'],
            'pedidos_envio_costo'         => (float) $rows[0]['pedidos_envio_costo'],
            'pedidos_envio_gratis_desde'  => $rows[0]['pedidos_envio_gratis_desde'] !== null ? (float) $rows[0]['pedidos_envio_gratis_desde'] : null,
            'pedidos_whatsapp'            => $rows[0]['pedidos_whatsapp'],
            'pedidos_trans_clabe'   => $rows[0]['pedidos_trans_clabe'],
            'pedidos_trans_cuenta'  => $rows[0]['pedidos_trans_cuenta'],
            'pedidos_trans_titular' => $rows[0]['pedidos_trans_titular'],
            'pedidos_trans_banco'   => $rows[0]['pedidos_trans_banco'],
            'pedidos_trans_activo'     => (bool) $rows[0]['pedidos_trans_activo'],
            'pedidos_terminal_activo'  => (bool) $rows[0]['pedidos_terminal_activo'],
            'tienda_abierta'           => isTiendaAbierta($rows[0]),
            'tienda_cerrada_manual'    => (bool) $rows[0]['tienda_cerrada_manual'],
            'tienda_horarios'          => $rows[0]['tienda_horarios'] ? json_decode($rows[0]['tienda_horarios'], true) : null,
            'stock_minimo_aviso'         => (int) $rows[0]['stock_minimo_aviso'],
            'codigos_promo_habilitado'   => (bool) $rows[0]['codigos_promo_habilitado'],
            'pedidos_programar_activo'   => (bool) $rows[0]['pedidos_programar_activo'],
        ];

        $categoriasMap = [];
        foreach ($rows as $row) {
            if (!$row['cat_id']) continue; // restaurante sin categorías aún
            if (!$row['prod_id']) continue; // categoría sin productos aún
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
                'id'                    => $row['prod_id'],
                'nombre'                => $row['prod_nombre'],
                'descripcion'           => $row['prod_descripcion'],
                'precio'                => (float) $row['precio'],
                'foto_principal'        => $row['foto_principal'] ? UPLOADS_URL . $row['foto_principal'] : null,
                'modelo_glb_url'        => $row['modelo_glb_path'] ? UPLOADS_URL . 'modelos/' . $row['modelo_glb_path'] : null,
                'tiene_ar'              => (bool) $row['tiene_ar'],
                'es_destacado'          => (bool) $row['es_destacado'],
                'disponible'            => (bool) $row['disponible'],
                'stock'                 => $row['stock'] !== null ? (int) $row['stock'] : null,
                'tiene_personalizacion' => (bool) $row['tiene_personalizacion'],
                'aviso_complemento'     => $row['aviso_complemento'],
                'aviso_categoria_id'    => $row['aviso_categoria_id'] ? (int) $row['aviso_categoria_id'] : null,
                'grupos'                => [],
            ];
        }

        // Cargar grupos y opciones para productos con personalización (evita N+1)
        $prodIds = [];
        foreach ($categoriasMap as $cat) {
            foreach ($cat['productos'] as $prod) {
                if ($prod['tiene_personalizacion']) {
                    $prodIds[] = $prod['id'];
                }
            }
        }
        if (!empty($prodIds)) {
            $placeholders = implode(',', array_fill(0, count($prodIds), '?'));
            $stmtGrupos = $pdo->prepare(
                "SELECT pg.id, pg.producto_id, pg.nombre, pg.tipo, pg.obligatorio,
                        pg.min_selecciones, pg.max_selecciones, pg.max_dinamico_grupo_id, pg.orden,
                        po.id AS op_id, po.nombre AS op_nombre, po.precio_extra,
                        po.max_override, po.orden AS op_orden
                 FROM producto_grupos pg
                 LEFT JOIN producto_opciones po ON po.grupo_id = pg.id AND po.activo = 1
                 WHERE pg.producto_id IN ($placeholders) AND pg.activo = 1
                 ORDER BY pg.producto_id, pg.orden, po.orden"
            );
            $stmtGrupos->execute($prodIds);
            $gruposRows = $stmtGrupos->fetchAll();

            $gruposMap = [];
            foreach ($gruposRows as $gr) {
                $pid = $gr['producto_id'];
                $gid = $gr['id'];
                if (!isset($gruposMap[$pid][$gid])) {
                    $gruposMap[$pid][$gid] = [
                        'id'                    => (int) $gr['id'],
                        'nombre'                => $gr['nombre'],
                        'tipo'                  => $gr['tipo'],
                        'obligatorio'           => (bool) $gr['obligatorio'],
                        'min_selecciones'       => (int) $gr['min_selecciones'],
                        'max_selecciones'       => (int) $gr['max_selecciones'],
                        'max_dinamico_grupo_id' => $gr['max_dinamico_grupo_id'] ? (int) $gr['max_dinamico_grupo_id'] : null,
                        'orden'                 => (int) $gr['orden'],
                        'opciones'              => [],
                    ];
                }
                if ($gr['op_id']) {
                    $gruposMap[$pid][$gid]['opciones'][] = [
                        'id'           => (int) $gr['op_id'],
                        'nombre'       => $gr['op_nombre'],
                        'precio_extra' => (float) $gr['precio_extra'],
                        'max_override' => $gr['max_override'] !== null ? (int) $gr['max_override'] : null,
                        'orden'        => (int) $gr['op_orden'],
                    ];
                }
            }

            foreach ($categoriasMap as &$cat) {
                foreach ($cat['productos'] as &$prod) {
                    if ($prod['tiene_personalizacion'] && isset($gruposMap[$prod['id']])) {
                        $prod['grupos'] = array_values($gruposMap[$prod['id']]);
                    }
                }
            }
            unset($cat, $prod);
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

        // Emitir cookie HttpOnly — el token nunca viaja en el body
        set_auth_cookie(ADMIN_TOKEN);
        json_response(['ok' => true]);
        break;

    case 'logout':
        clear_auth_cookie();
        json_response(['ok' => true]);
        break;

    case 'auth-check':
        require_auth();
        json_response(['ok' => true]);
        break;

    case 'restaurantes':
        // GET: lista restaurantes (auth requerida)
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require_auth();
            $stmt = $pdo->prepare('SELECT id, slug, nombre, descripcion, logo_url, color_primario, tema, qr_frase, qr_frase_activa, qr_wifi_nombre, qr_wifi_clave, qr_wifi_activo, pedidos_activos, pedidos_envio_activo, pedidos_envio_costo, pedidos_envio_gratis_desde, pedidos_whatsapp, pedidos_trans_clabe, pedidos_trans_cuenta, pedidos_trans_titular, pedidos_trans_banco, pedidos_trans_activo, pedidos_terminal_activo, compartir_mensaje, tienda_cerrada_manual, tienda_horarios, stock_minimo_aviso, codigos_promo_habilitado, pedidos_programar_activo FROM restaurantes WHERE activo = 1 ORDER BY nombre');
            $stmt->execute();
            $rows = $stmt->fetchAll();
            foreach ($rows as &$r) {
                $r['logo_url'] = $r['logo_url'] ? UPLOADS_URL . $r['logo_url'] : null;
                $r['tienda_horarios'] = $r['tienda_horarios'] ? json_decode($r['tienda_horarios'], true) : null;
            }
            unset($r);
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
            $allowed = ['nombre', 'descripcion', 'tema', 'color_primario', 'qr_frase', 'qr_frase_activa', 'qr_wifi_nombre', 'qr_wifi_clave', 'qr_wifi_activo', 'pedidos_activos', 'pedidos_envio_activo', 'pedidos_envio_costo', 'pedidos_envio_gratis_desde', 'pedidos_whatsapp', 'pedidos_trans_clabe', 'pedidos_trans_cuenta', 'pedidos_trans_titular', 'pedidos_trans_banco', 'pedidos_trans_activo', 'pedidos_terminal_activo', 'compartir_mensaje', 'tienda_cerrada_manual', 'tienda_horarios', 'stock_minimo_aviso', 'codigos_promo_habilitado', 'pedidos_programar_activo'];
            // Serializar tienda_horarios si viene como array
            if (isset($body['tienda_horarios']) && is_array($body['tienda_horarios'])) {
                $body['tienda_horarios'] = json_encode($body['tienda_horarios'], JSON_UNESCAPED_UNICODE);
            }
            $fields = [];
            $params = [':id' => (int)$id];
            foreach ($allowed as $f) {
                if (isset($body[$f])) {
                    $fields[] = "$f = :$f";
                    $params[":$f"] = $body[$f];
                }
            }
            if ($fields) {
                try {
                    $pdo->prepare('UPDATE restaurantes SET ' . implode(',', $fields) . ' WHERE id = :id')
                        ->execute($params);
                } catch (\PDOException $e) {
                    json_response(['error' => 'Error al guardar: ' . $e->getMessage()], 500);
                }
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
                $prod['stock']           = isset($prod['stock']) ? (int) $prod['stock'] : null;
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
            foreach (['categoria_id','nombre','descripcion','precio','es_destacado','disponible','stock','orden'] as $f) {
                // array_key_exists (no isset) para aceptar null explícito (ej: stock = null = quitar control)
                if (array_key_exists($f, $body)) {
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

        // Borrar fotos anteriores del disco y de la BD
        $stmtViejas = $pdo->prepare('SELECT ruta FROM fotos_producto WHERE producto_id = :pid');
        $stmtViejas->execute([':pid' => intval($producto_id)]);
        foreach ($stmtViejas->fetchAll() as $vieja) {
            $rutaFisica = __DIR__ . '/../' . $vieja['ruta'];
            if (file_exists($rutaFisica)) @unlink($rutaFisica);
        }
        $pdo->prepare('DELETE FROM fotos_producto WHERE producto_id = :pid')
            ->execute([':pid' => intval($producto_id)]);

        $saved = [];
        foreach ($_FILES['fotos']['tmp_name'] as $idx => $tmp) {
            // Validar MIME real (no el que manda el cliente)
            $mime = mime_content_type($tmp);
            if (!in_array($mime, $allowed_mime, true)) {
                continue;
            }

            // Construir nombre seguro: solo ID, índice y extensión permitida
            $original_ext = strtolower(pathinfo($_FILES['fotos']['name'][$idx], PATHINFO_EXTENSION));
            if (!in_array($original_ext, $allowed_ext, true)) {
                continue;
            }
            $base_name  = sprintf('foto_%d_%d_%d', intval($producto_id), $idx, time());
            $safe_name  = $base_name . '.webp';
            $thumb_name = 'thumb_' . $safe_name;

            $dest = "$dir/$safe_name";
            if (move_uploaded_file($tmp, $dest)) {
                // Convertir a WebP y generar thumbnail (si GD disponible; si no, se guarda el original)
                if (save_as_webp($dest, $dest)) {
                    // ya sobrescrito como WebP
                } else {
                    // GD no disponible: guardar con extensión original
                    $safe_name  = $base_name . '.' . $original_ext;
                    $thumb_name = 'thumb_' . $safe_name;
                    rename($dest, "$dir/$safe_name");
                    $dest = "$dir/$safe_name";
                }
                save_thumb_webp($dest, "$dir/$thumb_name", 220);

                $foto_rel = "fotos/$producto_id/$safe_name";
                $ruta_rel = "uploads/fotos/$producto_id/$safe_name";
                $url = UPLOADS_URL . "fotos/$producto_id/$safe_name";
                $pdo->prepare('INSERT INTO fotos_producto (producto_id, ruta, url_publica, orden) VALUES (:pid,:ruta,:url,0)')
                    ->execute([':pid'=>$producto_id,':ruta'=>$ruta_rel,':url'=>$url]);
                // Primera foto subida → siempre se convierte en foto_principal
                if (empty($saved)) {
                    $pdo->prepare('UPDATE productos SET foto_principal = :path WHERE id = :pid')
                        ->execute([':path' => $foto_rel, ':pid' => intval($producto_id)]);
                }
                $saved[] = $url;
            }
        }
        json_response(['uploaded' => $saved]);
        break;

    case 'upload-logo':
        require_auth();
        $restaurante_id = $_POST['restaurante_id'] ?? null;
        if (!$restaurante_id) {
            json_response(['error' => 'restaurante_id requerido'], 400);
        }
        if (empty($_FILES['logo']['tmp_name'])) {
            json_response(['error' => 'No se envió el logo'], 400);
        }
        $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
        $allowed_ext  = ['jpg', 'jpeg', 'png', 'webp'];
        $tmp  = $_FILES['logo']['tmp_name'];
        $mime = mime_content_type($tmp);
        if (!in_array($mime, $allowed_mime, true)) {
            json_response(['error' => 'Formato no permitido. Usa JPG, PNG o WebP.'], 400);
        }
        $original_ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (!in_array($original_ext, $allowed_ext, true)) {
            json_response(['error' => 'Extensión no permitida.'], 400);
        }
        if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
            json_response(['error' => 'El logo supera los 2 MB.'], 400);
        }
        $dir = __DIR__ . '/../uploads/logos/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        // Borrar logo anterior del disco
        $stmtLogoViejo = $pdo->prepare('SELECT logo_url FROM restaurantes WHERE id = :id');
        $stmtLogoViejo->execute([':id' => intval($restaurante_id)]);
        $logoViejo = $stmtLogoViejo->fetchColumn();
        if ($logoViejo) {
            $rutaFisica = __DIR__ . '/../uploads/' . $logoViejo;
            if (file_exists($rutaFisica)) @unlink($rutaFisica);
        }

        $base_logo = sprintf('logo_%d_%d', intval($restaurante_id), time());
        $filename  = $base_logo . '.webp';
        $dest = $dir . $filename;
        $dest_orig = $dir . $base_logo . '.' . $original_ext;
        if (!move_uploaded_file($tmp, $dest_orig)) {
            json_response(['error' => 'Error al guardar el archivo.'], 500);
        }
        if (!save_as_webp($dest_orig, $dest, 800)) {
            // GD no disponible: usar original
            $filename = $base_logo . '.' . $original_ext;
            $dest = $dest_orig;
        } else {
            @unlink($dest_orig); // eliminar original si WebP fue generado
        }
        $logo_rel = 'logos/' . $filename;
        $pdo->prepare('UPDATE restaurantes SET logo_url = :logo WHERE id = :id')
            ->execute([':logo' => $logo_rel, ':id' => intval($restaurante_id)]);
        json_response(['success' => true, 'logo_url' => UPLOADS_URL . $logo_rel]);
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
                        r.slug AS restaurante_slug, r.nombre AS restaurante_nombre,
                        r.color_primario, r.tema
                 FROM mesas m
                 JOIN restaurantes r ON r.id = m.restaurante_id
                 WHERE m.restaurante_id = :rid AND m.activo = 1
                 ORDER BY CAST(m.numero AS UNSIGNED), m.numero'
            );
            $stmt->execute([':rid' => $restaurante_id]);
            $rows = $stmt->fetchAll();
            $slug   = $rows ? $rows[0]['restaurante_slug']   : '';
            $nombre = $rows ? $rows[0]['restaurante_nombre'] : '';
            $color  = $rows ? $rows[0]['color_primario']     : '#FF6B35';
            $tema   = $rows ? ($rows[0]['tema'] ?? 'calido') : 'calido';
            $mesas  = array_map(fn($r) => [
                'id'          => $r['id'],
                'numero'      => $r['numero'],
                'qr_generado' => (bool) $r['qr_generado'],
            ], $rows);
            json_response([
                'mesas'              => $mesas,
                'restaurante_slug'   => $slug,
                'restaurante_nombre' => $nombre,
                'restaurante_color'  => $color,
                'restaurante_tema'   => $tema,
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

    case 'pedidos':
        // GET: lista pedidos del restaurante (auth)
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require_auth();
            $restaurante_id = $_GET['restaurante_id'] ?? null;
            if (!$restaurante_id) json_response(['error' => 'restaurante_id requerido'], 400);

            $stmt = $pdo->prepare(
                'SELECT p.id, p.numero_pedido, p.nombre_cliente, p.telefono,
                        p.tipo_entrega, p.direccion, p.referencia, p.metodo_pago, p.denominacion,
                        p.mesa, p.subtotal, p.costo_envio, p.total,
                        p.descuento_recompensa, p.descuento_promo, p.codigo_promo,
                        p.ajuste_manual, p.ajuste_nota,
                        (p.total + p.ajuste_manual) AS total_final,
                        p.fecha_programada, p.hora_programada,
                        p.status, p.created_at
                 FROM pedidos p
                 WHERE p.restaurante_id = :rid
                 ORDER BY p.created_at DESC LIMIT 100'
            );
            $stmt->execute([':rid' => (int)$restaurante_id]);
            $pedidos = $stmt->fetchAll();

            // Cargar items de cada pedido
            foreach ($pedidos as &$ped) {
                $stmtItems = $pdo->prepare(
                    'SELECT id, producto_id, nombre_producto, precio_unitario, cantidad, observacion, subtotal
                     FROM pedido_items WHERE pedido_id = :pid'
                );
                $stmtItems->execute([':pid' => $ped['id']]);
                $items = $stmtItems->fetchAll();
                // Cargar opciones de personalización por item
                $stmtOpc = $pdo->prepare(
                    'SELECT grupo_nombre, opcion_nombre, precio_extra
                     FROM pedido_item_opciones WHERE pedido_item_id = :iid'
                );
                foreach ($items as &$item) {
                    $stmtOpc->execute([':iid' => $item['id']]);
                    $item['opciones'] = $stmtOpc->fetchAll();
                }
                unset($item);
                $ped['items'] = $items;
                $ped['subtotal']      = (float) $ped['subtotal'];
                $ped['costo_envio']  = (float) $ped['costo_envio'];
                $ped['total']        = (float) $ped['total'];
                $ped['ajuste_manual']= (float) $ped['ajuste_manual'];
                $ped['total_final']  = (float) $ped['total_final'];
            }
            unset($ped);
            json_response(['pedidos' => $pedidos]);
        }

        // POST: crear pedido (público, sin auth)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?: [];

            $restaurante_id = $body['restaurante_id'] ?? null;
            $nombre_cliente = trim($body['nombre_cliente'] ?? '');
            $tipo_entrega   = $body['tipo_entrega'] ?? 'recoger';
            $metodo_pago    = $body['metodo_pago'] ?? 'efectivo';
            $items          = $body['items'] ?? [];

            if (!$restaurante_id || !$nombre_cliente || empty($items)) {
                json_response(['error' => 'restaurante_id, nombre_cliente e items son requeridos'], 400);
            }
            if (!in_array($tipo_entrega, ['recoger', 'envio'], true)) {
                json_response(['error' => 'tipo_entrega inválido'], 400);
            }
            if (!in_array($metodo_pago, ['efectivo', 'transferencia', 'terminal'], true)) {
                json_response(['error' => 'metodo_pago inválido'], 400);
            }

            // Generar numero_pedido: YYYYMMDD-KBR4 (fecha + sufijo aleatorio no secuencial)
            // Formato: consonantes sin I/O/U para evitar ambigüedad al leerlo en voz alta
            $numero_pedido = null;
            for ($__i = 0; $__i < 5; $__i++) {
                $__letras = 'BCDFGHJKLMNPQRSTVWXYZ';
                $__l = strlen($__letras);
                $__sufijo = $__letras[random_int(0, $__l - 1)]
                          . $__letras[random_int(0, $__l - 1)]
                          . $__letras[random_int(0, $__l - 1)]
                          . random_int(0, 9);
                $__candidato = date('Ymd') . '-' . $__sufijo;
                $__stmtChk = $pdo->prepare('SELECT 1 FROM pedidos WHERE restaurante_id=:r AND numero_pedido=:n');
                $__stmtChk->execute([':r' => (int)$restaurante_id, ':n' => $__candidato]);
                if (!$__stmtChk->fetchColumn()) { $numero_pedido = $__candidato; break; }
            }
            if (!$numero_pedido) json_response(['error' => 'Error generando folio, intente de nuevo'], 500);

            $subtotal   = (float) ($body['subtotal'] ?? 0);
            $costo_envio = (float) ($body['costo_envio'] ?? 0);
            $total      = (float) ($body['total'] ?? ($subtotal + $costo_envio));

            // Validar código promo antes del INSERT (si viene uno)
            $codigo_promo_input = strtoupper(trim($body['codigo_promo'] ?? ''));
            $promo_valida = null;
            if ($codigo_promo_input) {
                $stmtPromoChk = $pdo->prepare(
                    'SELECT id, tipo, valor, usos, usos_maximo, telefono_restringido FROM codigos_promo WHERE restaurante_id=:rid AND codigo=:c AND activo=1'
                );
                $stmtPromoChk->execute([':rid' => (int)$restaurante_id, ':c' => $codigo_promo_input]);
                $promo_valida = $stmtPromoChk->fetch(PDO::FETCH_ASSOC) ?: null;
                if ($promo_valida && $promo_valida['usos_maximo'] !== null && (int)$promo_valida['usos'] >= (int)$promo_valida['usos_maximo']) {
                    $promo_valida = null; // tope alcanzado, no aplicar descuento
                }
                // Validar restricción por teléfono
                if ($promo_valida && !empty($promo_valida['telefono_restringido'])) {
                    $tel_cliente  = preg_replace('/\D/', '', $body['telefono'] ?? '');
                    $tel_restrict = preg_replace('/\D/', '', $promo_valida['telefono_restringido']);
                    if ($tel_cliente !== $tel_restrict) {
                        json_response(['error' => 'Este cupón no es válido para este número', 'tipo' => 'cupon_restringido'], 400);
                    }
                }
                // Cupón de envío gratis: el backend sobreescribe costo_envio para evitar manipulación
                if ($promo_valida && $promo_valida['tipo'] === 'envio_gratis') {
                    $costo_envio = 0.0;
                }
            }

            $desc_rec  = max(0, (float)($body['descuento_recompensa'] ?? 0));
            $desc_promo = max(0, (float)($body['descuento_promo'] ?? 0));

            $fecha_programada = !empty($body['fecha_programada']) ? $body['fecha_programada'] : null;
            $hora_programada  = !empty($body['hora_programada'])  ? $body['hora_programada']  : null;

            $stmt = $pdo->prepare(
                'INSERT INTO pedidos (restaurante_id, numero_pedido, nombre_cliente, telefono, tipo_entrega, direccion, referencia, metodo_pago, denominacion, mesa, subtotal, costo_envio, total, descuento_recompensa, descuento_promo, codigo_promo, fecha_programada, hora_programada)
                 VALUES (:rid, :np, :nc, :tel, :te, :dir, :ref, :mp, :den, :mesa, :sub, :env, :tot, :drec, :dpro, :cp, :fp, :hp)'
            );
            $stmt->execute([
                ':rid'  => (int)$restaurante_id,
                ':np'   => $numero_pedido,
                ':nc'   => $nombre_cliente,
                ':tel'  => $body['telefono'] ?? null,
                ':te'   => $tipo_entrega,
                ':dir'  => $body['direccion'] ?? null,
                ':ref'  => $body['referencia'] ?? null,
                ':mp'   => $metodo_pago,
                ':den'  => isset($body['denominacion']) ? (float)$body['denominacion'] : null,
                ':mesa' => $body['mesa'] ?? null,
                ':sub'  => $subtotal,
                ':env'  => $costo_envio,
                ':tot'  => $total,
                ':drec' => $desc_rec,
                ':dpro' => $desc_promo,
                ':cp'   => $promo_valida ? $codigo_promo_input : null,
                ':fp'   => $fecha_programada,
                ':hp'   => $hora_programada,
            ]);
            $pedido_id = $pdo->lastInsertId();

            // Validar disponibilidad y stock de cada item antes de insertar
            $stmtStock = $pdo->prepare('SELECT stock, disponible FROM productos WHERE id=:id AND activo=1');
            foreach ($items as $item) {
                $prod_id = $item['producto_id'] ?? null;
                if (!$prod_id) continue;
                $cant        = max(1, (int)($item['cantidad'] ?? 1));
                $nombre_prod = $item['nombre'] ?? 'Producto';
                $stmtStock->execute([':id' => (int)$prod_id]);
                $row = $stmtStock->fetch(PDO::FETCH_ASSOC);
                if (!$row || (int)$row['disponible'] === 0) {
                    json_response(['error' => "$nombre_prod ya no está disponible", 'tipo' => 'stock_agotado', 'producto_id' => (int)$prod_id], 409);
                }
                if ($row['stock'] !== null && (int)$row['stock'] < $cant) {
                    json_response(['error' => "$nombre_prod se agotó", 'tipo' => 'stock_agotado', 'producto_id' => (int)$prod_id], 409);
                }
            }

            // Insertar items
            $stmtItem = $pdo->prepare(
                'INSERT INTO pedido_items (pedido_id, producto_id, nombre_producto, precio_unitario, cantidad, observacion, subtotal)
                 VALUES (:pid, :prod_id, :nombre, :precio, :cant, :obs, :sub)'
            );
            $stmtOpc = $pdo->prepare(
                'INSERT INTO pedido_item_opciones (pedido_item_id, grupo_nombre, opcion_nombre, precio_extra)
                 VALUES (:iid, :gn, :on, :pe)'
            );
            $stmtDescStock = $pdo->prepare(
                'UPDATE productos SET stock = GREATEST(0, stock - :cant) WHERE id=:id AND stock IS NOT NULL'
            );
            foreach ($items as $item) {
                $cant = max(1, (int)($item['cantidad'] ?? 1));
                $precio = (float)($item['precio'] ?? 0);
                $prod_id = $item['producto_id'] ?? null;
                $stmtItem->execute([
                    ':pid'    => $pedido_id,
                    ':prod_id'=> $prod_id,
                    ':nombre' => $item['nombre'] ?? '',
                    ':precio' => $precio,
                    ':cant'   => $cant,
                    ':obs'    => $item['observacion'] ?? null,
                    ':sub'    => $precio * $cant,
                ]);
                // Guardar opciones de personalización si las tiene
                $opciones = $item['opciones'] ?? [];
                if (!empty($opciones)) {
                    $item_id = (int)$pdo->lastInsertId();
                    foreach ($opciones as $op) {
                        $stmtOpc->execute([
                            ':iid' => $item_id,
                            ':gn'  => $op['grupo_nombre'] ?? '',
                            ':on'  => $op['opcion_nombre'] ?? '',
                            ':pe'  => (float)($op['precio_extra'] ?? 0),
                        ]);
                    }
                }
                // Descontar stock si el producto tiene control de stock
                if ($prod_id) {
                    $stmtDescStock->execute([':cant' => $cant, ':id' => (int)$prod_id]);
                }
            }

            // ── Recompensas y Referidos ──────────────────────────────────────
            $tel_pedido = preg_replace('/\D/', '', $body['telefono'] ?? '');
            $usaContadaEnRecompensas = db_column_exists($pdo, 'pedidos', 'contada_en_recompensas');
            if (strlen($tel_pedido) >= 8) {
                $stmtRC = $pdo->prepare('SELECT * FROM recompensas_config WHERE restaurante_id = :rid AND activo = 1');
                $stmtRC->execute([':rid' => (int)$restaurante_id]);
                $cfg_rec = $stmtRC->fetch(PDO::FETCH_ASSOC);

                if ($cfg_rec) {
                    if (!empty($body['aplicar_recompensa'])) {
                        // Canje de recompensa: solo actualizar ultima_compra, NO incrementar total_compras
                        // (el pedido con canje no cuenta como compra acumulable — el cliente arranca en 0)
                        $pdo->prepare(
                            'INSERT INTO clientes (restaurante_id, telefono, total_compras, ultima_compra)
                             VALUES (:rid, :tel, 0, NOW())
                             ON DUPLICATE KEY UPDATE ultima_compra = NOW()'
                        )->execute([':rid' => (int)$restaurante_id, ':tel' => $tel_pedido]);
                        // contada_en_recompensas queda en 0 (no hay total_compras que revertir al cancelar)
                    } else {
                        // Compra normal: incrementar total_compras
                        $pdo->prepare(
                            'INSERT INTO clientes (restaurante_id, telefono, total_compras, ultima_compra)
                             VALUES (:rid, :tel, 1, NOW())
                             ON DUPLICATE KEY UPDATE total_compras = total_compras + 1, ultima_compra = NOW()'
                        )->execute([':rid' => (int)$restaurante_id, ':tel' => $tel_pedido]);
                        // Marcar el pedido como contado para poder revertir si se cancela
                        if ($usaContadaEnRecompensas) {
                            $pdo->prepare('UPDATE pedidos SET contada_en_recompensas = 1 WHERE id = :id')
                                ->execute([':id' => (int)$pedido_id]);
                        }
                    }

                    $stmtCli = $pdo->prepare('SELECT * FROM clientes WHERE restaurante_id = :rid AND telefono = :tel');
                    $stmtCli->execute([':rid' => (int)$restaurante_id, ':tel' => $tel_pedido]);
                    $cli = $stmtCli->fetch(PDO::FETCH_ASSOC);

                    // Marcar recompensa usada si el cliente la aplicó en este pedido
                    if (!empty($body['aplicar_recompensa']) && $cli) {
                        $necesarias = (int)$cfg_rec['compras_necesarias'];
                        $ciclos     = $necesarias > 0 ? (int)floor((int)$cli['total_compras'] / $necesarias) : 0;
                        if ($ciclos > (int)$cli['recompensas_ganadas']) {
                            $pdo->prepare('UPDATE clientes SET recompensas_ganadas = recompensas_ganadas + 1 WHERE id = :id')
                                ->execute([':id' => $cli['id']]);
                        }
                    }

                }
            }

            // ── Código promocional: incrementar usos ─────────────────────────
            if ($promo_valida) {
                $pdo->prepare('UPDATE codigos_promo SET usos = usos + 1 WHERE id = :id')
                    ->execute([':id' => (int)$promo_valida['id']]);
            }
            // ────────────────────────────────────────────────────────────────

            // Notificar al restaurante via push sin romper la creación del pedido
            // si el helper aún no está disponible en el servidor.
            if (function_exists('notify_new_order')) {
                notify_new_order($pdo, (int)$restaurante_id, $numero_pedido);
            }

            json_response(['id' => (int)$pedido_id, 'numero_pedido' => $numero_pedido], 201);
        }

        // PUT: actualizar status O ajuste_manual del pedido (auth)
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            require_auth();
            $id = $_GET['id'] ?? null;
            if (!$id) json_response(['error' => 'id requerido'], 400);
            $body = json_decode(file_get_contents('php://input'), true) ?: [];

            // Rama: ajuste manual de monto (solo admin, no toca recompensas)
            if (array_key_exists('ajuste_manual', $body)) {
                $rid_ajuste = intval($body['restaurante_id'] ?? 0);
                if (!$rid_ajuste) json_response(['error' => 'restaurante_id requerido'], 400);
                $pdo->prepare(
                    'UPDATE pedidos SET ajuste_manual=:am, ajuste_nota=:an WHERE id=:id AND restaurante_id=:rid'
                )->execute([
                    ':am'  => (float)$body['ajuste_manual'],
                    ':an'  => isset($body['ajuste_nota']) ? substr(trim($body['ajuste_nota']), 0, 100) : null,
                    ':id'  => (int)$id,
                    ':rid' => $rid_ajuste,
                ]);
                json_response(['success' => true]);
            }

            // Rama: cambio de status
            $status = $body['status'] ?? null;
            $validStatuses = ['nuevo','visto','en_preparacion','listo','entregado','cancelado'];
            if (!$status || !in_array($status, $validStatuses, true)) {
                json_response(['error' => 'status inválido'], 400);
            }
            $pdo->prepare('UPDATE pedidos SET status = :s WHERE id = :id')
                ->execute([':s' => $status, ':id' => (int)$id]);

            // Al cancelar: revertir recompensas, cupón y stock
            if ($status === 'cancelado') {
                $usaContadaEnRecompensas = db_column_exists($pdo, 'pedidos', 'contada_en_recompensas');
                // Revertir recompensas
                $sqlPedidoCancelado = $usaContadaEnRecompensas
                    ? 'SELECT telefono, restaurante_id, contada_en_recompensas, descuento_recompensa, codigo_promo FROM pedidos WHERE id = :id'
                    : 'SELECT telefono, restaurante_id, descuento_recompensa, codigo_promo FROM pedidos WHERE id = :id';
                $stmtP = $pdo->prepare($sqlPedidoCancelado);
                $stmtP->execute([':id' => (int)$id]);
                $ped = $stmtP->fetch(PDO::FETCH_ASSOC);
                $tel = preg_replace('/\D/', '', $ped['telefono'] ?? '');
                if (strlen($tel) >= 8) {
                    // Revertir total_compras solo si fue una compra normal contada
                    if ($usaContadaEnRecompensas && $ped && (int)$ped['contada_en_recompensas'] === 1) {
                        $pdo->prepare(
                            'UPDATE clientes SET total_compras = GREATEST(0, total_compras - 1)
                             WHERE restaurante_id = :rid AND telefono = :tel'
                        )->execute([':rid' => (int)$ped['restaurante_id'], ':tel' => $tel]);
                        $pdo->prepare('UPDATE pedidos SET contada_en_recompensas = 0 WHERE id = :id')
                            ->execute([':id' => (int)$id]);
                    }
                    // Revertir recompensa canjeada (independiente — canje no incrementa total_compras)
                    if ($ped && (float)$ped['descuento_recompensa'] > 0) {
                        $pdo->prepare(
                            'UPDATE clientes SET recompensas_ganadas = GREATEST(0, recompensas_ganadas - 1)
                             WHERE restaurante_id = :rid AND telefono = :tel'
                        )->execute([':rid' => (int)$ped['restaurante_id'], ':tel' => $tel]);
                    }
                }

                // Revertir uso del código promo si se usó uno
                if (!empty($ped['codigo_promo'])) {
                    $pdo->prepare(
                        'UPDATE codigos_promo SET usos = GREATEST(0, usos - 1)
                         WHERE restaurante_id = :rid AND codigo = :c'
                    )->execute([':rid' => (int)$ped['restaurante_id'], ':c' => $ped['codigo_promo']]);
                }

                // Restaurar stock de los items del pedido cancelado
                $stmtItemsCan = $pdo->prepare('SELECT producto_id, cantidad FROM pedido_items WHERE pedido_id = :pid');
                $stmtItemsCan->execute([':pid' => (int)$id]);
                $stmtRestStock = $pdo->prepare(
                    'UPDATE productos SET stock = stock + :cant WHERE id = :id AND stock IS NOT NULL'
                );
                foreach ($stmtItemsCan->fetchAll(PDO::FETCH_ASSOC) as $it) {
                    $stmtRestStock->execute([':cant' => (int)$it['cantidad'], ':id' => (int)$it['producto_id']]);
                }
            }

            json_response(['success' => true]);
        }

        json_response(['error' => 'Método no soportado'], 405);
        break;

    case 'producto-grupos':
        // GET: obtener grupos y opciones de un producto (público)
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $producto_id = $_GET['producto_id'] ?? null;
            if (!$producto_id) json_response(['error' => 'producto_id requerido'], 400);

            $stmt = $pdo->prepare(
                'SELECT pg.id, pg.nombre, pg.tipo, pg.obligatorio,
                        pg.min_selecciones, pg.max_selecciones, pg.max_dinamico_grupo_id, pg.orden,
                        po.id AS op_id, po.nombre AS op_nombre, po.precio_extra,
                        po.max_override, po.orden AS op_orden
                 FROM producto_grupos pg
                 LEFT JOIN producto_opciones po ON po.grupo_id = pg.id AND po.activo = 1
                 WHERE pg.producto_id = :pid AND pg.activo = 1
                 ORDER BY pg.orden, po.orden'
            );
            $stmt->execute([':pid' => (int)$producto_id]);
            $rows = $stmt->fetchAll();

            $grupos = [];
            foreach ($rows as $row) {
                $gid = $row['id'];
                if (!isset($grupos[$gid])) {
                    $grupos[$gid] = [
                        'id'                    => (int) $row['id'],
                        'nombre'                => $row['nombre'],
                        'tipo'                  => $row['tipo'],
                        'obligatorio'           => (bool) $row['obligatorio'],
                        'min_selecciones'       => (int) $row['min_selecciones'],
                        'max_selecciones'       => (int) $row['max_selecciones'],
                        'max_dinamico_grupo_id' => $row['max_dinamico_grupo_id'] ? (int) $row['max_dinamico_grupo_id'] : null,
                        'orden'                 => (int) $row['orden'],
                        'opciones'              => [],
                    ];
                }
                if ($row['op_id']) {
                    $grupos[$gid]['opciones'][] = [
                        'id'           => (int) $row['op_id'],
                        'nombre'       => $row['op_nombre'],
                        'precio_extra' => (float) $row['precio_extra'],
                        'max_override' => $row['max_override'] !== null ? (int) $row['max_override'] : null,
                        'orden'        => (int) $row['op_orden'],
                    ];
                }
            }
            json_response(['grupos' => array_values($grupos)]);
        }

        // POST: reemplazar todos los grupos/opciones de un producto (auth requerida)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_auth();
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $producto_id           = $body['producto_id'] ?? null;
            $tiene_personalizacion = !empty($body['tiene_personalizacion']) ? 1 : 0;
            $aviso_complemento     = $body['aviso_complemento'] ?? null;
            $aviso_categoria_id    = $body['aviso_categoria_id'] ?? null;
            $grupos                = $body['grupos'] ?? [];

            if (!$producto_id) json_response(['error' => 'producto_id requerido'], 400);

            $pdo->beginTransaction();
            try {
                // Actualizar flags en el producto
                $pdo->prepare(
                    'UPDATE productos SET tiene_personalizacion=:tp, aviso_complemento=:ac, aviso_categoria_id=:acid WHERE id=:pid'
                )->execute([
                    ':tp'   => $tiene_personalizacion,
                    ':ac'   => $aviso_complemento ?: null,
                    ':acid' => $aviso_categoria_id ? (int)$aviso_categoria_id : null,
                    ':pid'  => (int)$producto_id,
                ]);

                // Borrado lógico de grupos anteriores
                $pdo->prepare('UPDATE producto_grupos SET activo=0 WHERE producto_id=:pid')
                    ->execute([':pid' => (int)$producto_id]);

                // Pasada 1: insertar grupos, guardar mapa índice → id insertado
                $grupoIds = [];
                $stmtGrupo = $pdo->prepare(
                    'INSERT INTO producto_grupos (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones, orden, activo)
                     VALUES (:pid, :n, :t, :ob, :min, :max, :ord, 1)'
                );
                foreach ($grupos as $idx => $g) {
                    $stmtGrupo->execute([
                        ':pid' => (int)$producto_id,
                        ':n'   => $g['nombre'] ?? '',
                        ':t'   => in_array($g['tipo'] ?? '', ['radio','checkbox']) ? $g['tipo'] : 'radio',
                        ':ob'  => !empty($g['obligatorio']) ? 1 : 0,
                        ':min' => (int)($g['min_selecciones'] ?? 0),
                        ':max' => (int)($g['max_selecciones'] ?? 1),
                        ':ord' => (int)($g['orden'] ?? $idx),
                    ]);
                    $grupoIds[$idx] = (int)$pdo->lastInsertId();
                }

                // Pasada 2: actualizar max_dinamico_grupo_id usando mapa de índices
                $stmtDin = $pdo->prepare('UPDATE producto_grupos SET max_dinamico_grupo_id=:dinid WHERE id=:id');
                foreach ($grupos as $idx => $g) {
                    $dinIdx = $g['max_dinamico_grupo_index'] ?? null;
                    if ($dinIdx !== null && isset($grupoIds[$dinIdx])) {
                        $stmtDin->execute([':dinid' => $grupoIds[$dinIdx], ':id' => $grupoIds[$idx]]);
                    }
                }

                // Insertar opciones
                $stmtOp = $pdo->prepare(
                    'INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, max_override, orden, activo)
                     VALUES (:gid, :n, :pe, :mo, :ord, 1)'
                );
                foreach ($grupos as $idx => $g) {
                    foreach ($g['opciones'] ?? [] as $oidx => $op) {
                        $stmtOp->execute([
                            ':gid' => $grupoIds[$idx],
                            ':n'   => $op['nombre'] ?? '',
                            ':pe'  => (float)($op['precio_extra'] ?? 0),
                            ':mo'  => isset($op['max_override']) && $op['max_override'] !== null ? (int)$op['max_override'] : null,
                            ':ord' => (int)($op['orden'] ?? $oidx),
                        ]);
                    }
                }

                $pdo->commit();
                json_response(['success' => true, 'producto_id' => (int)$producto_id]);
            } catch (Exception $e) {
                $pdo->rollBack();
                json_response(['error' => 'Error al guardar: ' . $e->getMessage()], 500);
            }
        }

        json_response(['error' => 'Método no soportado'], 405);
        break;

    // ── GET historial de cliente por teléfono (público) ──────────────────────
    case 'cliente-historial':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $tel = preg_replace('/\D/', '', trim($_GET['telefono'] ?? ''));
            $rid = intval($_GET['restaurante_id'] ?? 0);
            if (strlen($tel) < 8 || !$rid) json_response(['activo' => false]);

            $stmtCfg = $pdo->prepare('SELECT * FROM recompensas_config WHERE restaurante_id = :rid');
            $stmtCfg->execute([':rid' => $rid]);
            $cfg = $stmtCfg->fetch(PDO::FETCH_ASSOC);
            if (!$cfg || !$cfg['activo']) json_response(['activo' => false]);

            $stmtCli = $pdo->prepare('SELECT * FROM clientes WHERE restaurante_id = :rid AND telefono = :tel');
            $stmtCli->execute([':rid' => $rid, ':tel' => $tel]);
            $cli = $stmtCli->fetch(PDO::FETCH_ASSOC);

            $stmtRef = $pdo->prepare('SELECT codigo_ref FROM referidos WHERE restaurante_id = :rid AND telefono = :tel');
            $stmtRef->execute([':rid' => $rid, ':tel' => $tel]);
            $ref = $stmtRef->fetch(PDO::FETCH_ASSOC);

            $compras   = $cli ? (int)$cli['total_compras'] : 0;
            $necesarias = (int)$cfg['compras_necesarias'];
            $compras_en_ciclo = $necesarias > 0 ? $compras % $necesarias : 0;
            $ciclos_completados = $necesarias > 0 ? (int)floor($compras / $necesarias) : 0;
            $tiene_recompensa = $ciclos_completados > (int)($cli['recompensas_ganadas'] ?? 0);

            json_response([
                'activo'           => true,
                'compras'          => $compras,
                'necesarias'       => $necesarias,
                'compras_en_ciclo' => $tiene_recompensa ? $necesarias : $compras_en_ciclo,
                'tiene_recompensa' => $tiene_recompensa,
                'tipo'             => $cfg['tipo'],
                'valor'            => (float)$cfg['valor'],
                'codigo_ref'       => $ref ? $ref['codigo_ref'] : null,
            ]);
        }
        json_response(['error' => 'Método no soportado'], 405);
        break;

    // ── GET/PUT configuración de recompensas (auth) ───────────────────────
    case 'recompensas-config':
        require_auth();
        $rid = intval($_GET['restaurante_id'] ?? 0);
        if (!$rid) json_response(['error' => 'restaurante_id requerido'], 400);

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $stmt = $pdo->prepare('SELECT * FROM recompensas_config WHERE restaurante_id = :rid');
            $stmt->execute([':rid' => $rid]);
            $cfg = $stmt->fetch(PDO::FETCH_ASSOC);
            json_response($cfg ?: ['restaurante_id' => $rid, 'activo' => 0, 'compras_necesarias' => 10, 'tipo' => 'descuento_fijo', 'valor' => 0]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            $activo     = !empty($body['activo']) ? 1 : 0;
            $necesarias = max(2, (int)($body['compras_necesarias'] ?? 10));
            $tipo       = in_array($body['tipo'] ?? '', ['descuento_porcentaje','descuento_fijo']) ? $body['tipo'] : 'descuento_fijo';
            $valor      = max(0, (float)($body['valor'] ?? 0));
            $pdo->prepare(
                'INSERT INTO recompensas_config (restaurante_id, activo, compras_necesarias, tipo, valor)
                 VALUES (:rid, :a, :n, :t, :v)
                 ON DUPLICATE KEY UPDATE activo=:a, compras_necesarias=:n, tipo=:t, valor=:v'
            )->execute([':rid' => $rid, ':a' => $activo, ':n' => $necesarias, ':t' => $tipo, ':v' => $valor]);
            json_response(['success' => true]);
        }

        json_response(['error' => 'Método no soportado'], 405);
        break;

    // ── codigos-promo: CRUD de códigos para promotores (auth) ────────────────
    case 'codigos-promo':
        require_auth();
        $rid = intval($_GET['restaurante_id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (!$rid) json_response(['error' => 'restaurante_id requerido'], 400);
            $stmt = $pdo->prepare('SELECT * FROM codigos_promo WHERE restaurante_id = :rid AND activo = 1 ORDER BY created_at DESC');
            $stmt->execute([':rid' => $rid]);
            json_response(['codigos' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $body   = json_decode(file_get_contents('php://input'), true) ?: [];
            $rid2   = intval($body['restaurante_id'] ?? 0);
            $codigo = strtoupper(trim($body['codigo'] ?? ''));
            if (!$rid2 || !$codigo) json_response(['error' => 'restaurante_id y codigo son requeridos'], 400);
            $tipo        = in_array($body['tipo'] ?? '', ['descuento_porcentaje','descuento_fijo','envio_gratis']) ? $body['tipo'] : 'descuento_fijo';
            $valor       = max(0, (float)($body['valor'] ?? 0));
            $desc        = trim($body['descripcion'] ?? '') ?: null;
            $usos_maximo = isset($body['usos_maximo']) && $body['usos_maximo'] !== null && $body['usos_maximo'] !== ''
                ? max(1, (int)$body['usos_maximo']) : null;
            $tel_rest    = !empty($body['telefono_restringido']) ? preg_replace('/\D/', '', trim($body['telefono_restringido'])) : null;
            $tel_rest    = ($tel_rest && strlen($tel_rest) >= 8) ? $tel_rest : null;
            try {
                $pdo->prepare(
                    'INSERT INTO codigos_promo (restaurante_id, codigo, descripcion, tipo, valor, usos_maximo, telefono_restringido) VALUES (:rid, :c, :d, :t, :v, :um, :tr)'
                )->execute([':rid' => $rid2, ':c' => $codigo, ':d' => $desc, ':t' => $tipo, ':v' => $valor, ':um' => $usos_maximo, ':tr' => $tel_rest]);
                json_response(['id' => (int)$pdo->lastInsertId()], 201);
            } catch (\PDOException $e) {
                if ($e->getCode() === '23000') json_response(['error' => 'Ese código ya existe para este restaurante'], 409);
                throw $e;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $id   = intval($_GET['id'] ?? 0);
            $body = json_decode(file_get_contents('php://input'), true) ?: [];
            if (!$id) json_response(['error' => 'id requerido'], 400);
            $fields = []; $params = [':id' => $id];
            foreach (['activo','tipo','valor','descripcion','usos_maximo','telefono_restringido'] as $f) {
                if (array_key_exists($f, $body)) { $fields[] = "$f=:$f"; $params[":$f"] = $body[$f]; }
            }
            if ($fields) $pdo->prepare('UPDATE codigos_promo SET '.implode(',', $fields).' WHERE id=:id')->execute($params);
            json_response(['success' => true]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $id = intval($_GET['id'] ?? 0);
            if (!$id) json_response(['error' => 'id requerido'], 400);
            $pdo->prepare('UPDATE codigos_promo SET activo=0 WHERE id=:id')->execute([':id' => $id]);
            json_response(['success' => true]);
        }

        json_response(['error' => 'Método no soportado'], 405);
        break;

    // ── validar-codigo-promo: validación pública desde el checkout ───────────
    case 'validar-codigo-promo':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $codigo = strtoupper(trim($_GET['codigo'] ?? ''));
            $rid    = intval($_GET['restaurante_id'] ?? 0);
            if (!$codigo || !$rid) json_response(['valido' => false]);
            $stmt = $pdo->prepare('SELECT tipo, valor, descripcion, usos, usos_maximo, telefono_restringido FROM codigos_promo WHERE restaurante_id=:rid AND codigo=:c AND activo=1');
            $stmt->execute([':rid' => $rid, ':c' => $codigo]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                if ($row['usos_maximo'] !== null && (int)$row['usos'] >= (int)$row['usos_maximo']) {
                    json_response(['valido' => false, 'motivo' => 'agotado']);
                }
                json_response([
                    'valido'               => true,
                    'tipo'                 => $row['tipo'],
                    'valor'                => (float)$row['valor'],
                    'descripcion'          => $row['descripcion'],
                    'telefono_restringido' => $row['telefono_restringido'] ?: null,
                ]);
            }
            json_response(['valido' => false]);
        }
        json_response(['error' => 'Método no soportado'], 405);
        break;

    // ── vapid-key: devuelve la clave pública VAPID (pública, sin auth) ───────
    case 'vapid-key':
        json_response(['public_key' => VAPID_PUBLIC_KEY ?: null]);
        break;

    // ── push-subscribe: guardar suscripción push de un dispositivo admin ─────
    case 'push-subscribe':
        require_auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Método no soportado'], 405);
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $rid  = (int)($body['restaurante_id'] ?? 0);
        $sub  = $body['subscription'] ?? null;
        if (!$rid || !$sub || empty($sub['endpoint']) || empty($sub['keys']['p256dh']) || empty($sub['keys']['auth'])) {
            json_response(['error' => 'Datos de suscripción inválidos'], 400);
        }
        $endpoint = $sub['endpoint'];
        $stmt = $pdo->prepare(
            'INSERT INTO push_subscriptions (restaurante_id, endpoint, subscription_data)
             VALUES (:rid, :ep, :data)
             ON DUPLICATE KEY UPDATE subscription_data = :data2, restaurante_id = :rid2'
        );
        $encoded = json_encode($sub);
        $stmt->execute([
            ':rid'   => $rid,
            ':ep'    => $endpoint,
            ':data'  => $encoded,
            ':data2' => $encoded,
            ':rid2'  => $rid,
        ]);
        json_response(['ok' => true], 201);
        break;

    // ── push-unsubscribe: eliminar suscripción push ───────────────────────────
    case 'push-unsubscribe':
        require_auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Método no soportado'], 405);
        $body     = json_decode(file_get_contents('php://input'), true) ?: [];
        $endpoint = $body['endpoint'] ?? '';
        if (!$endpoint) json_response(['error' => 'endpoint requerido'], 400);
        $pdo->prepare('DELETE FROM push_subscriptions WHERE endpoint = :ep')
            ->execute([':ep' => $endpoint]);
        json_response(['ok' => true]);
        break;

    // ── reportes: corte de ventas por período (auth) ─────────────────────────
    case 'reportes':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require_auth();
            $rid   = intval($_GET['restaurante_id'] ?? 0);
            $desde = $_GET['desde'] ?? date('Y-m-d');
            $hasta = $_GET['hasta'] ?? date('Y-m-d');
            if (!$rid) json_response(['error' => 'restaurante_id requerido'], 400);
            // Sanitizar fechas
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = date('Y-m-d');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = date('Y-m-d');
            if ($hasta < $desde) $hasta = $desde;

            // Resumen agregado (excluye cancelados)
            $stmtRes = $pdo->prepare(
                'SELECT
                    COUNT(*) AS total_pedidos,
                    COALESCE(SUM(p.total + p.ajuste_manual), 0) AS ingresos_netos,
                    COALESCE(SUM(p.costo_envio), 0) AS total_envios,
                    COALESCE(SUM(p.descuento_recompensa), 0) AS desc_recompensa,
                    COALESCE(SUM(p.descuento_promo), 0) AS desc_promo,
                    COALESCE(SUM(GREATEST(0, -p.ajuste_manual)), 0) AS ajustes_negativos,
                    COUNT(CASE WHEN p.codigo_promo IS NOT NULL AND p.descuento_promo = 0 THEN 1 END) AS cupones_envio_gratis,
                    COALESCE(SUM(CASE WHEN p.metodo_pago=\'efectivo\'      THEN p.total+p.ajuste_manual ELSE 0 END), 0) AS efectivo,
                    COALESCE(SUM(CASE WHEN p.metodo_pago=\'transferencia\' THEN p.total+p.ajuste_manual ELSE 0 END), 0) AS transferencia,
                    COALESCE(SUM(CASE WHEN p.metodo_pago=\'terminal\'      THEN p.total+p.ajuste_manual ELSE 0 END), 0) AS terminal
                 FROM pedidos p
                 WHERE p.restaurante_id=:rid AND p.status != \'cancelado\'
                   AND DATE(p.created_at) BETWEEN :desde AND :hasta'
            );
            $stmtRes->execute([':rid' => $rid, ':desde' => $desde, ':hasta' => $hasta]);
            $resumen = $stmtRes->fetch(PDO::FETCH_ASSOC);
            // Castear a float
            $int_fields = ['total_pedidos', 'cupones_envio_gratis'];
            foreach ($resumen as $k => $v) {
                $resumen[$k] = in_array($k, $int_fields) ? (int)$v : (float)$v;
            }

            // Desglose por día
            $stmtDia = $pdo->prepare(
                'SELECT DATE(p.created_at) AS dia, COUNT(*) AS pedidos,
                        COALESCE(SUM(p.total + p.ajuste_manual), 0) AS total_dia
                 FROM pedidos p
                 WHERE p.restaurante_id=:rid AND p.status != \'cancelado\'
                   AND DATE(p.created_at) BETWEEN :desde AND :hasta
                 GROUP BY DATE(p.created_at) ORDER BY dia ASC'
            );
            $stmtDia->execute([':rid' => $rid, ':desde' => $desde, ':hasta' => $hasta]);
            $por_dia = $stmtDia->fetchAll(PDO::FETCH_ASSOC);
            foreach ($por_dia as &$d) {
                $d['pedidos']   = (int)$d['pedidos'];
                $d['total_dia'] = (float)$d['total_dia'];
            }
            unset($d);

            json_response(['resumen' => $resumen, 'por_dia' => $por_dia]);
        }
        json_response(['error' => 'Método no soportado'], 405);
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Ruta no encontrada']);
        break;
}
