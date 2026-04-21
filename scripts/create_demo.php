<?php
/**
 * create_demo.php — Genera un demo para un prospecto forkeando una plantilla
 *
 * Uso:
 *   php scripts/create_demo.php \
 *     --template=taqueria \
 *     --slug=tacos-garcia \
 *     --nombre="Tacos García" \
 *     --whatsapp=9611234567 \
 *     --email=garcia@demo.com \
 *     --pass=demo1234 \
 *     --days=7
 *
 * Rubros disponibles: taqueria | burgers | pizza | mariscos | cafe
 *
 * Requisitos:
 *   - PHP 8.1+
 *   - BD demo (nodosmxc_menu_demos) ya inicializada con init_demo_db.sql
 *   - Los templates de rubro ya ejecutados (template_taqueria.sql, etc.)
 *   - Ejecutar desde la raíz del proyecto: c:/xampp/htdocs/menu_qr_3d/
 */

// ── Configuración de conexión a la BD demo ────────────────────────────────────
// Ajustar según entorno: local o producción
$DEMO_DB = [
    'host' => 'localhost',
    'name' => 'nodosmxc_menu_demos',
    'user' => 'root',        // local: root / prod: nodosmxc_user_demos
    'pass' => '',            // local: vacío / prod: password real
];

$BASE_URL_DEMO = 'https://nodosmx.com'; // URL base del servidor de demos


// ── Parsear argumentos ────────────────────────────────────────────────────────
$opts = getopt('', [
    'template:', 'slug:', 'nombre:', 'whatsapp:', 'email:', 'pass:', 'days::'
]);

$errors = [];
if (empty($opts['template'])) $errors[] = '--template es requerido (taqueria|burgers|pizza|mariscos|cafe)';
if (empty($opts['slug']))     $errors[] = '--slug es requerido (ej: tacos-garcia)';
if (empty($opts['nombre']))   $errors[] = '--nombre es requerido (ej: "Tacos García")';
if (empty($opts['email']))    $errors[] = '--email es requerido';
if (empty($opts['pass']))     $errors[] = '--pass es requerido';

if ($errors) {
    echo "\n❌ Errores:\n";
    foreach ($errors as $e) echo "   · $e\n";
    echo "\nUso:\n  php scripts/create_demo.php --template=taqueria --slug=tacos-garcia --nombre=\"Tacos García\" --whatsapp=9611234567 --email=garcia@demo.com --pass=demo1234 [--days=7]\n\n";
    exit(1);
}

$template  = strtolower(trim($opts['template']));
$slug      = strtolower(trim($opts['slug']));
$nombre    = trim($opts['nombre']);
$whatsapp  = trim($opts['whatsapp'] ?? '');
$email     = trim($opts['email']);
$pass      = trim($opts['pass']);
$days      = (int) ($opts['days'] ?? 7);

$rubrosValidos = ['taqueria', 'burgers', 'pizza', 'mariscos', 'cafe'];
if (!in_array($template, $rubrosValidos)) {
    echo "\n❌ --template '$template' no válido. Opciones: " . implode(', ', $rubrosValidos) . "\n\n";
    exit(1);
}

$templateSlug = "template-$template";

// ── Conectar a BD demo ────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host={$DEMO_DB['host']};dbname={$DEMO_DB['name']};charset=utf8mb4",
        $DEMO_DB['user'],
        $DEMO_DB['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    echo "\n❌ No se pudo conectar a la BD demo: " . $e->getMessage() . "\n";
    echo "   Verifica que la BD '{$DEMO_DB['name']}' existe y las credenciales son correctas.\n\n";
    exit(1);
}

// ── Verificar que el slug destino no exista ────────────────────────────────────
$check = $pdo->prepare('SELECT id FROM restaurantes WHERE slug = :slug');
$check->execute([':slug' => $slug]);
if ($check->fetch()) {
    echo "\n❌ El slug '$slug' ya existe en la BD demo. Elige otro.\n\n";
    exit(1);
}

// ── Verificar que el email no exista ──────────────────────────────────────────
$checkEmail = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email');
$checkEmail->execute([':email' => $email]);
if ($checkEmail->fetch()) {
    echo "\n❌ El email '$email' ya existe. Usa otro o elimina el usuario previo.\n\n";
    exit(1);
}

// ── Leer plantilla ────────────────────────────────────────────────────────────
$tmpl = $pdo->prepare('SELECT * FROM restaurantes WHERE slug = :slug');
$tmpl->execute([':slug' => $templateSlug]);
$restauranteTmpl = $tmpl->fetch();

