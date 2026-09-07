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
    ["Shampo y Condicionador", 40000, "IMG/Shampo y Condicionador.jpg", "Shampoo y acondicionador para limpiar, hidratar y cuidar el cabello.", 4.8],
    ["Protector térmico", 25000, "IMG/Protector térmico.jpg", "Protector térmico que ayuda a proteger el cabello del calor de la plancha y el secador.", 4.7],
    ["Mascarilla para cabello", 32000, "IMG/Mascarilla.jpg", "Mascarilla nutritiva para hidratar y mejorar la apariencia del cabello.", 4.9],
    ["Masajeador de cuero cabelludo", 15000, "IMG/Masajeador de cuero cabelludo.jpg", "Masajeador diseñado para estimular y relajar el cuero cabelludo.", 4.6],
    ["Jabón para cabello", 8000, "IMG/Jabon para cabello.jpg", "Producto para la limpieza y cuidado diario del cabello.", 4.5],
    ["Enruladores", 15000, "IMG/Enruladores.jpg", "Enruladores para crear ondas y rizos de diferentes estilos.", 4.7],
    ["Crema skala", 30000, "IMG/Crema skala.jpg", "Crema para hidratar, nutrir y ayudar a mantener el cabello suave.", 4.8],
    ["Aceite para cabello", 25000, "IMG/Aceite para cabello.jpg", "Aceite para aportar brillo, suavidad e hidratación al cabello.", 4.8],
    ["Aceite de coco", 40000, "IMG/Aceite de coco.jpg", "Aceite de coco para nutrir e hidratar el cabello.", 4.9]
];
?>

<?php foreach($productos as $p): ?>

<div class="card">

    <div class="imagen-producto">
        <a href="producto.php?nombre=<?= urlencode($p[0]) ?>&precio=<?= $p[1] ?>&imagen=<?= urlencode($p[2]) ?>&descripcion=<?= urlencode($p[3]) ?>&valoracion=<?= $p[4] ?>&categoria=Cabello"><img src="<?= $p[2] ?>" alt="<?= $p[0] ?>"></a>
    </div>

    <div class="informacion-producto">
        <span class="etiqueta-producto">DESTACADO</span>
        <h3><a class="nombre-producto" href="producto.php?nombre=<?= urlencode($p[0]) ?>&precio=<?= $p[1] ?>&imagen=<?= urlencode($p[2]) ?>&descripcion=<?= urlencode($p[3]) ?>&valoracion=<?= $p[4] ?>&categoria=Cabello"><?= $p[0] ?></a></h3>
        <a class="enlace-detalle" href="producto.php?nombre=<?= urlencode($p[0]) ?>&precio=<?= $p[1] ?>&imagen=<?= urlencode($p[2]) ?>&descripcion=<?= urlencode($p[3]) ?>&valoracion=<?= $p[4] ?>&categoria=Cabello">Ver detalles y reseñas</a>
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