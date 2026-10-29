<?php
include_once __DIR__ . '/Seguridad.php';
include_once __DIR__ . '/../conexion.php';
$conexion = $GLOBALS['conexion'];

if (empty($_SESSION['csrf_producto_destacado'])) {
    $_SESSION['csrf_producto_destacado'] = bin2hex(random_bytes(32));
}

$mensaje = '';
$tipoMensaje = 'exito';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_producto_destacado'], $token)) {
        http_response_code(403);
        exit('La solicitud expiró. Recarga la página e inténtalo de nuevo.');
    }

    $accion = $_POST['accion'] ?? '';
    if ($accion === 'guardar') {
        $productoIdRecibido = $_POST['producto_id'] ?? null;
        $productoId = is_scalar($productoIdRecibido) ? filter_var($productoIdRecibido, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
        $descripcionRecibida = $_POST['descripcion'] ?? '';
        $descripcion = is_string($descripcionRecibida) ? trim($descripcionRecibida) : '';
        $longitudDescripcion = preg_match_all('/./us', $descripcion, $caracteresDescripcion);
        $activo = isset($_POST['activo']) ? 1 : 0;
        if ($productoId === false || $productoId === null || $descripcion === '' || $longitudDescripcion === false || $longitudDescripcion > 1000) {
            $mensaje = 'Selecciona un producto y escribe una descripción de hasta 1000 caracteres.';
            $tipoMensaje = 'error';
        } else {
            $validarProducto = mysqli_prepare($conexion, 'SELECT id FROM productos WHERE id = ? AND activo = 1 LIMIT 1');
            if ($validarProducto) {
                mysqli_stmt_bind_param($validarProducto, 'i', $productoId);
                mysqli_stmt_execute($validarProducto);
                $resultadoProducto = mysqli_stmt_get_result($validarProducto);
                $productoValido = $resultadoProducto && mysqli_fetch_assoc($resultadoProducto);
                mysqli_stmt_close($validarProducto);
            } else {
                $productoValido = false;
            }

            if (!$productoValido) {
                $mensaje = 'El producto seleccionado no existe o no está activo en el catálogo.';
                $tipoMensaje = 'error';
            } else {
                $guardar = mysqli_prepare($conexion, 'INSERT INTO producto_destacado (id, producto_id, descripcion, activo) VALUES (1, ?, ?, ?) ON DUPLICATE KEY UPDATE producto_id = VALUES(producto_id), descripcion = VALUES(descripcion), activo = VALUES(activo)');
                if ($guardar) {
                    mysqli_stmt_bind_param($guardar, 'isi', $productoId, $descripcion, $activo);
                    $guardado = mysqli_stmt_execute($guardar);
                    mysqli_stmt_close($guardar);
                } else {
                    $guardado = false;
                }
                $mensaje = $guardado ? 'Los cambios del producto destacado se guardaron.' : 'No se pudieron guardar los cambios.';
                $tipoMensaje = $guardado ? 'exito' : 'error';
            }
        }
    } elseif ($accion === 'alternar') {
        $alternar = mysqli_prepare($conexion, 'UPDATE producto_destacado SET activo = IF(activo = 1, 0, 1) WHERE id = 1 AND producto_id IS NOT NULL');
        $alternado = $alternar && mysqli_stmt_execute($alternar) && mysqli_stmt_affected_rows($alternar) > 0;
        if ($alternar) {
            mysqli_stmt_close($alternar);
        }
        $mensaje = $alternado ? 'El estado de la sección se actualizó.' : 'Guarda primero un producto destacado para cambiar el estado.';
        $tipoMensaje = $alternado ? 'exito' : 'error';
    } elseif ($accion === 'quitar') {
        $quitar = mysqli_prepare($conexion, 'UPDATE producto_destacado SET producto_id = NULL, descripcion = \'\', activo = 0 WHERE id = 1');
        $quitado = $quitar && mysqli_stmt_execute($quitar);
        if ($quitar) {
            mysqli_stmt_close($quitar);
        }
        $mensaje = $quitado ? 'Se quitó el producto destacado.' : 'No se pudo quitar el producto destacado.';
        $tipoMensaje = $quitado ? 'exito' : 'error';
    }
}

$productos = [];
$resultadoProductos = mysqli_query($conexion, 'SELECT id, nombre, precio, categoria, imagen FROM productos WHERE activo = 1 ORDER BY nombre');
if ($resultadoProductos) {
    while ($producto = mysqli_fetch_assoc($resultadoProductos)) {
        $rutaImagenProducto = trim((string) ($producto['imagen'] ?? ''));
        if ($rutaImagenProducto === '' || strpos($rutaImagenProducto, 'IMG/') !== 0 || strpos($rutaImagenProducto, '..') !== false || !is_file(__DIR__ . '/../' . $rutaImagenProducto)) {
            $producto['imagen'] = '';
        }
        $productos[] = $producto;
    }
}

