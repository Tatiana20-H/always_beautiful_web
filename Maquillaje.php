<?php 
session_start();
include("header.php");
?>

<link rel="stylesheet" href="styles.css">

<h2 class="titulo-seccion">Maquillaje</h2>

<div class="contenedor-productos">

<?php
function cantidadProducto($nombre){
    return $_SESSION['carrito'][$nombre]['cantidad'] ?? 0;
}
?>

<!-- PRODUCTOS -->

<?php
$productos = [
    ["Paleta de sombras", 90000, "IMG/sombras.jpg"],
    ["Gloss Sheglam", 10000, "IMG/gloss.jpg"],
    ["Base Nars", 200000, "IMG/base.jpg"],
    ["Brochas", 30000, "IMG/brochas.jpg"],
    ["Encrespador", 5000, "IMG/Encrespador.jpg"],
    ["Contorno", 80000, "IMG/Contorno.jpg"],
    ["Pestañina", 50000, "IMG/Pestañina.jpg"],
    ["Lapices para labios", 5000, "IMG/Lapiz-labios.jpg"],
    ["Delineador", 12000, "IMG/Delineador.jpg"]
];

foreach($productos as $p):
?>

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