<?php
session_start();
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

        <p class="precio">$<?= $precio * $cantidad ?></p>

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

<h2 class="total">Total: $<?= $total ?></h2>

<div class="contenedor-pagar">
    <a href="pagar.php" class="btn-pagar">Pagar</a>
</div>