if (!$restauranteTmpl) {
    echo "\n❌ No se encontró la plantilla '$templateSlug' en la BD demo.\n";
    echo "   Ejecuta primero: database/demos/template_$template.sql\n\n";
    exit(1);
}

$tmplId = $restauranteTmpl['id'];

// ── Leer categorías de la plantilla ───────────────────────────────────────────
$cats = $pdo->prepare('SELECT * FROM categorias WHERE restaurante_id = :rid AND activo = 1 ORDER BY orden');
$cats->execute([':rid' => $tmplId]);
$categorias = $cats->fetchAll();

// ── Leer productos de la plantilla ────────────────────────────────────────────
$prods = $pdo->prepare('
    SELECT p.* FROM productos p
    JOIN categorias c ON c.id = p.categoria_id
    WHERE c.restaurante_id = :rid AND p.activo = 1
    ORDER BY p.orden
');
$prods->execute([':rid' => $tmplId]);
$productos = $prods->fetchAll();

// ── Leer recompensas_config de la plantilla ───────────────────────────────────
$rcfg = $pdo->prepare('SELECT * FROM recompensas_config WHERE restaurante_id = :rid');
$rcfg->execute([':rid' => $tmplId]);
$recompensasConfig = $rcfg->fetch();

echo "\n🔧 Creando demo: $nombre ($slug) desde plantilla: $templateSlug\n";

$pdo->beginTransaction();

try {
    // 1. Crear usuario del prospecto
    $passHash = password_hash($pass, PASSWORD_DEFAULT);
    $insUsr = $pdo->prepare('
        INSERT INTO usuarios (nombre, email, password_hash, rol)
        VALUES (:nombre, :email, :pass, "admin")
    ');
    $insUsr->execute([
        ':nombre' => $nombre,
        ':email'  => $email,
        ':pass'   => $passHash,
    ]);
    $nuevoUserId = $pdo->lastInsertId();

    // 2. Crear restaurante copiando de la plantilla
    $trialExpires = date('Y-m-d H:i:s', strtotime("+$days days"));

    // Logo: heredar path del template (apunta a demos/logo_*.jpg compartido)
    // El usuario puede reemplazarlo desde el admin después
    $insRest = $pdo->prepare('
        INSERT INTO restaurantes (
            usuario_id, slug, nombre, descripcion, tema, activo,
            logo_url,
            pedidos_activos, pedidos_envio_activo, pedidos_envio_costo,
            pedidos_envio_gratis_desde, pedidos_whatsapp,
            pedidos_trans_activo, pedidos_terminal_activo,
            codigos_promo_habilitado, stock_minimo_aviso,
            compartir_mensaje, trial_expires_at
        ) VALUES (
            :uid, :slug, :nombre, :desc, :tema, 1,
            :logo_url,
            :ped_activos, :ped_env_activo, :ped_env_costo,
            :ped_env_gratis, :whatsapp,
            :trans_activo, :terminal_activo,
            :promo_hab, :stock_min,
            :compartir, :trial
        )
    ');
    $insRest->execute([
        ':uid'           => $nuevoUserId,
        ':slug'          => $slug,
        ':nombre'        => $nombre,
        ':desc'          => $restauranteTmpl['descripcion'],
        ':tema'          => $restauranteTmpl['tema'],
        ':logo_url'      => $restauranteTmpl['logo_url'],
        ':ped_activos'   => $restauranteTmpl['pedidos_activos'],
        ':ped_env_activo'=> $restauranteTmpl['pedidos_envio_activo'],
        ':ped_env_costo' => $restauranteTmpl['pedidos_envio_costo'],
        ':ped_env_gratis'=> $restauranteTmpl['pedidos_envio_gratis_desde'],
        ':whatsapp'      => $whatsapp ?: $restauranteTmpl['pedidos_whatsapp'],
        ':trans_activo'  => $restauranteTmpl['pedidos_trans_activo'],
        ':terminal_activo'=> $restauranteTmpl['pedidos_terminal_activo'],
        ':promo_hab'     => $restauranteTmpl['codigos_promo_habilitado'],
        ':stock_min'     => $restauranteTmpl['stock_minimo_aviso'],
        ':compartir'     => $restauranteTmpl['compartir_mensaje'],
        ':trial'         => $trialExpires,
    ]);
    $nuevoRestId = $pdo->lastInsertId();

    // 3. Copiar recompensas_config
    if ($recompensasConfig) {
        $insRcfg = $pdo->prepare('
            INSERT INTO recompensas_config (restaurante_id, activo, compras_necesarias, tipo, valor)
            VALUES (:rid, :activo, :compras, :tipo, :valor)
        ');
        $insRcfg->execute([
            ':rid'     => $nuevoRestId,
            ':activo'  => $recompensasConfig['activo'],
            ':compras' => $recompensasConfig['compras_necesarias'],
            ':tipo'    => $recompensasConfig['tipo'],
            ':valor'   => $recompensasConfig['valor'],
        ]);
    }

    // 4. Copiar categorías y construir mapa old_id → new_id
    $catMap = [];
    $insCat = $pdo->prepare('
        INSERT INTO categorias (restaurante_id, nombre, icono, orden)
        VALUES (:rid, :nombre, :icono, :orden)
    ');
    foreach ($categorias as $cat) {
        $insCat->execute([
            ':rid'    => $nuevoRestId,
            ':nombre' => $cat['nombre'],
            ':icono'  => $cat['icono'],
            ':orden'  => $cat['orden'],
        ]);
        $catMap[$cat['id']] = $pdo->lastInsertId();
    }

    // 5. Copiar productos usando el mapa de categorías
    $prodMap = []; // old producto id → new producto id
    $insProd = $pdo->prepare('
        INSERT INTO productos (
            categoria_id, nombre, descripcion, precio, foto_principal,
            tiene_ar, es_destacado, disponible, activo, stock,
            tiene_personalizacion, aviso_complemento, orden
        ) VALUES (
            :cat_id, :nombre, :desc, :precio, :foto,
            :tiene_ar, :destacado, 1, 1, NULL,
            :pers, :aviso, :orden
        )
    ');
    foreach ($productos as $prod) {
        $nuevaCatId = $catMap[$prod['categoria_id']] ?? null;
        if (!$nuevaCatId) continue;

        $insProd->execute([
            ':cat_id'   => $nuevaCatId,
            ':nombre'   => $prod['nombre'],
            ':desc'     => $prod['descripcion'],
            ':precio'   => $prod['precio'],
            ':foto'     => $prod['foto_principal'], // path compartido demos/xxx.jpg
            ':tiene_ar' => $prod['tiene_ar'],
            ':destacado'=> $prod['es_destacado'],
            ':pers'     => $prod['tiene_personalizacion'],
            ':aviso'    => $prod['aviso_complemento'],
            ':orden'    => $prod['orden'],
        ]);
        $prodMap[$prod['id']] = $pdo->lastInsertId();
    }

    // 6. Copiar producto_grupos y producto_opciones
    $gruposCopied   = 0;
    $opcionesCopied = 0;
    if ($prodMap) {
        $oldProdIds = array_keys($prodMap);
        $ph         = implode(',', array_fill(0, count($oldProdIds), '?'));

        $stmtGrupos = $pdo->prepare(
            "SELECT * FROM producto_grupos WHERE producto_id IN ($ph) AND activo = 1 ORDER BY id"
        );
        $stmtGrupos->execute($oldProdIds);
        $gruposData = $stmtGrupos->fetchAll();

        $grupoMap = []; // old grupo id → new grupo id
        $insGrp   = $pdo->prepare('
            INSERT INTO producto_grupos
                (producto_id, nombre, tipo, obligatorio, min_selecciones, max_selecciones,
                 max_dinamico_grupo_id, orden)
            VALUES
                (:prod_id, :nombre, :tipo, :obligatorio, :min_sel, :max_sel, NULL, :orden)
        ');
        foreach ($gruposData as $grp) {
            $nuevoProdId = $prodMap[$grp['producto_id']] ?? null;
            if (!$nuevoProdId) continue;

            $insGrp->execute([
                ':prod_id'    => $nuevoProdId,
                ':nombre'     => $grp['nombre'],
                ':tipo'       => $grp['tipo'],
                ':obligatorio'=> $grp['obligatorio'],
                ':min_sel'    => $grp['min_selecciones'],
                ':max_sel'    => $grp['max_selecciones'],
                ':orden'      => $grp['orden'],
            ]);
            $grupoMap[$grp['id']] = $pdo->lastInsertId();
            $gruposCopied++;
        }

        // Restaurar referencias max_dinamico_grupo_id usando el mapa de grupos
        foreach ($gruposData as $grp) {
            if ($grp['max_dinamico_grupo_id'] !== null) {
                $nuevoGrupoId       = $grupoMap[$grp['id']] ?? null;
                $nuevoMaxDinamicoId = $grupoMap[$grp['max_dinamico_grupo_id']] ?? null;
                if ($nuevoGrupoId && $nuevoMaxDinamicoId) {
                    $pdo->prepare('UPDATE producto_grupos SET max_dinamico_grupo_id = :mdg WHERE id = :id')
                        ->execute([':mdg' => $nuevoMaxDinamicoId, ':id' => $nuevoGrupoId]);
                }
            }
        }

        if ($grupoMap) {
            $oldGrupoIds = array_keys($grupoMap);
            $ph2         = implode(',', array_fill(0, count($oldGrupoIds), '?'));

            $stmtOpc = $pdo->prepare(
                "SELECT * FROM producto_opciones WHERE grupo_id IN ($ph2) AND activo = 1 ORDER BY id"
            );
            $stmtOpc->execute($oldGrupoIds);
            $opcionesData = $stmtOpc->fetchAll();

            $insOpc = $pdo->prepare('
                INSERT INTO producto_opciones (grupo_id, nombre, precio_extra, max_override, orden)
                VALUES (:grupo_id, :nombre, :precio_extra, :max_override, :orden)
            ');
            foreach ($opcionesData as $opc) {
                $nuevoGrupoId = $grupoMap[$opc['grupo_id']] ?? null;
                if (!$nuevoGrupoId) continue;

                $insOpc->execute([
                    ':grupo_id'    => $nuevoGrupoId,
                    ':nombre'      => $opc['nombre'],
                    ':precio_extra'=> $opc['precio_extra'],
                    ':max_override'=> $opc['max_override'],
                    ':orden'       => $opc['orden'],
                ]);
                $opcionesCopied++;
            }
        }
    }

    // 7. Registrar la demo creada para seguimiento comercial/operativo
    $insRegistro = $pdo->prepare('
        INSERT INTO demo_registros (
            restaurante_id, usuario_id, template, slug, nombre, whatsapp, email,
            trial_dias, trial_expires_at, estado, origen
        ) VALUES (
            :restaurante_id, :usuario_id, :template, :slug, :nombre, :whatsapp, :email,
            :trial_dias, :trial_expires_at, "activa", "create_demo.php"
        )
    ');
    $insRegistro->execute([
        ':restaurante_id'   => $nuevoRestId,
        ':usuario_id'       => $nuevoUserId,
        ':template'         => $template,
        ':slug'             => $slug,
        ':nombre'           => $nombre,
        ':whatsapp'         => $whatsapp ?: null,
        ':email'            => $email,
        ':trial_dias'       => $days,
        ':trial_expires_at' => $trialExpires,
    ]);

    $pdo->commit();

    // ── Output final ──────────────────────────────────────────────────────────
    $rubroSubdominio = [
        'taqueria' => 'taqueria',
        'burgers'  => 'burgers',
        'pizza'    => 'pizza',
        'mariscos' => 'mariscos',
        'cafe'     => 'cafe',
    ][$template];

    $host     = parse_url($BASE_URL_DEMO, PHP_URL_HOST);
    $menuUrl  = "https://$rubroSubdominio.$host/menu/?r=$slug";
    $adminUrl = "https://$rubroSubdominio.$host/menu/admin";

    echo "\n✅ Demo creada exitosamente\n";
    echo "   ────────────────────────────────────────\n";
    echo "   Menú público: $menuUrl\n";
    echo "   Admin:        $adminUrl\n";
    echo "   Usuario:      $email\n";
    echo "   Pass:         $pass\n";
    echo "   Expira:       " . date('d/m/Y', strtotime($trialExpires)) . " ($days días)\n";
    echo "   restaurante_id: $nuevoRestId\n";
    echo "   Registro:      demo_registros\n";
    echo "   ────────────────────────────────────────\n";
    echo "   Categorías copiadas: " . count($catMap) . "\n";
    echo "   Productos copiados:  " . count($prodMap) . "\n";
    echo "   Grupos opciones:     $gruposCopied grupos, $opcionesCopied opciones\n\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "\n❌ Error durante la creación: " . $e->getMessage() . "\n";
    echo "   Se revirtieron todos los cambios.\n\n";
    exit(1);
}
