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
    ["Vaseline", 10000, "IMG/Vaseline.jpg", "Vaselina hidratante que ayuda a proteger y suavizar la piel.", 4.8],
    ["Protector solar", 30000, "IMG/Protector solar.jpg", "Protector solar que ayuda a proteger la piel de los rayos del sol.", 4.9],
    ["Mascarilla para los labios", 30000, "IMG/Mascarilla para los labios.jpg", "Mascarilla hidratante para mantener los labios suaves y cuidados.", 4.7],
    ["Mascarilla para la cara", 10000, "IMG/Mascarilla para la cara.jpg", "Mascarilla facial que ayuda a hidratar y mejorar la apariencia de la piel.", 4.8],
    ["Hidratante Aloe", 20000, "IMG/Hidratante para cara.jpg", "Crema hidratante con aloe que ayuda a mantener la piel suave y fresca.", 4.6],
    ["Crema Ponds", 25000, "IMG/Crema Ponds.jpg", "Crema hidratante para el cuidado diario de la piel.", 4.7],
    ["Crema Nivea", 15000, "IMG/Crema Nivea.jpg", "Crema hidratante que ayuda a mantener la piel suave y protegida.", 4.9],
    ["Crema CeraVe", 40000, "IMG/Crema hidratante.jpg", "Crema hidratante para ayudar a mantener la piel hidratada y cuidada.", 4.8],
    ["Agua micelar", 15000, "IMG/Agua micelar.jpg", "Agua micelar para limpiar la piel y retirar impurezas y maquillaje.", 4.7]
];
?>

<?php foreach($productos as $p): ?>

<div class="card">

    <div class="imagen-producto">
        <a href="producto.php?nombre=<?= urlencode($p[0]) ?>&precio=<?= $p[1] ?>&imagen=<?= urlencode($p[2]) ?>&descripcion=<?= urlencode($p[3]) ?>&valoracion=<?= $p[4] ?>&categoria=Piel"><img src="<?= $p[2] ?>" alt="<?= $p[0] ?>"></a>
    </div>

    <div class="informacion-producto">
        <span class="etiqueta-producto">DESTACADO</span>
        <h3><a class="nombre-producto" href="producto.php?nombre=<?= urlencode($p[0]) ?>&precio=<?= $p[1] ?>&imagen=<?= urlencode($p[2]) ?>&descripcion=<?= urlencode($p[3]) ?>&valoracion=<?= $p[4] ?>&categoria=Piel"><?= $p[0] ?></a></h3>
        <a class="enlace-detalle" href="producto.php?nombre=<?= urlencode($p[0]) ?>&precio=<?= $p[1] ?>&imagen=<?= urlencode($p[2]) ?>&descripcion=<?= urlencode($p[3]) ?>&valoracion=<?= $p[4] ?>&categoria=Piel">Ver detalles y reseñas</a>
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