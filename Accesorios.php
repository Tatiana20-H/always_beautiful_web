<?php 
session_start();
include("header.php");
include("conexion.php");
$conexion = $GLOBALS['conexion'];
?>

<link rel="stylesheet" href="styles.css">

<h2 class="titulo-seccion">Accesorios</h2>

<div class="contenedor-Accesorios">

<?php
function cantidadProducto($nombre){
    return $_SESSION['carrito'][$nombre]['cantidad'] ?? 0;
}

$productos = [
    ["Moño", 5000, "IMG/Moño.jpg", "Moño para complementar diferentes peinados y estilos.", 4.7, "Accesorios"],
    ["Pinzas", 6000, "IMG/Pinzas.jpg", "Pinzas prácticas para sujetar y organizar el cabello.", 4.6, "Accesorios"],
    ["Gorro de dormir", 40000, "IMG/Gorro de dormir.jpg", "Gorro de dormir diseñado para proteger y cuidar el cabello durante la noche.", 4.8, "Accesorios"]
];
$normalizarNombreProducto = static function ($nombre) {
    return function_exists('mb_strtolower') ? mb_strtolower(trim($nombre), 'UTF-8') : strtolower(trim($nombre));
};
$nombresProductos = [];
foreach ($productos as $productoManual) {
    $nombresProductos[$normalizarNombreProducto($productoManual[0])] = true;
}
$resultado = mysqli_query($conexion, "SELECT nombre, precio, imagen, descripcion, categoria FROM productos WHERE categoria = 'Accesorios' AND activo = 1 ORDER BY fecha_creacion DESC");
if ($resultado) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $nombreNormalizado = $normalizarNombreProducto($fila['nombre']);
        if ($nombreNormalizado === '' || isset($nombresProductos[$nombreNormalizado])) {
            continue;
        }
        $nombresProductos[$nombreNormalizado] = true;
        $productos[] = [$fila['nombre'], (int) $fila['precio'], $fila['imagen'] ?: 'IMG/default.jpg', $fila['descripcion'] ?: 'Accesorio para complementar tu estilo.', 4.7, $fila['categoria'] ?? 'Accesorios'];
    }
}
?>

<?php foreach($productos as $p): ?>

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