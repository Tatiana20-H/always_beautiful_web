<?php 
session_start();
include("header.php");
?>

<link rel="stylesheet" href="styles.css">

<h2 class="titulo-seccion">Cuidado del cabello</h2>

<div class="contenedor-Cabello">

<?php
function cantidadProducto($nombre){
    return $_SESSION['carrito'][$nombre]['cantidad'] ?? 0;
}

/* LISTA DE PRODUCTOS */
$productos = [
    ["Shampo y Condicionador", 40000, "IMG/Shampo y Condicionador.jpg"],
    ["Protector térmico", 25000, "IMG/Protector térmico.jpg"],
    ["Mascarilla para cabello", 32000, "IMG/Mascarilla.jpg"],
    ["Masajeador de cuero cabelludo", 15000, "IMG/Masajeador de cuero cabelludo.jpg"],
    ["Jabón para cabello", 8000, "IMG/Jabon para cabello.jpg"],
    ["Enruladores", 15000, "IMG/Enruladores.jpg"],
    ["Crema skala", 30000, "IMG/Crema skala.jpg"],
    ["Aceite para cabello", 25000, "IMG/Aceite para cabello.jpg"],
    ["Aceite de coco", 40000, "IMG/Aceite de coco.jpg"]
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