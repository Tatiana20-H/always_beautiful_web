<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: inicio.php');
    exit();
}
$total = 0;
?>

<?php include("header.php"); ?>

<link rel="stylesheet" href="styles.css">

<h2 class="titulo-carrito">Carrito de compras</h2>

<div class="carrito-container">

<?php
if(isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])):

foreach($_SESSION['carrito'] as $nombre => $producto):

    $cantidad = $producto['cantidad'] ?? 1;
    $precio = $producto['precio'] ?? 0;
    $imagen = $producto['imagen'] ?? "IMG/default.png";
?>

<div class="item">

    <img src="<?= $imagen ?>" class="img-carrito">

    <div class="info">
        <h3><?= $nombre ?></h3>

        <div class="cantidad">
            <a href="sumar.php?producto=<?= urlencode($nombre) ?>" class="btn-cantidad">➕</a>

            <span><?= $cantidad ?></span>

            <a href="restar.php?producto=<?= urlencode($nombre) ?>" class="btn-cantidad">➖</a>
        </div>

        <p class="precio">$<?= number_format($precio * $cantidad, 0, ',', '.') ?></p>

        <a href="eliminar.php?producto=<?= urlencode($nombre) ?>" class="eliminar">Eliminar</a>
    </div>

</div>

<?php 
$total += $precio * $cantidad;
endforeach; 

else:
?>

<p class="vacio">Tu carrito está vacío 🛒</p>

<?php endif; ?>

</div>

<h2 class="total">Total: $<?= number_format($total, 0, ',', '.') ?></h2>

<?php if (!empty($_SESSION['carrito'])): ?>
<div class="pasarela-pago">
    <h2>Pagar con Wompi</h2>
    <p>En el siguiente paso podrás elegir Nequi o Bancolombia.</p>
    <form action="pagar.php" method="POST" id="form-pago">
        <button type="submit" class="btn-pagar">Continuar a Nequi o Bancolombia</button>
    </form>
</div>
<?php endif; ?>