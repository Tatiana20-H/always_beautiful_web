<?php
session_start();
require_once __DIR__ . '/conexion.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: inicio.php');
    exit();
}
include_once __DIR__ . '/conexion.php';
$conexion = $GLOBALS['conexion'];
require_once __DIR__ . '/precios_carrito.php';
$puntosDisponibles = puntosDisponiblesUsuario($conexion, (int) $_SESSION['usuario_id']);
$solicitudDescuentoActiva = false;
$consultaDescuentoActivo = mysqli_prepare($conexion, "SELECT id FROM solicitudes_beneficios WHERE usuario_id = ? AND tipo = 'descuento' AND estado IN ('solicitado', 'reservado') LIMIT 1");
if ($consultaDescuentoActivo) {
    $usuarioBeneficioId = (int) $_SESSION['usuario_id'];
    mysqli_stmt_bind_param($consultaDescuentoActivo, 'i', $usuarioBeneficioId);
    if (mysqli_stmt_execute($consultaDescuentoActivo)) {
        $resultadoDescuentoActivo = mysqli_stmt_get_result($consultaDescuentoActivo);
        $solicitudDescuentoActiva = $resultadoDescuentoActivo && mysqli_fetch_assoc($resultadoDescuentoActivo);
    }
    mysqli_stmt_close($consultaDescuentoActivo);
}
$total = 0;
$carritoValido = true;
$usuarioId = (int) $_SESSION['usuario_id'];
$stmtPuntos = $conexion->prepare('SELECT puntos, puntos_resto FROM usuarios WHERE id = ?');
$stmtPuntos->bind_param('i', $usuarioId);
$stmtPuntos->execute();
$saldoPuntos = $stmtPuntos->get_result()->fetch_assoc() ?: ['puntos' => 0, 'puntos_resto' => 0];
$stmtPuntos->close();
$productosObsequio = $conexion->query('SELECT id, nombre, precio FROM productos WHERE activo = 1 AND stock > 0 ORDER BY nombre ASC');
if (empty($_SESSION['csrf_obsequio'])) {
    $_SESSION['csrf_obsequio'] = bin2hex(random_bytes(32));
}
$avisoObsequio = $_SESSION['notificacion_obsequio'] ?? '';
unset($_SESSION['notificacion_obsequio']);
?>

<?php include("header.php"); ?>

<link rel="stylesheet" href="styles.css">

<main class="pagina-carrito">
<h1 class="titulo-carrito">Carrito de compras</h1>

<div class="carrito-container">
<div class="puntos-carrito" aria-label="<?= (int) $puntosDisponibles ?> puntos disponibles">
    <span aria-hidden="true">★</span> <?= (int) $puntosDisponibles ?> puntos
</div>

<?php
if(isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])):

foreach($_SESSION['carrito'] as $nombre => $producto):
    $nombre = (string) $nombre;
    $cantidad = is_array($producto) ? normalizarCantidadCarrito($producto['cantidad'] ?? null) : null;
    $articulo = resolverArticuloCarrito($conexion, $nombre);
    $lineaValida = $cantidad !== null && $articulo['valido'] && validarCantidadDisponible($articulo, $cantidad);
    $lineaConPrecio = $cantidad !== null && $articulo['valido'];
    if (!$lineaValida) {
        $carritoValido = false;
    }
    $cantidadMostrar = $cantidad ?? 0;
    $nombreMostrar = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    $imagen = $articulo['valido'] && $articulo['imagen'] !== '' ? $articulo['imagen'] : 'IMG/default.png';
    $precio = $articulo['valido'] ? $articulo['precio'] : 0;
    $subtotal = $lineaConPrecio ? $precio * $cantidadMostrar : 0;
    if ($lineaConPrecio) {
        $total += $subtotal;
    }
    if ($lineaValida) {
        $_SESSION['carrito'][$nombre]['precio'] = $precio;
        $_SESSION['carrito'][$nombre]['imagen'] = $imagen;
    }
?>