$configuracion = null;
$consultaConfiguracion = mysqli_prepare($conexion, 'SELECT pd.producto_id, pd.descripcion, pd.activo, p.nombre, p.precio, p.categoria, p.imagen FROM producto_destacado pd LEFT JOIN productos p ON p.id = pd.producto_id WHERE pd.id = 1 LIMIT 1');
if ($consultaConfiguracion) {
    mysqli_stmt_execute($consultaConfiguracion);
    $resultadoConfiguracion = mysqli_stmt_get_result($consultaConfiguracion);
    $configuracion = $resultadoConfiguracion ? mysqli_fetch_assoc($resultadoConfiguracion) : null;
    mysqli_stmt_close($consultaConfiguracion);
}

$productoSeleccionado = (string) ($configuracion['producto_id'] ?? ($productos[0]['id'] ?? ''));
$descripcionSeleccionada = (string) ($configuracion['descripcion'] ?? '');
$imagenSeleccionada = trim((string) ($configuracion['imagen'] ?? ''));
$imagenDisponible = $imagenSeleccionada !== ''
    && strpos($imagenSeleccionada, 'IMG/') === 0
    && strpos($imagenSeleccionada, '..') === false
    && is_file(__DIR__ . '/../' . $imagenSeleccionada);
include __DIR__ . '/header-admin.php';
?>

