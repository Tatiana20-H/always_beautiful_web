<?php
session_start();
require_once __DIR__ . '/conexion.php';
$conexion = $GLOBALS['conexion'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Inicio - Always Beautiful</title>
<link rel="stylesheet" href="styles.css?v=<?= filemtime(__DIR__ . '/styles.css') ?>">
</head>

<body>

<?php include("header.php"); ?>

<section class="hero-intro">
    <?php
    $generoUsuario = strtolower(trim((string) ($_SESSION['genero'] ?? '')));
    $saludo = match ($generoUsuario) {
        'hombre' => 'Bienvenido',
        'mujer' => 'Bienvenida',
        default => 'Te damos la bienvenida'
    };
    ?>
    <h2><?= $saludo ?> a Always Beautiful</h2>
</section>

<!-- SLIDER -->
<section class="slider" aria-label="Galería de Always Beautiful">
    <div class="slider-pista">
        <img src="IMG/Slide1.jpg" alt="Productos de belleza Always Beautiful">
        <img class="slider-centro" src="IMG/Slide2.jpg" alt="Colección destacada de belleza">
        <img src="IMG/Slide3.jpg" alt="Cuidado y belleza">
    </div>
</section>

<section class="slider-texto" aria-labelledby="titulo-slider-texto">
    <div class="slider-texto-contenido">
        <h2 id="titulo-slider-texto">Tu belleza, tu esencia, tu momento.</h2>
        <p class="slider-texto-descripcion">En Always Beautiful creemos que cada persona tiene una belleza única y merece sentirse especial. Descubre productos que resaltan lo mejor de ti, encuentra tus favoritos y regálate ese pequeño detalle que te recuerda lo hermosa que eres. Porque no necesitas ser perfecta para brillar, solo ser tú misma.</p>
        <p class="slider-texto-cierre">¡Haz de tu belleza algo extraordinario!</p>
    </div>
</section>

<?php
$descuentos = [
    ["Base Nars", 200000, 20, "IMG/Base.jpg", "Maquillaje"],
    ["Paleta de sombras", 90000, 15, "IMG/Sombras.jpg", "Maquillaje"],
    ["Protector solar", 30000, 10, "IMG/Protector solar.jpg", "Piel"]
];
$resultadoPromociones = $conexion->query('SELECT titulo, precio, categoria, imagen FROM promociones WHERE activo = 1 ORDER BY id DESC');
if ($resultadoPromociones) {
    while ($promocion = $resultadoPromociones->fetch_assoc()) {
        $descuentos[] = [$promocion['titulo'], (int) $promocion['precio'], 0, $promocion['imagen'] ?: 'IMG/default.jpg', $promocion['categoria']];
    }
}
$resultadoCombos = $conexion->query('SELECT nombre, precio, imagen, descripcion FROM combos WHERE activo = 1 ORDER BY id DESC');
$combosInicio = $resultadoCombos ? $resultadoCombos->fetch_all(MYSQLI_ASSOC) : [];
?>

<section class="mejores-descuentos" aria-labelledby="titulo-descuentos">
    <div class="encabezado-descuentos">
        <span class="supertitulo-descuentos">Favoritos de la temporada</span>
        <h2 id="titulo-descuentos">Los mejores descuentos</h2>
        <p>Encuentra tus productos favoritos a un precio especial.</p>
    </div>

    <div class="descuentos-grid">
        <?php foreach ($descuentos as $producto):
            $precioAnterior = $producto[1];
            $precioActual = $producto[2] > 0 ? $precioAnterior - ($precioAnterior * $producto[2] / 100) : $precioAnterior;
        ?>
            <article class="descuento-card">
                <div class="descuento-imagen">
                    <?php if ($producto[2] > 0): ?><span class="insignia-descuento">-<?php echo $producto[2]; ?>%</span><?php endif; ?>
                    <a href="producto.php?nombre=<?php echo urlencode($producto[0]); ?>&precio=<?php echo $precioActual; ?>&imagen=<?php echo urlencode($producto[3]); ?>&descripcion=<?php echo urlencode('Producto seleccionado de Always Beautiful para complementar tu rutina de belleza.'); ?>&valoracion=4.8&categoria=<?php echo urlencode($producto[4]); ?>"><img src="<?php echo htmlspecialchars($producto[3]); ?>" alt="<?php echo htmlspecialchars($producto[0]); ?>"></a>
                </div>
                <div class="descuento-contenido">
                    <span class="categoria-descuento"><?php echo htmlspecialchars($producto[4]); ?></span>
                    <h3><a class="nombre-producto" href="producto.php?nombre=<?php echo urlencode($producto[0]); ?>&precio=<?php echo $precioActual; ?>&imagen=<?php echo urlencode($producto[3]); ?>&descripcion=<?php echo urlencode('Producto seleccionado de Always Beautiful para complementar tu rutina de belleza.'); ?>&valoracion=4.8&categoria=<?php echo urlencode($producto[4]); ?>"><?php echo htmlspecialchars($producto[0]); ?></a></h3>
                    <a class="enlace-detalle" href="producto.php?nombre=<?php echo urlencode($producto[0]); ?>&precio=<?php echo $precioActual; ?>&imagen=<?php echo urlencode($producto[3]); ?>&descripcion=<?php echo urlencode('Producto seleccionado de Always Beautiful para complementar tu rutina de belleza.'); ?>&valoracion=4.8&categoria=<?php echo urlencode($producto[4]); ?>">Ver detalles y reseñas</a>
                    <?php if ($producto[2] > 0): ?><p class="precio-anterior">$<?php echo number_format($precioAnterior, 0, ',', '.'); ?></p><?php endif; ?>
                    <p class="precio-descuento">$<?php echo number_format($precioActual, 0, ',', '.'); ?></p>
                    <form method="POST" action="carrito.php">
                        <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($producto[0]); ?>">
                        <input type="hidden" name="precio" value="<?php echo $precioActual; ?>">
                        <input type="hidden" name="imagen" value="<?php echo htmlspecialchars($producto[3]); ?>">
                        <button type="submit">Agregar al carrito</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($combosInicio): ?>
<section class="mejores-descuentos" aria-labelledby="titulo-combos">
    <div class="encabezado-descuentos"><h2 id="titulo-combos">Combos</h2><p>Selecciones especiales de Always Beautiful.</p></div>
    <div class="descuentos-grid">
        <?php foreach ($combosInicio as $combo): ?>
            <article class="descuento-card"><div class="descuento-imagen"><img src="<?= htmlspecialchars($combo['imagen'] ?: 'IMG/default.jpg') ?>" alt="<?= htmlspecialchars($combo['nombre']) ?>"></div><div class="descuento-contenido"><h3><?= htmlspecialchars($combo['nombre']) ?></h3><p><?= htmlspecialchars($combo['descripcion'] ?? '') ?></p><p class="precio-descuento">$<?= number_format((float) $combo['precio'], 0, ',', '.') ?></p></div></article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<script>
const slider = document.querySelector(".slider");
let ultimaPosicion = window.scrollY;

window.addEventListener("scroll", () => {
    const posicionActual = window.scrollY;
    const seDesplazaHaciaAbajo = posicionActual > ultimaPosicion;

    if (seDesplazaHaciaAbajo && posicionActual > 80) {
        slider.classList.add("slider-enfocado");
    } else if (posicionActual < ultimaPosicion) {
        slider.classList.remove("slider-enfocado");
    }

    ultimaPosicion = posicionActual;
}, { passive: true });
</script>

</body>
</html>

