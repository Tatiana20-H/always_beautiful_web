<?php 
session_start();
include("header.php");
include("conexion.php");
$conexion = $GLOBALS['conexion'];
?>

<link rel="stylesheet" href="styles.css">

<h2 class="titulo-seccion">Cuidado del cabello</h2>

<div class="contenedor-Cabello">

<?php
function cantidadProducto($nombre){
    return $_SESSION['carrito'][$nombre]['cantidad'] ?? 0;
}

$productos = [
    ["Shampo y Condicionador", 40000, "IMG/Shampo y Condicionador.jpg", "Shampoo y acondicionador para limpiar, hidratar y cuidar el cabello.", 4.8, "Cabello"],
    ["Protector térmico", 25000, "IMG/Protector térmico.jpg", "Protector térmico que ayuda a proteger el cabello del calor de la plancha y el secador.", 4.7, "Cabello"],
    ["Mascarilla para cabello", 32000, "IMG/Mascarilla.jpg", "Mascarilla nutritiva para hidratar y mejorar la apariencia del cabello.", 4.9, "Cabello"]
];
$normalizarNombreProducto = static function ($nombre) {
    return function_exists('mb_strtolower') ? mb_strtolower(trim($nombre), 'UTF-8') : strtolower(trim($nombre));
};
$nombresProductos = [];
foreach ($productos as $productoManual) {
    $nombresProductos[$normalizarNombreProducto($productoManual[0])] = true;
}
$resultado = mysqli_query($conexion, "SELECT nombre, precio, imagen, descripcion, categoria FROM productos WHERE categoria = 'Cabello' AND activo = 1 ORDER BY fecha_creacion DESC");
if ($resultado) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $nombreNormalizado = $normalizarNombreProducto($fila['nombre']);
        if ($nombreNormalizado === '' || isset($nombresProductos[$nombreNormalizado])) {
            continue;
        }
        $nombresProductos[$nombreNormalizado] = true;
        $productos[] = [$fila['nombre'], (int) $fila['precio'], $fila['imagen'] ?: 'IMG/default.jpg', $fila['descripcion'] ?: 'Producto para el cuidado del cabello.', 4.8, $fila['categoria'] ?? 'Cabello'];
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