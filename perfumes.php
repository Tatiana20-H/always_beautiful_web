<?php
session_start();
include __DIR__ . '/header.php';
include __DIR__ . '/conexion.php';
$conexion = $GLOBALS['conexion'];
$productos = mysqli_query($conexion, "SELECT nombre, precio, imagen, descripcion, categoria, stock FROM productos WHERE categoria = 'Perfumes' AND activo = 1 ORDER BY fecha_creacion DESC");
?>

<link rel="stylesheet" href="styles.css">

<h2 class="titulo-seccion">Perfumes</h2>

<div class="contenedor-Piel">
    <?php if ($productos && mysqli_num_rows($productos) > 0): ?>
        <?php while ($producto = mysqli_fetch_assoc($productos)): ?>
            <div class="card">
                <div class="imagen-producto">
                    <?php if ((int) $producto['stock'] <= 0): ?>
    <span class="etiqueta-agotado">AGOTADO</span>
<?php endif; ?>
                    <a href="producto.php?nombre=<?= urlencode($producto['nombre']) ?>&precio=<?= (int) $producto['precio'] ?>&imagen=<?= urlencode($producto['imagen'] ?: 'IMG/default.jpg') ?>&descripcion=<?= urlencode($producto['descripcion'] ?? '') ?>&categoria=<?= urlencode($producto['categoria']) ?>">
                        <img src="<?= htmlspecialchars($producto['imagen'] ?: 'IMG/default.jpg', ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                    </a>
                </div>
                <div class="informacion-producto">
                    <span class="etiqueta-producto">PERFUME</span>
                    <h3><a class="nombre-producto" href="producto.php?nombre=<?= urlencode($producto['nombre']) ?>&precio=<?= (int) $producto['precio'] ?>&imagen=<?= urlencode($producto['imagen'] ?: 'IMG/default.jpg') ?>&descripcion=<?= urlencode($producto['descripcion'] ?? '') ?>&categoria=<?= urlencode($producto['categoria']) ?>"><?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?></a></h3>
                    <div class="valoracion-producto"><span class="estrellas-cards" aria-label="Sin reseñas aún">☆ ☆ ☆ ☆ ☆</span></div>
                    <p class="descripcion-producto"><?= htmlspecialchars($producto['descripcion'] ?: 'Perfume para complementar tu rutina de belleza.', ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="precio">$<?= number_format((int) $producto['precio'], 0, ',', '.') ?></p>
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <span class="contador"><?= (int) ($_SESSION['carrito'][$producto['nombre']]['cantidad'] ?? 0) ?></span>
                    <?php endif; ?>
                    <form method="POST" action="carrito.php">
                        <input type="hidden" name="nombre" value="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="precio" value="<?= (int) $producto['precio'] ?>">
                        <input type="hidden" name="imagen" value="<?= htmlspecialchars($producto['imagen'] ?: 'IMG/default.jpg', ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ((int) $producto['stock'] > 0): ?>
    <button type="submit">Agregar al carrito</button>
<?php else: ?>
    <button type="button" disabled>Producto agotado</button>
<?php endif; ?>
                    </form>
                    <a class="enlace-detalle enlace-detalle-producto" href="producto.php?nombre=<?= urlencode($producto['nombre']) ?>&precio=<?= (int) $producto['precio'] ?>&imagen=<?= urlencode($producto['imagen'] ?: 'IMG/default.jpg') ?>&descripcion=<?= urlencode($producto['descripcion'] ?? '') ?>&categoria=<?= urlencode($producto['categoria']) ?>">Ver detalles y reseñas</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="mensaje-vacio">Próximamente encontrarás aquí nuestros perfumes.</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
