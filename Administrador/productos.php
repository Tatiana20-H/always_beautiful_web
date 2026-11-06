<?php
require_once __DIR__ . '/Seguridad.php';
require_once __DIR__ . '/../conexion.php';
$conexion = $GLOBALS['conexion'];

if (empty($_SESSION['csrf_catalogo'])) {
    $_SESSION['csrf_catalogo'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_catalogo'];
$aviso = '';
$avisoTipo = 'success';
$categorias = ['Maquillaje', 'Piel', 'Cabello', 'Accesorios'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Solicitud no válida. Recarga la página e inténtalo de nuevo.');
    }

    $accion = $_POST['accion'] ?? '';
    $administradorId = (int) $_SESSION['usuario_id'];
    if ($accion === 'producto_guardar') {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $imagen = trim($_POST['imagen'] ?? '');
        $categoria = $_POST['categoria'] ?? '';
        $precio = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($nombre === '' || strlen($nombre) > 150 || $descripcion === '' || strlen($descripcion) > 5000 || !in_array($categoria, $categorias, true) || $precio === false || $stock === false || strlen($imagen) > 255) {
            $aviso = 'Completa nombre, descripción, categoría, precio y stock con valores válidos.';
            $avisoTipo = 'error';
        } else {
            $stmt = $conexion->prepare('INSERT INTO productos (nombre, precio, stock, categoria, descripcion, imagen, administrador_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('siisssi', $nombre, $precio, $stock, $categoria, $descripcion, $imagen, $administradorId);
            if ($stmt->execute()) {
                header('Location: productos.php?vista=productos&guardado=producto');
                exit();
            }
            $aviso = $stmt->errno === 1062 ? 'Ya existe un producto con ese nombre.' : 'No fue posible guardar el producto.';
            $avisoTipo = 'error';
            $stmt->close();
        }
    } elseif ($accion === 'producto_eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conexion->prepare('UPDATE productos SET activo = 0 WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        header('Location: productos.php?vista=productos&guardado=eliminado');
        exit();
    } elseif ($accion === 'promocion_guardar') {
        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $categoria = $_POST['categoria'] ?? '';
        $imagen = trim($_POST['imagen'] ?? '');
        $precio = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($titulo === '' || strlen($titulo) > 150 || $descripcion === '' || strlen($descripcion) > 5000 || !in_array($categoria, $categorias, true) || $precio === false || strlen($imagen) > 255) {
            $aviso = 'Completa los datos de la promoción con valores válidos.';
            $avisoTipo = 'error';
        } else {
            $stmt = $conexion->prepare('INSERT INTO promociones (titulo, descripcion, categoria, precio, imagen, administrador_id) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssdsi', $titulo, $descripcion, $categoria, $precio, $imagen, $administradorId);
            if ($stmt->execute()) {
                header('Location: productos.php?vista=promociones&guardado=promocion');
                exit();
            }
            $aviso = 'No fue posible guardar la promoción.';
            $avisoTipo = 'error';
            $stmt->close();
        }
    } elseif ($accion === 'promocion_eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conexion->prepare('UPDATE promociones SET activo = 0 WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        header('Location: productos.php?vista=promociones&guardado=eliminado');
        exit();
    } elseif ($accion === 'combo_guardar') {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $productos = trim($_POST['productos'] ?? '');
        $imagen = trim($_POST['imagen'] ?? '');
        $precio = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($nombre === '' || strlen($nombre) > 150 || $descripcion === '' || strlen($descripcion) > 5000 || strlen($productos) > 2000 || $precio === false || strlen($imagen) > 255) {
            $aviso = 'Completa los datos del combo con valores válidos.';
            $avisoTipo = 'error';
        } else {
            $stmt = $conexion->prepare('INSERT INTO combos (nombre, descripcion, precio, imagen, productos, administrador_id) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssdssi', $nombre, $descripcion, $precio, $imagen, $productos, $administradorId);
            if ($stmt->execute()) {
                header('Location: productos.php?vista=combos&guardado=combo');
                exit();
            }
            $aviso = 'No fue posible guardar el combo.';
            $avisoTipo = 'error';
            $stmt->close();
        }
    } elseif ($accion === 'combo_eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conexion->prepare('UPDATE combos SET activo = 0 WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        header('Location: productos.php?vista=combos&guardado=eliminado');
        exit();
    } elseif ($accion === 'galeria_guardar') {
        $archivo = $_FILES['imagen_galeria'] ?? null;
        $mimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = $archivo && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && is_uploaded_file($archivo['tmp_name'] ?? '')
            ? mime_content_type($archivo['tmp_name'])
            : '';
        $guardado = false;
        if ($archivo && isset($mimes[$mime]) && ($archivo['size'] ?? 0) <= 5 * 1024 * 1024) {
            $directorio = __DIR__ . '/../IMG/galeria';
            if (!is_dir($directorio)) {
                mkdir($directorio, 0755, true);
            }
            $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $mimes[$mime];
            $destino = $directorio . '/' . $nombreArchivo;
            if (move_uploaded_file($archivo['tmp_name'], $destino)) {
                $ruta = 'IMG/galeria/' . $nombreArchivo;
                $nombreOriginal = substr(basename($archivo['name']), 0, 150);
                $stmt = $conexion->prepare('INSERT INTO galeria_inicio (nombre, ruta, administrador_id) VALUES (?, ?, ?)');
                $stmt->bind_param('ssi', $nombreOriginal, $ruta, $administradorId);
                $guardado = $stmt->execute();
                $stmt->close();
                if (!$guardado) {
                    unlink($destino);
                }
            }
        }
        header('Location: productos.php?vista=galeria&guardado=' . ($guardado ? 'imagen' : 'error'));
        exit();
    }
}

$vista = $_GET['vista'] ?? 'productos';
if (!in_array($vista, ['productos', 'promociones', 'combos', 'galeria'], true)) {
    $vista = 'productos';
}
$mensajes = [
    'producto' => 'Producto agregado al catálogo.',
    'promocion' => 'Promoción agregada al inicio.',
    'combo' => 'Combo agregado al inicio.',
    'eliminado' => 'El elemento se quitó del catálogo.',
    'imagen' => 'Imagen agregada a la galería.',
    'error' => 'No se pudo guardar el archivo. Usa JPG, PNG o WEBP de hasta 5 MB.'
];
if (isset($_GET['guardado'], $mensajes[$_GET['guardado']])) {
    $aviso = $mensajes[$_GET['guardado']];
    $avisoTipo = $_GET['guardado'] === 'error' ? 'error' : 'success';
}

$productos = $conexion->query('SELECT id, nombre, precio, stock, categoria, activo FROM productos ORDER BY id ASC');
$promociones = $conexion->query('SELECT id, titulo, categoria, precio, activo FROM promociones ORDER BY id ASC');
$combos = $conexion->query('SELECT id, nombre, precio, productos, activo FROM combos ORDER BY id ASC');
$galeria = $conexion->query('SELECT id, nombre, ruta FROM galeria_inicio ORDER BY id ASC');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo | Always Beautiful</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        .catalogo-admin { color: #403834; margin: 24px auto; max-width: 1320px; padding: 0 24px 48px; }
        .catalogo-admin h1 { color: #481b18; font-size: 30px; margin: 26px 0 18px; }
        .catalogo-tabs { border-bottom: 1px solid #cdbfb3; display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 24px; }
        .catalogo-tabs a { border-bottom: 3px solid transparent; color: #5b3c35; font-weight: 700; padding: 12px 16px; text-decoration: none; }
        .catalogo-tabs a[aria-current="page"] { border-color: #a96d4b; color: #481b18; }
        .catalogo-grid { align-items: start; display: grid; gap: 22px; grid-template-columns: minmax(280px, 360px) minmax(0, 1fr); }
        .catalogo-panel { background: #fbf7f3; border: 1px solid #d7b8ad; border-radius: 10px; padding: 22px; }
        .catalogo-panel h2 { color: #743629; font-size: 20px; margin: 0 0 16px; }
        .catalogo-form { display: grid; gap: 12px; }
        .catalogo-form label { color: #5b3c35; font-size: 13px; font-weight: 700; }
        .catalogo-form input, .catalogo-form textarea, .catalogo-form select { background: #fff; border: 1px solid #cdbfb3; border-radius: 6px; box-sizing: border-box; color: #403834; padding: 11px 12px; width: 100%; }
        .catalogo-form textarea { min-height: 100px; resize: vertical; }
        .catalogo-form button, .catalogo-delete { background: #481b18; border: 0; border-radius: 6px; color: #fff; cursor: pointer; font-weight: 700; padding: 11px 14px; }
        .catalogo-delete { background: #7a3028; padding: 8px 10px; }
        .catalogo-table-wrap { overflow-x: auto; }
        .catalogo-table { border-collapse: collapse; min-width: 640px; width: 100%; }
        .catalogo-table th { background: #743629; color: #fff; padding: 12px; text-align: left; }
        .catalogo-table td { border-bottom: 1px solid #e5d8ce; padding: 12px; vertical-align: middle; }
        .catalogo-table tr:last-child td { border-bottom: 0; }
        .catalogo-status { color: #2f6848; font-weight: 700; }
        .catalogo-status.inactivo { color: #8a3830; }
        .catalogo-alerta { border-radius: 6px; margin-bottom: 16px; padding: 12px 14px; }
        .catalogo-alerta.success { background: #e6f1e8; color: #285d3b; }
        .catalogo-alerta.error { background: #f7e6e2; color: #7a3028; }
        @media (max-width: 850px) { .catalogo-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<?php include __DIR__ . '/header-admin.php'; ?>
<main class="catalogo-admin">
    <h1>Gestión del catálogo</h1>
    <nav class="catalogo-tabs" aria-label="Secciones del catálogo">
        <?php foreach (['productos' => 'Productos', 'promociones' => 'Promociones', 'combos' => 'Combos', 'galeria' => 'Galería'] as $clave => $etiqueta): ?>
            <a href="?vista=<?= $clave ?>" <?= $vista === $clave ? 'aria-current="page"' : '' ?>><?= $etiqueta ?></a>
        <?php endforeach; ?>
    </nav>
    <?php if ($aviso !== ''): ?><div class="catalogo-alerta <?= $avisoTipo ?>" role="status"><?= htmlspecialchars($aviso) ?></div><?php endif; ?>

    <?php if ($vista === 'productos'): ?>
        <div class="catalogo-grid">
            <section class="catalogo-panel">
                <h2>Agregar producto</h2>
                <form class="catalogo-form" method="POST">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="accion" value="producto_guardar">
                    <label>Nombre<input name="nombre" maxlength="150" required></label>
                    <label>Descripción<textarea name="descripcion" maxlength="5000" required></textarea></label>
                    <label>Catálogo<select name="categoria" required><option value="">Selecciona categoría</option><?php foreach ($categorias as $categoria): ?><option><?= htmlspecialchars($categoria) ?></option><?php endforeach; ?></select></label>
                    <label>Precio en COP<input type="number" name="precio" min="1" step="1" required></label>
                    <label>Stock<input type="number" name="stock" min="0" step="1" required></label>
                    <label>Ruta de imagen<input name="imagen" maxlength="255" placeholder="IMG/producto.jpg"></label>
                    <button type="submit">Guardar producto</button>
                </form>
            </section>
            <section class="catalogo-panel"><h2>Productos del catálogo</h2><div class="catalogo-table-wrap"><table class="catalogo-table"><thead><tr><th>ID</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
                <?php while ($fila = $productos->fetch_assoc()): ?><tr><td><?= (int) $fila['id'] ?></td><td><?= htmlspecialchars($fila['nombre']) ?></td><td><?= htmlspecialchars($fila['categoria']) ?></td><td>$<?= number_format((int) $fila['precio'], 0, ',', '.') ?></td><td><?= (int) $fila['stock'] ?></td><td class="catalogo-status <?= $fila['activo'] ? '' : 'inactivo' ?>"><?= $fila['activo'] ? 'Activo' : 'Retirado' ?></td><td><?php if ($fila['activo']): ?><form method="POST" onsubmit="return confirm('¿Retirar este producto del catálogo?')"><input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="accion" value="producto_eliminar"><input type="hidden" name="id" value="<?= (int) $fila['id'] ?>"><button class="catalogo-delete" type="submit">Retirar</button></form><?php endif; ?></td></tr><?php endwhile; ?>
            </tbody></table></div></section>
        </div>
    <?php elseif ($vista === 'promociones'): ?>
        <div class="catalogo-grid">
            <section class="catalogo-panel"><h2>Agregar promoción para inicio</h2><form class="catalogo-form" method="POST"><input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="accion" value="promocion_guardar"><label>Título<input name="titulo" maxlength="150" required></label><label>Descripción<textarea name="descripcion" maxlength="5000" required></textarea></label><label>Categoría<select name="categoria" required><?php foreach ($categorias as $categoria): ?><option><?= htmlspecialchars($categoria) ?></option><?php endforeach; ?></select></label><label>Precio en COP<input type="number" name="precio" min="0" required></label><label>Ruta de imagen<input name="imagen" maxlength="255" placeholder="IMG/promocion.jpg"></label><button type="submit">Publicar promoción</button></form></section>
            <section class="catalogo-panel"><h2>Promociones</h2><div class="catalogo-table-wrap"><table class="catalogo-table"><thead><tr><th>ID</th><th>Título</th><th>Categoría</th><th>Precio</th><th>Estado</th><th>Acción</th></tr></thead><tbody><?php while ($fila = $promociones->fetch_assoc()): ?><tr><td><?= (int) $fila['id'] ?></td><td><?= htmlspecialchars($fila['titulo']) ?></td><td><?= htmlspecialchars($fila['categoria']) ?></td><td>$<?= number_format((float) $fila['precio'], 0, ',', '.') ?></td><td class="catalogo-status <?= $fila['activo'] ? '' : 'inactivo' ?>"><?= $fila['activo'] ? 'Activa' : 'Retirada' ?></td><td><?php if ($fila['activo']): ?><form method="POST" onsubmit="return confirm('¿Retirar esta promoción?')"><input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="accion" value="promocion_eliminar"><input type="hidden" name="id" value="<?= (int) $fila['id'] ?>"><button class="catalogo-delete">Retirar</button></form><?php endif; ?></td></tr><?php endwhile; ?></tbody></table></div></section>
        </div>
    <?php elseif ($vista === 'combos'): ?>
        <div class="catalogo-grid">
            <section class="catalogo-panel"><h2>Agregar combo para inicio</h2><form class="catalogo-form" method="POST"><input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="accion" value="combo_guardar"><label>Nombre<input name="nombre" maxlength="150" required></label><label>Descripción<textarea name="descripcion" maxlength="5000" required></textarea></label><label>Productos incluidos<textarea name="productos" maxlength="2000" placeholder="Un producto por línea"></textarea></label><label>Precio en COP<input type="number" name="precio" min="0" required></label><label>Ruta de imagen<input name="imagen" maxlength="255" placeholder="IMG/combo.jpg"></label><button type="submit">Publicar combo</button></form></section>
            <section class="catalogo-panel"><h2>Combos</h2><div class="catalogo-table-wrap"><table class="catalogo-table"><thead><tr><th>ID</th><th>Nombre</th><th>Incluye</th><th>Precio</th><th>Estado</th><th>Acción</th></tr></thead><tbody><?php while ($fila = $combos->fetch_assoc()): ?><tr><td><?= (int) $fila['id'] ?></td><td><?= htmlspecialchars($fila['nombre']) ?></td><td><?= nl2br(htmlspecialchars($fila['productos'] ?? '')) ?></td><td>$<?= number_format((float) $fila['precio'], 0, ',', '.') ?></td><td class="catalogo-status <?= $fila['activo'] ? '' : 'inactivo' ?>"><?= $fila['activo'] ? 'Activo' : 'Retirado' ?></td><td><?php if ($fila['activo']): ?><form method="POST" onsubmit="return confirm('¿Retirar este combo?')"><input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="accion" value="combo_eliminar"><input type="hidden" name="id" value="<?= (int) $fila['id'] ?>"><button class="catalogo-delete">Retirar</button></form><?php endif; ?></td></tr><?php endwhile; ?></tbody></table></div></section>
        </div>
    <?php else: ?>
        <div class="catalogo-grid"><section class="catalogo-panel"><h2>Agregar imagen a galería</h2><form class="catalogo-form" method="POST" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="accion" value="galeria_guardar"><label>Imagen<input type="file" name="imagen_galeria" accept="image/jpeg,image/png,image/webp" required></label><button type="submit">Subir imagen</button></form></section><section class="catalogo-panel"><h2>Galería de inicio</h2><div class="catalogo-table-wrap"><table class="catalogo-table"><thead><tr><th>ID</th><th>Archivo</th><th>Vista previa</th></tr></thead><tbody><?php while ($fila = $galeria->fetch_assoc()): ?><tr><td><?= (int) $fila['id'] ?></td><td><?= htmlspecialchars($fila['nombre']) ?></td><td><img src="../<?= htmlspecialchars($fila['ruta']) ?>" alt="" width="96" height="64" style="object-fit:cover"></td></tr><?php endwhile; ?></tbody></table></div></section></div>
    <?php endif; ?>
</main>
</body>
</html>