<article class="carrito-linea">

    <img src="<?= htmlspecialchars($imagen, ENT_QUOTES, 'UTF-8') ?>" class="img-carrito" alt="">

    <div class="carrito-info">
        <h3><?= $nombreMostrar ?></h3>

        <div class="cantidad">
            <a href="sumar.php?producto=<?= urlencode($nombre) ?>" class="btn-cantidad" aria-label="Aumentar cantidad de <?= $nombreMostrar ?>">+</a>

            <span><?= $cantidadMostrar ?></span>

            <a href="restar.php?producto=<?= urlencode($nombre) ?>" class="btn-cantidad" aria-label="Reducir cantidad de <?= $nombreMostrar ?>">−</a>
        </div>

        <?php if ($articulo['valido']): ?>
            <p class="precio">$<?= number_format($subtotal, 0, ',', '.') ?></p>
            <?php if (!$lineaValida): ?><p>La cantidad o disponibilidad de este artículo debe revisarse.</p><?php endif; ?>
        <?php else: ?>
            <p class="precio">No disponible</p>
        <?php endif; ?>

        <a href="eliminar.php?producto=<?= urlencode($nombre) ?>" class="eliminar">Quitar del carrito</a>
    </div>

    </article>

<?php 
endforeach; 

else:
?>

<p class="vacio">Tu carrito está vacío</p>

<?php endif; ?>

</div>

<h2 class="total">Total: $<?= number_format($total, 0, ',', '.') ?></h2>

<section class="recompensas-carrito" aria-labelledby="titulo-recompensas">
    <div class="recompensas-encabezado">
        <div><p class="recompensas-etiqueta">BENEFICIOS</p><h2 id="titulo-recompensas">Puntos y obsequios</h2></div>
        <span class="recompensas-saldo"><strong><?= (int) $puntosDisponibles ?></strong> puntos</span>
    </div>
    <?php if ($avisoObsequio !== ''): ?><p role="status"><?= htmlspecialchars($avisoObsequio) ?></p><?php endif; ?>
    <p class="recompensas-regla">Cada $50.000 COP en compras confirmadas suma 1 punto.</p>
    <p class="recompensas-progreso">Te faltan <strong>$<?= number_format(max(0, 50000 - (int) $saldoPuntos['puntos_resto']), 0, ',', '.') ?></strong> para sumar otro punto.</p>
    <?php if ($puntosDisponibles > 0): ?>
        <form class="recompensas-form" action="reclamar_obsequio.php" method="POST">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_obsequio']) ?>">
            <label for="producto-obsequio">Elige un producto disponible
                <select id="producto-obsequio" name="producto_id" required>
                    <option value="">Selecciona producto</option>
                    <?php if ($productosObsequio): while ($productoObsequio = $productosObsequio->fetch_assoc()): ?>
                        <option value="<?= (int) $productoObsequio['id'] ?>"><?= htmlspecialchars($productoObsequio['nombre']) ?> (valor $<?= number_format((int) $productoObsequio['precio'], 0, ',', '.') ?>)</option>
                    <?php endwhile; endif; ?>
                </select>
            </label>
            <button type="submit" class="btn-reclamar-obsequio">Reclamar con 1 punto</button>
        </form>
    <?php else: ?>
        <p>Cuando acumules un punto, podrás elegir aquí un producto disponible como obsequio.</p>
    <?php endif; ?>
</section>

<?php if (!empty($_SESSION['carrito'])): ?>
<section class="pasarela-pago" aria-labelledby="titulo-pago-carrito">
    <div class="pago-encabezado"><div><p class="pago-etiqueta">PAGO SEGURO</p><h2 id="titulo-pago-carrito">Finaliza tu compra</h2></div></div>
    <p>Continúa al checkout seguro para completar el pedido.</p>
    <?php if (empty($_SESSION['csrf_pago'])) $_SESSION['csrf_pago'] = bin2hex(random_bytes(32)); ?>
    <form action="pagar.php" method="POST" id="form-pago">
        <input type="hidden" name="csrf_pago" value="<?= htmlspecialchars($_SESSION['csrf_pago'], ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="btn-pagar" <?= empty($carritoValido) ? 'disabled' : '' ?>>Continuar al pago</button>
    </form>
</section>
<?php endif; ?>

</main>

<?php include __DIR__ . '/footer.php'; ?>