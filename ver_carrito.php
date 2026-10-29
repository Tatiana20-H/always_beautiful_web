<?php
session_start();
if (strtolower((string) ($_SESSION['rol'] ?? '')) === 'admin') {
    header('Location: Administrador/index.php');
    exit();
}
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

<?php if (!empty($_SESSION['carrito'])): ?>
<section class="pasarela-pago" aria-labelledby="titulo-pago">
    <div class="pago-encabezado">
        <div>
            <h2 id="titulo-pago">Pago seguro</h2>
        </div>
    </div>
    <div class="pago-metodo" aria-label="Método de pago seleccionado">
        <span class="pago-icono-tarjeta" aria-hidden="true">▰</span>
        <span><strong>Tarjeta bancaria</strong><small>Procesada por Stripe Checkout</small></span>
        <span class="pago-seleccionado" aria-label="Seleccionado">✓</span>
    </div>
    <div class="pago-total">
        <span>Total a pagar</span>
        <strong>$<?= number_format($total, 0, ',', '.') ?><small> COP</small></strong>
    </div>
    <?php if ($solicitudDescuentoActiva && $total > 50000): ?>
        <p class="canje-puntos-aviso canje-puntos-activo">Tu solicitud de descuento está activa. Se aplicará automáticamente el 30% a este pedido.</p>
    <?php elseif ($solicitudDescuentoActiva): ?>
        <p class="canje-puntos-aviso">Tu solicitud de descuento sigue activa; se aplicará cuando el total supere $50.000 COP.</p>
    <?php elseif ($puntosDisponibles >= 7): ?>
        <p class="canje-puntos-aviso"><a href="beneficios_cliente.php">Tienes <?= (int) $puntosDisponibles ?> puntos. Solicita el descuento del 30% desde Mis beneficios.</a></p>
    <?php endif; ?>
    <form action="pagar.php" method="POST" id="form-pago">
        <button type="submit" class="btn-pagar" <?= $carritoValido ? '' : 'disabled' ?>>Realizar pago</button>
    </form>
</section>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>