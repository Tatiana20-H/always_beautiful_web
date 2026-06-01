<?php 
session_start();
include("header.php");
?>

<link rel="stylesheet" href="styles.css">

<h2 class="titulo-seccion">Cuidado de piel</h2>

<div class="contenedor-Piel">

<?php
function cantidadProducto($nombre){
    return $_SESSION['carrito'][$nombre]['cantidad'] ?? 0;
}

/* LISTA DE PRODUCTOS (TODO IGUAL Y CORRECTO) */
$productos = [
    ["Vaseline", 10000, "IMG/Vaseline.jpg"],
    ["Protector solar", 30000, "IMG/Protector solar.jpg"],
    ["Mascarilla para los labios", 30000, "IMG/Mascarilla para los labios.jpg"],
    ["Mascarilla para la cara", 10000, "IMG/Mascarilla para la cara.jpg"],
    ["Hidratante Aloe", 20000, "IMG/Hidratante para cara.jpg"],
    ["Crema Ponds", 25000, "IMG/Crema ponds.jpg"],
    ["Crema Nivea", 15000, "IMG/Crema nivea.jpg"],
    ["Crema CeraVe", 40000, "IMG/Crema hidratante.jpg"],
    ["Agua micelar", 15000, "IMG/Agua micelar.jpg"]
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