<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Inicio - Always Beautiful</title>
<link rel="stylesheet" href="styles.css">
<style>
    .producto-destacado-inicio { align-items: center; background: rgba(255, 248, 242, .78); border: 0; border-radius: 0; box-sizing: border-box; column-gap: clamp(24px, 4vw, 48px); display: grid; grid-template-columns: minmax(0, .35fr) minmax(0, .65fr); margin: 18px 0 8px; max-width: none; overflow: hidden; padding: 12px max(24px, calc((100% - 1180px) / 2)); row-gap: 0; width: 100%; }
    .producto-destacado-titulo, .encabezado-descuentos h2 { color: #481b18; font: 700 clamp(1.6rem, 2.2vw, 2rem) Georgia, "Times New Roman", serif; line-height: 1.2; margin: 0; text-align: center; text-transform: none; }
    .producto-destacado-titulo { font-size: 1.8rem; grid-column: 1 / -1; text-shadow: 0 1px 0 rgba(255, 255, 255, .7); }
    .decoracion-titulo { align-items: center; color: #a97878; display: flex; gap: 11px; justify-content: center; margin: 8px auto 6px; width: 100%; }
    .decoracion-titulo::before, .decoracion-titulo::after { border-top: 1px solid currentColor; content: ""; flex: 0 1 62px; }
    .decoracion-titulo span { font-size: 1.15rem; line-height: 1; }
    .producto-destacado-inicio > .decoracion-titulo { grid-column: 1 / -1; }
    .producto-destacado-foto { align-items: center; background: transparent; border-radius: 10px; display: flex; justify-content: center; min-height: 0; min-width: 0; }
    .producto-destacado-foto img { display: block; height: auto; max-height: 230px; max-width: 100%; mix-blend-mode: multiply; object-fit: contain; width: 70%; }
    .producto-destacado-texto { padding: 4px; }
    .producto-destacado-texto .supertitulo-descuentos { color: #a97878; }
    .producto-destacado-texto h2 { color: #481b18; font: 700 1.55rem Georgia, serif; line-height: 1.1; margin: 3px 0 5px; }
    .producto-destacado-texto p { color: #75665d; line-height: 1.6; }
    .producto-destacado-descripcion { max-height: 3em; overflow: hidden; }
    .producto-destacado-descripcion p { line-height: 1.7; margin: 0 0 .5em; }
    .producto-destacado-descripcion p:last-child { margin-bottom: 0; }
    .producto-destacado-meta { color: #743629 !important; font-size: .9rem; font-weight: 700; text-transform: uppercase; }
    .producto-destacado-precio { color: #743629 !important; font: 700 1.15rem Georgia, serif; margin: 5px 0 7px; }
    .producto-destacado-boton { background: #481b18; border-radius: 5px; color: #fffaf7; display: inline-block; font-size: 14px; font-weight: 700; padding: 10px 15px; text-decoration: none; }
    .producto-destacado-boton:hover { background: #a97878; color: #fff; }
    .producto-destacado-sin-imagen { align-items: center; color: #75665d; display: flex; justify-content: center; min-height: 180px; padding: 20px; text-align: center; }
    .presentacion-modelo { background: rgba(255, 248, 242, .72); border: 1px solid rgba(184, 117, 82, .35); border-radius: 12px; box-sizing: border-box; margin: 30px auto 14px; max-width: 1080px; padding: clamp(22px, 3.5vw, 38px); width: calc(100% - 48px); }
    .presentacion-modelo-texto { color: #75665d; font: 400 clamp(1rem, 1.4vw, 1.1rem)/1.8 Georgia, "Times New Roman", serif; margin: 0 auto; max-width: 900px; text-align: center; }
    .campana-esencia { align-items: center; background: #f7f1ec; box-sizing: border-box; display: grid; gap: clamp(32px, 5vw, 72px); grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr); margin: 62px 0; padding: clamp(32px, 5vw, 64px) max(24px, calc((100% - 1180px) / 2)); }
    .campana-esencia-imagen { margin: 0; min-width: 0; }
    .campana-esencia-imagen img { aspect-ratio: 3 / 2; display: block; height: auto; object-fit: cover; width: 100%; }
    .campana-esencia-texto { margin: 0 auto; max-width: 500px; }
    .campana-esencia-etiqueta { color: #a97878; font-size: .78rem; font-weight: 700; margin: 0 0 16px; }
    .campana-esencia-titulo { color: #481b18; font: 700 2.4rem/1.16 Georgia, "Times New Roman", serif; margin: 0 0 18px; }
    .campana-esencia-descripcion { color: #75665d; font-size: 1rem; line-height: 1.8; margin: 0 0 24px; }
    .campana-esencia-boton { align-items: center; background: #481b18; border: 1px solid #481b18; border-radius: 5px; box-sizing: border-box; color: #fffaf7; display: inline-flex; font-size: 1rem; font-weight: 700; justify-content: center; min-height: 48px; padding: 12px 22px; text-decoration: none; transition: background-color .2s ease, border-color .2s ease; }
    .campana-esencia-boton:hover { background: #a97878; border-color: #a97878; }
    .campana-esencia-boton:focus-visible { outline: 3px solid #a97878; outline-offset: 3px; }
    .mejores-descuentos { background: linear-gradient(135deg, #f8eee8 0%, #f1e2d9 100%); border: 0; border-radius: 12px; box-sizing: border-box; margin: 36px auto 0; max-width: none; padding: clamp(24px, 3.5vw, 48px) max(20px, calc((100% - 1360px) / 2)); width: calc(100% - clamp(32px, 8vw, 112px)); }
    .encabezado-descuentos { margin-bottom: 22px; text-align: center; }
    .encabezado-descuentos h2 { margin: 8px 0 0; }
    .encabezado-descuentos .decoracion-titulo { margin: 10px auto 12px; }
    .descuentos-grid { align-items: stretch; display: grid; gap: clamp(14px, 1.8vw, 20px); grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .descuento-card { background: #fbf5f0; border: 1px solid #d9b8ad; border-radius: 12px; box-shadow: 0 6px 18px rgba(72, 27, 24, .07); display: flex; flex-direction: column; overflow: hidden; }
    .descuento-card:nth-child(2) { background: #f7ede7; }
    .descuento-card:nth-child(3) { background: #f2e4dc; }
    .descuento-imagen { background: #f0e2d9; height: 205px; overflow: hidden; position: relative; }
    .descuento-imagen > a { align-items: center; display: flex; height: 100%; justify-content: center; width: 100%; }
    .descuento-imagen img { box-sizing: border-box; display: block; height: 100%; mix-blend-mode: multiply; object-fit: contain; padding: 14px; width: 100%; }
    .descuento-contenido { align-items: center; display: flex; flex: 1; flex-direction: column; padding: 16px 16px 18px; text-align: center; }
    .descuento-contenido h3 { min-height: 38px; }
    .descuento-contenido > form { margin-top: auto; padding-top: 8px; width: 100%; }
    .descuento-contenido button { background: #481b18; border: 1px solid #481b18; border-radius: 7px; color: #fff8f2; max-width: 240px; width: 100%; }
    .descuento-contenido button:hover { background: #a97878; border-color: #a97878; }
    @media (max-width: 760px) { .producto-destacado-inicio { grid-template-columns: minmax(0, 1fr); margin: 16px 0 8px; padding: 12px 24px; } .producto-destacado-foto { min-height: 0; } .producto-destacado-foto img { max-height: 145px; width: 72%; } .producto-destacado-texto { margin: 0 auto; max-width: 620px; text-align: center; } .producto-destacado-sin-imagen { min-height: 120px; } .campana-esencia { grid-template-columns: minmax(0, 1fr); margin: 48px 0; padding: 32px 24px; } .campana-esencia-texto { max-width: 620px; text-align: center; } .descuentos-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 540px) { .producto-destacado-inicio { padding: 18px 14px; width: 100%; } .mejores-descuentos { padding: 24px 14px; width: calc(100% - 28px); } .presentacion-modelo { margin-top: 20px; padding: 20px 16px; width: calc(100% - 28px); } .presentacion-modelo-texto { font-size: 15px; line-height: 1.7; } .producto-destacado-foto img { max-height: 170px; width: 68%; } .campana-esencia { gap: 24px; margin: 38px 0; padding: 24px 18px; } .campana-esencia-titulo { font-size: 2rem; } .campana-esencia-descripcion { font-size: .96rem; } .campana-esencia-boton { width: 100%; } .descuentos-grid { grid-template-columns: 1fr; } .descuento-imagen { height: 195px; } }
</style>
</head>

<body>

<?php include("header.php"); ?>
<?php $notificacionPago = $_SESSION['notificacion_pago'] ?? null; unset($_SESSION['notificacion_pago']); ?>
<?php if (is_string($notificacionPago) && $notificacionPago !== ''): ?>
    <div class="notificacion-pago" id="notificacion-pago" role="status" aria-live="polite">
        <span><?= htmlspecialchars($notificacionPago, ENT_QUOTES, 'UTF-8') ?></span>
        <button type="button" aria-label="Cerrar notificación" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <script>setTimeout(()=>document.getElementById('notificacion-pago')?.remove(),6000);</script>
<?php endif; ?>
<?php include_once __DIR__ . '/conexion.php'; ?>
<?php $conexion = $GLOBALS['conexion']; ?>
<?php require_once __DIR__ . '/precios_carrito.php'; ?>
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

<section class="presentacion-modelo" aria-labelledby="titulo-presentacion-modelo">
    <p class="presentacion-modelo-texto" id="titulo-presentacion-modelo">La auténtica belleza de la mujer no sigue reglas ni se esconde detrás de estándares. Está en la fuerza de tu mirada, en la libertad de expresarte y en el amor con el que cuidas de ti misma cada día. Celebramos tu autenticidad compartiendo productos creados para resaltar lo que ya te hace única. Porque ser tú misma es tu mayor poder.</p>
</section>

<?php
$ofertasInicio = [];
$consultasOfertasInicio = [
    [
        'sql' => "SELECT id, titulo AS nombre, descripcion, categoria, precio, descuento, imagen, fecha_creacion FROM promociones WHERE activo = 1 ORDER BY fecha_creacion DESC",
        'tipo' => 'Promoción',
    ],
    [
        "sql" => "SELECT id, nombre, descripcion, 'Combo' AS categoria, precio, descuento, imagen, fecha_creacion FROM combos WHERE activo = 1 ORDER BY fecha_creacion DESC",
        'tipo' => 'Combo',
    ],
];
foreach ($consultasOfertasInicio as $consultaOfertaInicio) {
    $resultadoOfertasInicio = mysqli_query($conexion, $consultaOfertaInicio['sql']);
    if (!$resultadoOfertasInicio) {
        continue;
    }
    while ($ofertaInicio = mysqli_fetch_assoc($resultadoOfertasInicio)) {
        $rutaOfertaInicio = trim((string) ($ofertaInicio['imagen'] ?? ''));
        $rutaRealOfertaInicio = $rutaOfertaInicio !== '' && strpos($rutaOfertaInicio, 'IMG/') === 0 && strpos($rutaOfertaInicio, '..') === false
            ? realpath(__DIR__ . '/' . $rutaOfertaInicio)
            : false;
        if (!$rutaRealOfertaInicio || strpos($rutaRealOfertaInicio, realpath(__DIR__ . '/IMG') . DIRECTORY_SEPARATOR) !== 0) {
            $rutaOfertaInicio = 'IMG/default.jpg';
        }
        $ofertaInicio['imagen'] = $rutaOfertaInicio;
        $ofertaInicio['tipo'] = $consultaOfertaInicio['tipo'];
        $ofertasInicio[] = $ofertaInicio;
    }
}
usort($ofertasInicio, static function ($primera, $segunda) {
    return strtotime((string) $segunda['fecha_creacion']) <=> strtotime((string) $primera['fecha_creacion']);
});

$usarOfertasGuardadas = count($ofertasInicio) > 0;
if (!$usarOfertasGuardadas) {
    $nombresDestacados = ['Base Nars', 'Paleta de sombras', 'Protector solar'];
    $consultaDestacados = mysqli_prepare($conexion, 'SELECT id, nombre, descripcion, categoria, precio, imagen FROM productos WHERE activo = 1 AND nombre IN (?, ?, ?)');
    if ($consultaDestacados) {
        mysqli_stmt_bind_param($consultaDestacados, 'sss', $nombresDestacados[0], $nombresDestacados[1], $nombresDestacados[2]);
        if (mysqli_stmt_execute($consultaDestacados)) {
            $resultadoDestacados = mysqli_stmt_get_result($consultaDestacados);
            $productosDestacados = [];
            while ($resultadoDestacados && ($productoDestacado = mysqli_fetch_assoc($resultadoDestacados))) {
                $productosDestacados[$productoDestacado['nombre']] = $productoDestacado;
            }
            foreach ($nombresDestacados as $nombreDestacado) {
                if (!isset($productosDestacados[$nombreDestacado])) {
                    continue;
                }
                $productoDestacado = $productosDestacados[$nombreDestacado];
                $ofertasInicio[] = [
                    'id' => (int) $productoDestacado['id'],
                    'producto_id' => (int) $productoDestacado['id'],
                    'es_producto_catalogo' => true,
                    'nombre' => $productoDestacado['nombre'],
                    'descripcion' => $productoDestacado['descripcion'],
                    'categoria' => $productoDestacado['categoria'],
                    'precio' => (int) $productoDestacado['precio'],
                    'descuento' => 0,
                    'imagen' => $productoDestacado['imagen'],
                    'tipo' => 'Producto destacado',
                ];
            }
        }
        mysqli_stmt_close($consultaDestacados);
    }
}
?>

<section class="mejores-descuentos" aria-labelledby="titulo-descuentos">
    <div class="encabezado-descuentos">
        <span class="supertitulo-descuentos"><?php echo htmlspecialchars($sesionFavoritosPublica['nombre']); ?></span>
        <h2 id="titulo-descuentos"><?php echo $usarOfertasGuardadas ? 'Promociones y combos' : 'Productos destacados'; ?></h2>
        <div class="decoracion-titulo" aria-hidden="true"><span>♡</span></div>
        <p><?php echo $usarOfertasGuardadas ? 'Descubre las ofertas disponibles en Always Beautiful.' : 'Descubre productos disponibles en el catálogo de Always Beautiful.'; ?></p>
    </div>

    <div class="descuentos-grid">
        <?php foreach ($ofertasInicio as $oferta):
            $precioAnterior = (float) $oferta['precio'];
            $porcentajeDescuento = (float) $oferta['descuento'];
            $precioActual = precioOfertaCarrito($precioAnterior, $porcentajeDescuento);
            if ($precioActual === null) {
                continue;
            }
            $nombreOferta = (string) $oferta['nombre'];
            $descripcionOferta = (string) ($oferta['descripcion'] ?? 'Oferta de Always Beautiful.');
            $categoriaOferta = (string) ($oferta['categoria'] ?? $oferta['tipo']);
            $imagenOferta = (string) $oferta['imagen'];
            $etiquetaOferta = $porcentajeDescuento > 0 ? '-' . rtrim(rtrim(number_format($porcentajeDescuento, 2, '.', ''), '0'), '.') . '%' : $oferta['tipo'];
            $enlaceOferta = !empty($oferta['es_producto_catalogo'])
                ? 'producto.php?id=' . (int) $oferta['producto_id']
                : 'producto.php?' . http_build_query([
                    'nombre' => $nombreOferta,
                    'precio' => $precioActual,
                    'imagen' => $imagenOferta,
                    'descripcion' => $descripcionOferta,
                    'categoria' => $categoriaOferta,
                ]);
        ?>
            <article class="descuento-card">
                <div class="descuento-imagen">
                    <span class="insignia-descuento"><?php echo htmlspecialchars($etiquetaOferta, ENT_QUOTES, 'UTF-8'); ?></span>
                    <a href="<?php echo htmlspecialchars($enlaceOferta, ENT_QUOTES, 'UTF-8'); ?>"><img src="<?php echo htmlspecialchars($imagenOferta, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($nombreOferta, ENT_QUOTES, 'UTF-8'); ?>"></a>
                </div>
                <div class="descuento-contenido">
                    <span class="categoria-descuento"><?php echo htmlspecialchars($categoriaOferta, ENT_QUOTES, 'UTF-8'); ?></span>
                    <h3><a class="nombre-producto" href="<?php echo htmlspecialchars($enlaceOferta, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($nombreOferta, ENT_QUOTES, 'UTF-8'); ?></a></h3>
                    <a class="enlace-detalle" href="<?php echo htmlspecialchars($enlaceOferta, ENT_QUOTES, 'UTF-8'); ?>">Ver detalles</a>
                    <?php if ($porcentajeDescuento > 0): ?><p class="precio-anterior">$<?php echo number_format($precioAnterior, 0, ',', '.'); ?></p><?php endif; ?>
                    <p class="precio-descuento">$<?php echo number_format($precioActual, 0, ',', '.'); ?></p>
                    <form method="POST" action="carrito.php">
                        <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($nombreOferta, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="precio" value="<?php echo $precioActual; ?>">
                        <input type="hidden" name="imagen" value="<?php echo htmlspecialchars($imagenOferta, ENT_QUOTES, 'UTF-8'); ?>">
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

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>

