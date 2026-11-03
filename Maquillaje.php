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
    ["Paleta de sombras", 90000, "IMG/Sombras.jpg", "Paleta de sombras con tonos variados para crear diferentes looks.", 4.8],
    ["Gloss Sheglam", 10000, "IMG/Gloss.jpg", "Gloss de labios con acabado brillante y cómodo.", 4.7],
    ["Base Nars", 200000, "IMG/Base.jpg", "Base de maquillaje con acabado natural y cobertura uniforme.", 4.9],
    ["Brochas", 30000, "IMG/Brochas.jpg", "Set de brochas ideales para aplicar y difuminar el maquillaje.", 4.6],
    ["Encrespador", 5000, "IMG/Encrespador.jpg", "Encrespador para dar forma y definición a las pestañas.", 4.5],
    ["Contorno", 80000, "IMG/Contorno.jpg", "Producto para definir y resaltar las facciones del rostro.", 4.7],
    ["Pestañina", 50000, "IMG/Pestañina.jpg", "Máscara de pestañas para dar volumen y definición.", 4.8],
    ["Lapices para labios", 5000, "IMG/Lapiz-labios.jpg", "Lápiz para definir el contorno de los labios.", 4.6],
    ["Delineador", 12000, "IMG/Delineador.jpg", "Delineador para crear líneas precisas y diferentes estilos.", 4.8]
];

foreach($productos as $p):
?>

<div class="card">

    <div class="imagen-producto">
        <a href="producto.php?nombre=<?= urlencode($p[0]) ?>&precio=<?= $p[1] ?>&imagen=<?= urlencode($p[2]) ?>&descripcion=<?= urlencode($p[3]) ?>&valoracion=<?= $p[4] ?>&categoria=Maquillaje"><img src="<?= $p[2] ?>" alt="<?= $p[0] ?>"></a>
    </div>

    <div class="informacion-producto">
        <span class="etiqueta-producto">DESTACADO</span>
        <h3><a class="nombre-producto" href="producto.php?nombre=<?= urlencode($p[0]) ?>&precio=<?= $p[1] ?>&imagen=<?= urlencode($p[2]) ?>&descripcion=<?= urlencode($p[3]) ?>&valoracion=<?= $p[4] ?>&categoria=Maquillaje"><?= $p[0] ?></a></h3>
        <a class="enlace-detalle" href="producto.php?nombre=<?= urlencode($p[0]) ?>&precio=<?= $p[1] ?>&imagen=<?= urlencode($p[2]) ?>&descripcion=<?= urlencode($p[3]) ?>&valoracion=<?= $p[4] ?>&categoria=Maquillaje">Ver detalles y reseñas</a>
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