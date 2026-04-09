<?php 
session_start();
include("header.php");
?>

<link rel="stylesheet" href="styles.css">

<h2 class="titulo-seccion">Accesorios</h2>

<div class="contenedor-Accesorios">

<?php
function cantidadProducto($nombre){
    return $_SESSION['carrito'][$nombre]['cantidad'] ?? 0;
}

/* LISTA DE PRODUCTOS */
$productos = [
    ["Moño", 5000, "IMG/Moño.jpg"],
    ["Pinzas", 6000, "IMG/pinzas.jpg"],
    ["Gorro de dormir", 40000, "IMG/Gorro de dormir.jpg"],
    ["Aretes", 5000, "IMG/Aretes.jpg"],
    ["Caiman de cabello", 15000, "IMG/Caiman de cabello.jpg"],
    ["Pestañas postizas", 12000, "IMG/Pestañas postizas.jpg"],
    ["Uñas postizas", 10000, "IMG/Uñas postizas.jpg"],
    ["Collar", 30000, "IMG/Collar.jpg"],
    ["Antifaz para dormir", 20000, "IMG/Antifaz.jpg"]
];
?>

<?php foreach($productos as $p): ?>

<div class="card">

    <img src="<?= $p[2] ?>">
    <h3><?= $p[0] ?></h3>
    <p class="precio">$<?= number_format($p[1], 0, ',', '.') ?></p>

    <span class="contador"><?= cantidadProducto($p[0]) ?></span>

    <form method="POST" action="carrito.php">
        <input type="hidden" name="nombre" value="<?= $p[0] ?>">
        <input type="hidden" name="precio" value="<?= $p[1] ?>">
        <input type="hidden" name="imagen" value="<?= $p[2] ?>">
        <button type="submit">Agregar al carrito</button>
    </form>

</div>

<?php endforeach; ?>

</div>

</body>
</html>