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
    ["Moño", 5000, "IMG/Moño.jpg", "Moño para complementar diferentes peinados y estilos.", 4.7],
    ["Pinzas", 6000, "IMG/Pinzas.jpg", "Pinzas prácticas para sujetar y organizar el cabello.", 4.6],
    ["Gorro de dormir", 40000, "IMG/Gorro de dormir.jpg", "Gorro de dormir diseñado para proteger y cuidar el cabello durante la noche.", 4.8],
    ["Aretes", 5000, "IMG/Aretes.jpg", "Aretes ideales para complementar diferentes estilos y looks.", 4.7],
    ["Caiman de cabello", 15000, "IMG/Caiman de cabello.jpg", "Caimán para sujetar el cabello de forma práctica y cómoda.", 4.6],
    ["Pestañas postizas", 12000, "IMG/Pestañas postizas.jpg", "Pestañas postizas para resaltar y darle mayor definición a la mirada.", 4.8],
    ["Uñas postizas", 10000, "IMG/Uñas postizas.jpg", "Uñas postizas para complementar diferentes estilos de manicura.", 4.7],
    ["Collar", 30000, "IMG/Collar.jpg", "Collar decorativo para complementar diferentes outfits.", 4.8],
    ["Antifaz para dormir", 20000, "IMG/Antifaz.jpg", "Antifaz cómodo para ayudar a bloquear la luz durante el descanso.", 4.7]
];
?>

<?php foreach($productos as $p): ?>

<div class="card">

    <div class="imagen-producto">
        <a href="producto.php?nombre=<?= urlencode($p[0]) ?>&precio=<?= $p[1] ?>&imagen=<?= urlencode($p[2]) ?>&descripcion=<?= urlencode($p[3]) ?>&valoracion=<?= $p[4] ?>&categoria=Accesorios"><img src="<?= $p[2] ?>" alt="<?= $p[0] ?>"></a>
    </div>

    <div class="informacion-producto">
        <span class="etiqueta-producto">DESTACADO</span>
        <h3><a class="nombre-producto" href="producto.php?nombre=<?= urlencode($p[0]) ?>&precio=<?= $p[1] ?>&imagen=<?= urlencode($p[2]) ?>&descripcion=<?= urlencode($p[3]) ?>&valoracion=<?= $p[4] ?>&categoria=Accesorios"><?= $p[0] ?></a></h3>
        <a class="enlace-detalle" href="producto.php?nombre=<?= urlencode($p[0]) ?>&precio=<?= $p[1] ?>&imagen=<?= urlencode($p[2]) ?>&descripcion=<?= urlencode($p[3]) ?>&valoracion=<?= $p[4] ?>&categoria=Accesorios">Ver detalles y reseñas</a>
        <p class="descripcion-producto"><?= $p[3] ?></p>
        <p class="precio">$<?= number_format($p[1], 0, ',', '.') ?></p>
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <span class="contador"><?= cantidadProducto($p[0]) ?></span>
        <?php endif; ?>

        <form method="POST" action="carrito.php">
            <input type="hidden" name="nombre" value="<?= $p[0] ?>">
            <input type="hidden" name="precio" value="<?= $p[1] ?>">
            <input type="hidden" name="imagen" value="<?= $p[2] ?>">
            <button type="submit">Agregar al carrito</button>
        </form>
    </div>

</div>

<?php endforeach; ?>

</div>

</body>
</html>