<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Inicio - Always Beautiful</title>
<link rel="stylesheet" href="styles.css">
</head>

<body>

<?php include("header.php"); ?>
<?php include_once __DIR__ . '/conexion.php'; ?>
<?php $conexion = $GLOBALS['conexion']; ?>
<?php
$sesionesPublicas = [];
$resultadoSesionesPublicas = mysqli_query($conexion, "SELECT s.id, s.orden, s.nombre, s.tipo, s.estilo_slider, s.imagenes, gi.ruta AS imagen_ruta FROM sesiones_inicio s LEFT JOIN sesiones_imagenes si ON si.sesion_id = s.id LEFT JOIN galeria_inicio gi ON gi.id = si.galeria_id WHERE s.activo = 1 ORDER BY s.orden, s.id, si.orden");
if ($resultadoSesionesPublicas) {
    while ($sesionPublica = mysqli_fetch_assoc($resultadoSesionesPublicas)) {
        $ordenSesion = (int) $sesionPublica['orden'];
        if (!isset($sesionesPublicas[$ordenSesion])) {
            $imagenesAntiguas = json_decode($sesionPublica['imagenes'] ?? '', true);
            $sesionesPublicas[$ordenSesion] = [
                'nombre' => $sesionPublica['nombre'],
                'tipo' => $sesionPublica['tipo'],
                'estilo' => (int) $sesionPublica['estilo_slider'],
                'imagenes' => [],
                'imagenes_legacy' => is_array($imagenesAntiguas) ? $imagenesAntiguas : [],
            ];
        }
        if (!empty($sesionPublica['imagen_ruta'])) {
            $sesionesPublicas[$ordenSesion]['imagenes'][] = $sesionPublica['imagen_ruta'];
        }
    }
    foreach ($sesionesPublicas as &$sesionPublica) {
        if (empty($sesionPublica['imagenes'])) {
            $sesionPublica['imagenes'] = $sesionPublica['imagenes_legacy'];
        }
        unset($sesionPublica['imagenes_legacy']);
    }
    unset($sesionPublica);
}
$sesionSliderPublica = $sesionesPublicas[1] ?? ['nombre' => 'Slider principal', 'estilo' => 1, 'imagenes' => []];
$sesionFavoritosPublica = $sesionesPublicas[2] ?? ['nombre' => 'Favoritos de la temporada', 'imagenes' => []];
$sesionOfertasPublica = $sesionesPublicas[3] ?? ['nombre' => 'Ofertas para ti', 'imagenes' => []];
?>

<section class="hero-intro">
    <?php $saludo = ($_SESSION['genero'] ?? '') === 'hombre' ? 'Bienvenido' : 'Bienvenida'; ?>
    <h2><?= $saludo ?> a Always Beautiful</h2>
</section>

<!-- SLIDER -->
<?php
$imagenesSesion = $sesionSliderPublica['imagenes'];
if (empty($imagenesSesion)) {
    $imagenesSesion = ['IMG/Slide1.jpg', 'IMG/Slide2.jpg', 'IMG/Slide3.jpg'];
}
?>
<section class="slider estilo-slider-<?php echo (int) ($sesionSliderPublica['estilo'] ?? 1); ?>" aria-label="<?php echo htmlspecialchars($sesionSliderPublica['nombre']); ?>">
    <div class="slider-pista">
        <?php foreach ($imagenesSesion as $indiceImagen => $imagenSesion): ?>
            <img class="<?php echo $indiceImagen === 1 ? 'slider-centro' : ''; ?>" src="<?php echo htmlspecialchars($imagenSesion); ?>" alt="Productos de belleza Always Beautiful">
        <?php endforeach; ?>
    </div>
</section>

<?php
$descuentos = [
    ["Base Nars", 200000, 20, "IMG/Base.jpg", "Maquillaje"],
    ["Paleta de sombras", 90000, 15, "IMG/Sombras.jpg", "Maquillaje"],
    ["Protector solar", 30000, 10, "IMG/Protector solar.jpg", "Piel"]
];
$imagenesFavoritos = $sesionFavoritosPublica['imagenes'];
foreach ($descuentos as $indiceDescuento => &$descuento) {
    if (!empty($imagenesFavoritos)) {
        $descuento[3] = $imagenesFavoritos[$indiceDescuento % count($imagenesFavoritos)];
    }
}
unset($descuento);
?>

<section class="mejores-descuentos" aria-labelledby="titulo-descuentos">
    <div class="encabezado-descuentos">
        <span class="supertitulo-descuentos"><?php echo htmlspecialchars($sesionFavoritosPublica['nombre']); ?></span>
        <h2 id="titulo-descuentos">Los mejores descuentos</h2>
        <p>Encuentra tus productos favoritos a un precio especial.</p>
    </div>

    <div class="descuentos-grid">
        <?php foreach ($descuentos as $producto):
            $precioAnterior = $producto[1];
            $precioActual = $precioAnterior - ($precioAnterior * $producto[2] / 100);
        ?>
            <article class="descuento-card">
                <div class="descuento-imagen">
                    <span class="insignia-descuento">-<?php echo $producto[2]; ?>%</span>
                    <a href="producto.php?nombre=<?php echo urlencode($producto[0]); ?>&precio=<?php echo $precioActual; ?>&imagen=<?php echo urlencode($producto[3]); ?>&descripcion=<?php echo urlencode('Producto seleccionado de Always Beautiful para complementar tu rutina de belleza.'); ?>&valoracion=4.8&categoria=<?php echo urlencode($producto[4]); ?>"><img src="<?php echo htmlspecialchars($producto[3]); ?>" alt="<?php echo htmlspecialchars($producto[0]); ?>"></a>
                </div>
                <div class="descuento-contenido">
                    <span class="categoria-descuento"><?php echo htmlspecialchars($producto[4]); ?></span>
                    <h3><a class="nombre-producto" href="producto.php?nombre=<?php echo urlencode($producto[0]); ?>&precio=<?php echo $precioActual; ?>&imagen=<?php echo urlencode($producto[3]); ?>&descripcion=<?php echo urlencode('Producto seleccionado de Always Beautiful para complementar tu rutina de belleza.'); ?>&valoracion=4.8&categoria=<?php echo urlencode($producto[4]); ?>"><?php echo htmlspecialchars($producto[0]); ?></a></h3>
                    <a class="enlace-detalle" href="producto.php?nombre=<?php echo urlencode($producto[0]); ?>&precio=<?php echo $precioActual; ?>&imagen=<?php echo urlencode($producto[3]); ?>&descripcion=<?php echo urlencode('Producto seleccionado de Always Beautiful para complementar tu rutina de belleza.'); ?>&valoracion=4.8&categoria=<?php echo urlencode($producto[4]); ?>">Ver detalles y reseñas</a>
                    <p class="precio-anterior">$<?php echo number_format($precioAnterior, 0, ',', '.'); ?></p>
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

