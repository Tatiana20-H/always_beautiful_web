<?php 
session_start();
include("header.php");
include("conexion.php");
$conexion = $GLOBALS['conexion'];
?>

<link rel="stylesheet" href="styles.css">

<h2 class="titulo-seccion">Maquillaje</h2>

<div class="contenedor-productos">

<?php
function cantidadProducto($nombre){
    return $_SESSION['carrito'][$nombre]['cantidad'] ?? 0;
}

$productos = [
    ["Paleta de sombras", 90000, "IMG/Sombras.jpg", "Paleta de sombras con tonos variados para crear diferentes looks.", 4.8, "Maquillaje"],
    ["Gloss Sheglam", 10000, "IMG/Gloss.jpg", "Gloss de labios con acabado brillante y cómodo.", 4.7, "Maquillaje"],
    ["Base Nars", 200000, "IMG/Base.jpg", "Base de maquillaje con acabado natural y cobertura uniforme.", 4.9, "Maquillaje"]
];
$normalizarNombreProducto = static function ($nombre) {
    return function_exists('mb_strtolower') ? mb_strtolower(trim($nombre), 'UTF-8') : strtolower(trim($nombre));
};
$nombresProductos = [];
foreach ($productos as $productoManual) {
    $nombresProductos[$normalizarNombreProducto($productoManual[0])] = true;
}
$resultado = mysqli_query($conexion, "SELECT nombre, precio, imagen, descripcion, categoria FROM productos WHERE categoria = 'Maquillaje' AND activo = 1 ORDER BY fecha_creacion DESC");
if ($resultado) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $nombreNormalizado = $normalizarNombreProducto($fila['nombre']);
        if ($nombreNormalizado === '' || isset($nombresProductos[$nombreNormalizado])) {
            continue;
        }
        $nombresProductos[$nombreNormalizado] = true;
        $productos[] = [$fila['nombre'], (int) $fila['precio'], $fila['imagen'] ?: 'IMG/default.jpg', $fila['descripcion'] ?: 'Producto de maquillaje.', 4.8, $fila['categoria'] ?? 'Maquillaje'];
    }
}

foreach($productos as $p):
?>

<div class="card">

    <div class="imagen-producto">
        <a href="producto.php?nombre=<?= urlencode($p[0]) ?>&precio=<?= (int) $p[1] ?>&imagen=<?= urlencode($p[2]) ?>&descripcion=<?= urlencode($p[3]) ?>&valoracion=<?= (float) $p[4] ?>&categoria=<?= urlencode($p[5]) ?>"><img src="<?= htmlspecialchars($p[2], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($p[0], ENT_QUOTES, 'UTF-8') ?>"></a>
    </div>

    <div class="informacion-producto">
        <span class="etiqueta-producto">DESTACADO</span>
        <h3><a class="nombre-producto" href="producto.php?nombre=<?= urlencode($p[0]) ?>&precio=<?= (int) $p[1] ?>&imagen=<?= urlencode($p[2]) ?>&descripcion=<?= urlencode($p[3]) ?>&valoracion=<?= (float) $p[4] ?>&categoria=<?= urlencode($p[5]) ?>"><?= htmlspecialchars($p[0], ENT_QUOTES, 'UTF-8') ?></a></h3>
        <div class="valoracion-producto">
            <span class="estrellas-cards" aria-label="Sin reseñas aún">☆ ☆ ☆ ☆ ☆</span>
        </div>
        <p class="descripcion-producto"><?= htmlspecialchars($p[3], ENT_QUOTES, 'UTF-8') ?></p>
        <p class="precio">$<?= number_format((int) $p[1], 0, ',', '.') ?></p>
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <span class="contador"><?= cantidadProducto($p[0]) ?></span>
        <?php endif; ?>

        <form method="POST" action="carrito.php">
            <input type="hidden" name="nombre" value="<?= htmlspecialchars($p[0], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="precio" value="<?= (int) $p[1] ?>">
            <input type="hidden" name="imagen" value="<?= htmlspecialchars($p[2], ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Agregar al carrito</button>
        </form>
        <a class="enlace-detalle enlace-detalle-producto" href="producto.php?nombre=<?= urlencode($p[0]) ?>&precio=<?= (int) $p[1] ?>&imagen=<?= urlencode($p[2]) ?>&descripcion=<?= urlencode($p[3]) ?>&valoracion=<?= (float) $p[4] ?>&categoria=<?= urlencode($p[5]) ?>">Ver detalles y reseñas</a>
    </div>

</div>

<?php endforeach; ?>

</div>

</body>
</html>