<style>
    .destacado-admin { color: #403834; margin: 30px auto 60px; max-width: 980px; padding: 0 20px; }
    .destacado-admin h1 { color: #481b18; font-family: Georgia, serif; margin-bottom: 8px; }
    .destacado-admin > p { color: #75665d; margin-top: 0; }
    .destacado-form { background: #fffaf5; border: 1px solid #cdbfb3; border-radius: 12px; display: grid; gap: 18px; grid-template-columns: minmax(0, 1fr) minmax(240px, .8fr); padding: 24px; }
    .destacado-campos { display: grid; gap: 8px; align-content: start; }
    .destacado-campos label { color: #481b18; font-weight: 700; margin-top: 6px; }
    .destacado-campos select, .destacado-campos textarea { box-sizing: border-box; margin: 0; width: 100%; }
    .destacado-campos textarea { min-height: 150px; padding: 10px; resize: vertical; }
    .destacado-preview { align-self: start; background: #f8f3ed; border: 1px solid #eadbd0; border-radius: 8px; min-width: 0; padding: 16px; }
    .destacado-preview img { aspect-ratio: 4 / 3; background: #fff; display: block; object-fit: contain; width: 100%; }
    .destacado-preview h2 { color: #481b18; font: 700 1.2rem Georgia, serif; margin: 14px 0 6px; overflow-wrap: anywhere; }
    .destacado-preview p { color: #75665d; line-height: 1.5; margin: 8px 0; overflow-wrap: anywhere; }
    .destacado-preview .destacado-precio { color: #743629; font-weight: 700; }
    .destacado-estado { align-items: center; display: flex; gap: 10px; margin: 8px 0; }
    .destacado-estado input { accent-color: #a97878; height: 18px; margin: 0; width: 18px; }
    .destacado-acciones { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
    .destacado-acciones button { border-radius: 6px; max-width: 230px; }
    .destacado-acciones .boton-secundario { background: #75665d; }
    .destacado-notificacion { border-left: 4px solid #65a77a; margin: 0 0 16px; padding: 12px 15px; }
    .destacado-notificacion.error { border-color: #b35b7a; }
    @media (max-width: 650px) { .destacado-form { grid-template-columns: 1fr; padding: 16px; } }
</style>

<main class="destacado-admin">
    <h1>Producto destacado</h1>
    <p>Selecciona un producto del catálogo y configura cómo aparecerá en Inicio.</p>
    <?php if ($mensaje): ?>
        <p class="destacado-notificacion <?php echo $tipoMensaje === 'error' ? 'error' : ''; ?>" role="status"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <form class="destacado-form" method="POST">
        <div class="destacado-campos">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_producto_destacado'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="accion" value="guardar">
            <label for="producto_id">Producto del catálogo</label>
            <select id="producto_id" name="producto_id" required>
                <option value="">Selecciona un producto</option>
                <?php foreach ($productos as $producto): ?>
                    <option value="<?php echo (int) $producto['id']; ?>" data-nombre="<?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>" data-precio="<?php echo (int) $producto['precio']; ?>" data-categoria="<?php echo htmlspecialchars($producto['categoria'], ENT_QUOTES, 'UTF-8'); ?>" data-imagen="<?php echo htmlspecialchars($producto['imagen'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) $producto['id'] === $productoSeleccionado ? 'selected' : ''; ?>><?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?> - $<?php echo number_format((int) $producto['precio'], 0, ',', '.'); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!$productos): ?><p>No hay productos activos disponibles en el catálogo.</p><?php endif; ?>
            <label for="descripcion">Descripción personalizada para Inicio</label>
            <textarea id="descripcion" name="descripcion" maxlength="1000" required><?php echo htmlspecialchars($descripcionSeleccionada, ENT_QUOTES, 'UTF-8'); ?></textarea>
            <label class="destacado-estado"><input type="checkbox" name="activo" value="1" <?php echo !empty($configuracion['activo']) ? 'checked' : ''; ?>> Mostrar en Inicio</label>
            <div class="destacado-acciones">
                <button type="submit" <?php echo !$productos ? 'disabled' : ''; ?>>Guardar cambios</button>
            </div>
        </div>
        <aside class="destacado-preview" aria-live="polite">
            <?php if ($imagenDisponible): ?>
                <img id="vista-imagen" src="../<?php echo htmlspecialchars($imagenSeleccionada, ENT_QUOTES, 'UTF-8'); ?>" alt="Vista previa del producto">
            <?php else: ?>
                <img id="vista-imagen" src="" alt="Vista previa del producto" hidden>
            <?php endif; ?>
            <p id="aviso-imagen" <?php echo $imagenDisponible ? 'hidden' : ''; ?>>La imagen seleccionada no está disponible.</p>
            <h2 id="vista-nombre"><?php echo htmlspecialchars($configuracion['nombre'] ?? 'Selecciona un producto', ENT_QUOTES, 'UTF-8'); ?></h2>
            <p id="vista-categoria"><?php echo htmlspecialchars($configuracion['categoria'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="destacado-precio" id="vista-precio"><?php echo isset($configuracion['precio']) ? '$' . number_format((int) $configuracion['precio'], 0, ',', '.') : ''; ?></p>
            <p id="vista-descripcion"><?php echo htmlspecialchars($descripcionSeleccionada, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php if (!$imagenDisponible && !empty($configuracion['producto_id'])): ?><p>La imagen no está disponible en la ruta guardada en el catálogo.</p><?php endif; ?>
        </aside>
    </form>
    <div class="destacado-acciones" style="margin-top: 16px;">
        <?php if (!empty($configuracion['producto_id'])): ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_producto_destacado'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="accion" value="alternar">
                <button class="boton-secundario" type="submit"><?php echo !empty($configuracion['activo']) ? 'Desactivar sección' : 'Activar sección'; ?></button>
            </form>
            <form method="POST" onsubmit="return confirm('¿Quitar el producto destacado de Inicio?');">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_producto_destacado'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="accion" value="quitar">
                <button class="boton-secundario" type="submit">Quitar producto destacado</button>
            </form>
        <?php endif; ?>
    </div>
</main>

<script>
const selectorDestacado = document.querySelector('#producto_id');
const imagenDestacada = document.querySelector('#vista-imagen');
const avisoImagenDestacada = document.querySelector('#aviso-imagen');
const nombreDestacado = document.querySelector('#vista-nombre');
const categoriaDestacada = document.querySelector('#vista-categoria');
const precioDestacado = document.querySelector('#vista-precio');
const descripcionDestacada = document.querySelector('#descripcion');

function actualizarVistaPrevia() {
    const opcion = selectorDestacado.selectedOptions[0];
    if (!opcion || !opcion.value) {
        nombreDestacado.textContent = 'Selecciona un producto';
        categoriaDestacada.textContent = '';
        precioDestacado.textContent = '';
        imagenDestacada.removeAttribute('src');
        imagenDestacada.hidden = true;
        avisoImagenDestacada.hidden = true;
        return;
    }
    nombreDestacado.textContent = opcion.dataset.nombre || '';
    categoriaDestacada.textContent = opcion.dataset.categoria || '';
    precioDestacado.textContent = '$' + Number(opcion.dataset.precio || 0).toLocaleString('es-CO');
    if (opcion.dataset.imagen) {
        imagenDestacada.src = '../' + opcion.dataset.imagen;
        imagenDestacada.hidden = false;
        avisoImagenDestacada.hidden = true;
    } else {
        imagenDestacada.removeAttribute('src');
        imagenDestacada.hidden = true;
        avisoImagenDestacada.hidden = false;
    }
}

imagenDestacada.addEventListener('error', function () {
    imagenDestacada.hidden = true;
    avisoImagenDestacada.hidden = false;
});
selectorDestacado.addEventListener('change', actualizarVistaPrevia);
descripcionDestacada.addEventListener('input', function () {
    document.querySelector('#vista-descripcion').textContent = this.value;
});
actualizarVistaPrevia();
</script>
