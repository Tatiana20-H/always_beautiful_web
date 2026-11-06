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
$usuarioId = (int) $_SESSION['usuario_id'];
$stmtPuntos = $conexion->prepare('SELECT puntos, puntos_resto FROM usuarios WHERE id = ?');
$stmtPuntos->bind_param('i', $usuarioId);
$stmtPuntos->execute();
$saldoPuntos = $stmtPuntos->get_result()->fetch_assoc() ?: ['puntos' => 0, 'puntos_resto' => 0];
$stmtPuntos->close();
$puntosDisponibles = $conexion->query('SELECT id, nombre, precio FROM productos WHERE activo = 1 AND stock > 0 ORDER BY nombre ASC');
if (empty($_SESSION['csrf_obsequio'])) {
    $_SESSION['csrf_obsequio'] = bin2hex(random_bytes(32));
}
$avisoObsequio = $_SESSION['notificacion_obsequio'] ?? '';
unset($_SESSION['notificacion_obsequio']);
?>

<?php include("header.php"); ?>

<link rel="stylesheet" href="styles.css">

<h2 class="titulo-carrito">Carrito de compras</h2>

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

<div class="item">

    <img src="<?= htmlspecialchars($imagen, ENT_QUOTES, 'UTF-8') ?>" class="img-carrito">

    <div class="info">
        <h3><?= $nombreMostrar ?></h3>

        <div class="cantidad">
            <a href="sumar.php?producto=<?= urlencode($nombre) ?>" class="btn-cantidad">+</a>

            <span><?= $cantidadMostrar ?></span>

            <a href="restar.php?producto=<?= urlencode($nombre) ?>" class="btn-cantidad">-</a>
        </div>

        <?php if ($articulo['valido']): ?>
            <p class="precio">$<?= number_format($subtotal, 0, ',', '.') ?></p>
            <?php if (!$lineaValida): ?><p>La cantidad o disponibilidad de este artículo debe revisarse.</p><?php endif; ?>
        <?php else: ?>
            <p class="precio">No disponible</p>
        <?php endif; ?>

        <a href="eliminar.php?producto=<?= urlencode($nombre) ?>" class="eliminar">Eliminar</a>
    </div>

</div>

<?php 
endforeach; 

else:
?>

<p class="vacio">Tu carrito está vacío</p>

<?php endif; ?>

</div>

<h2 class="total">Total: $<?= number_format($total, 0, ',', '.') ?></h2>

<section class="recompensas-carrito" style="background:#fbf7f3;border:1px solid #d7b8ad;border-radius:10px;margin:24px auto;max-width:900px;padding:22px;">
    <h2 style="color:#481b18;margin-top:0;">Puntos y obsequios</h2>
    <?php if ($avisoObsequio !== ''): ?><p role="status"><?= htmlspecialchars($avisoObsequio) ?></p><?php endif; ?>
    <p>Tienes <strong><?= (int) $saldoPuntos['puntos'] ?></strong> puntos disponibles. Cada $50.000 COP acumulados en compras confirmadas suman 1 punto.</p>
    <p>Te faltan $<?= number_format(max(0, 50000 - (int) $saldoPuntos['puntos_resto']), 0, ',', '.') ?> para tu siguiente punto.</p>
    <?php if ((int) $saldoPuntos['puntos'] > 0): ?>
        <form action="reclamar_obsequio.php" method="POST" style="display:flex;flex-wrap:wrap;gap:10px;align-items:end;">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_obsequio']) ?>">
            <label style="display:grid;gap:6px;flex:1;min-width:220px;">Elige el producto de obsequio
                <select name="producto_id" required style="padding:10px;border:1px solid #cdbfb3;border-radius:6px;">
                    <option value="">Selecciona producto</option>
                    <?php if ($puntosDisponibles): while ($productoObsequio = $puntosDisponibles->fetch_assoc()): ?>
                        <option value="<?= (int) $productoObsequio['id'] ?>"><?= htmlspecialchars($productoObsequio['nombre']) ?> (valor $<?= number_format((int) $productoObsequio['precio'], 0, ',', '.') ?>)</option>
                    <?php endwhile; endif; ?>
                </select>
            </label>
            <button type="submit" class="btn-pagar" style="background:#481b18;border:0;border-radius:6px;color:#fff;cursor:pointer;padding:11px 16px;">Reclamar con 1 punto</button>
        </form>
    <?php else: ?>
        <p>Cuando acumules un punto, podrás elegir aquí un producto disponible como obsequio.</p>
    <?php endif; ?>
</section>

<?php if (!empty($_SESSION['carrito'])): ?>
<div class="pasarela-pago">
    <h2>Pagar con Stripe</h2>
    <p>Completa tu compra en la página segura de Stripe.</p>
    <form action="pagar.php" method="POST" id="form-pago">
        <button type="submit" class="btn-pagar">Continuar con Stripe</button>
    </form>
</section>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>