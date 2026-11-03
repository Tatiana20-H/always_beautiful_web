<?php
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];

$nombre = trim($_GET['nombre'] ?? 'Producto');
$precio = (int) ($_GET['precio'] ?? 0);
$imagen = $_GET['imagen'] ?? '';
$descripcion = trim($_GET['descripcion'] ?? 'Este producto fue seleccionado para acompañar tu rutina de belleza.');
$valoracion = 0;
$categoria = trim($_GET['categoria'] ?? 'Always Beautiful');
$beneficios = [
    'Maquillaje' => 'Ayuda a crear looks definidos y a realzar tus facciones con un acabado bonito y duradero.',
    'Piel' => 'Ayuda a limpiar, hidratar y proteger la piel para mantenerla suave, fresca y cuidada.',
    'Cabello' => 'Ayuda a nutrir, proteger y mantener el cabello suave, brillante y fácil de peinar.',
    'Accesorios' => 'Ayuda a complementar tus looks y a cuidar tu cabello con comodidad y estilo.'
];
$beneficio = $beneficios[$categoria] ?? 'Ayuda a complementar tu rutina de belleza con una opción pensada para tu bienestar.';


function usuarioComproProducto($nombre) {
    foreach ($_SESSION['historial'] ?? [] as $compra) {
        foreach ($compra['productos'] ?? [] as $productoNombre => $producto) {
            if ($productoNombre === $nombre) {
                return true;
            }
        }
    }
    return false;
}

$mensaje = '';
$tipoMensaje = '';
$usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $reseñaId = (int) ($_POST['reseña_id'] ?? 0);

    if ($accion === 'crear_reseña') {
        $comentario = trim($_POST['comentario'] ?? '');
        $estrellasNueva = (int) ($_POST['estrellas'] ?? 0);
        $nombreAutor = trim($_POST['nombre_autor'] ?? 'Visitante');
        $nombreAutor = $nombreAutor !== '' ? $nombreAutor : 'Visitante';
        if ($estrellasNueva < 1 || $estrellasNueva > 5 || $comentario === '') {
            $mensaje = 'Selecciona una valoración y escribe tu comentario.';
            $tipoMensaje = 'error';
        } else {
            if ($usuarioId > 0) {
                $stmt = mysqli_prepare($conexion, 'INSERT INTO reseñas_productos (usuario_id, nombre_autor, producto_nombre, estrellas, comentario) VALUES (?, ?, ?, ?, ?)');
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'issis', $usuarioId, $nombreAutor, $nombre, $estrellasNueva, $comentario);
                }
            } else {
                $stmt = mysqli_prepare($conexion, 'INSERT INTO reseñas_productos (usuario_id, nombre_autor, producto_nombre, estrellas, comentario) VALUES (NULL, ?, ?, ?, ?)');
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'ssis', $nombreAutor, $nombre, $estrellasNueva, $comentario);
                }
            }
            if ($stmt && mysqli_stmt_execute($stmt)) {
                $mensaje = 'Tu reseña fue publicada.';
                $tipoMensaje = 'exito';
            } else {
                $mensaje = 'No se pudo publicar la reseña en este momento.';
                $tipoMensaje = 'error';
            }
            if ($stmt) {
                mysqli_stmt_close($stmt);
            }
        }
    } elseif ($usuarioId && in_array($accion, ['like', 'dislike'], true) && $reseñaId > 0) {
        $voto = $accion;
        $stmt = mysqli_prepare($conexion, 'INSERT INTO votos_reseñas (reseña_id, usuario_id, voto) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE voto = VALUES(voto)');
        mysqli_stmt_bind_param($stmt, 'iis', $reseñaId, $usuarioId, $voto);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } elseif ($usuarioId && $accion === 'reportar' && $reseñaId > 0) {
        $motivo = trim($_POST['motivo'] ?? 'Comentario inapropiado');
        $stmt = mysqli_prepare($conexion, 'INSERT IGNORE INTO reportes_reseñas (reseña_id, usuario_id, motivo) VALUES (?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iis', $reseñaId, $usuarioId, $motivo);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $mensaje = 'Gracias. Revisaremos el comentario reportado.';
        $tipoMensaje = 'exito';
    }
}

$reseñas = [];
$stmt = mysqli_prepare($conexion, "SELECT r.id, r.usuario_id, r.estrellas, r.comentario, r.fecha_creacion, COALESCE(NULLIF(r.nombre_autor, ''), u.nombre, 'Visitante') AS nombre FROM reseñas_productos r LEFT JOIN usuarios u ON u.id = r.usuario_id WHERE r.producto_nombre = ? ORDER BY r.fecha_creacion DESC");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $nombre);
    mysqli_stmt_execute($stmt);
    $resultadoReseñas = mysqli_stmt_get_result($stmt);
    while ($reseña = mysqli_fetch_assoc($resultadoReseñas)) {
        $reseña['likes'] = 0;
        $reseña['dislikes'] = 0;
        $reseñas[] = $reseña;
    }
    mysqli_stmt_close($stmt);
} else {
    $mensaje = 'No se pudieron cargar las reseñas en este momento.';
    $tipoMensaje = 'error';
}

if ($reseñas) {
    $valoracion = array_sum(array_column($reseñas, 'estrellas')) / count($reseñas);
}

foreach ($reseñas as &$reseña) {
    $reseñaId = (int) $reseña['id'];
    $votos = mysqli_query($conexion, "SELECT voto, COUNT(*) AS total FROM votos_reseñas WHERE reseña_id = $reseñaId GROUP BY voto");
    while ($voto = mysqli_fetch_assoc($votos)) {
        $reseña[$voto['voto'] === 'like' ? 'likes' : 'dislikes'] = (int) $voto['total'];
    }
}
unset($reseña);

function estrellas($cantidad) {
    return str_repeat('★', $cantidad) . str_repeat('☆', 5 - $cantidad);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($nombre); ?> - Always Beautiful</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include("header.php"); ?>

<main class="detalle-producto">
    <a class="volver-productos" href="javascript:history.back()">&larr; Volver a productos</a>

    <section class="producto-presentacion">
        <div class="producto-foto-detalle">
            <img src="<?php echo htmlspecialchars($imagen); ?>" alt="<?php echo htmlspecialchars($nombre); ?>">
        </div>
        <div class="producto-info-detalle">
            <span class="categoria-detalle"><?php echo htmlspecialchars($categoria); ?></span>
            <h1><?php echo htmlspecialchars($nombre); ?></h1>
            <div class="valoracion-detalle">
                <?php if ($reseñas): ?>
                    <span class="estrellas-detalle"><?php echo estrellas((int) round($valoracion)); ?></span>
                    <strong><?php echo number_format($valoracion, 1, ',', '.'); ?></strong>
                <?php else: ?>
                    <span>Aún no hay valoraciones</span>
                <?php endif; ?>
                <span>(<?php echo count($reseñas); ?> reseñas)</span>
            </div>
            <p class="precio-detalle">$<?php echo number_format($precio, 0, ',', '.'); ?></p>
            <p class="descripcion-detalle"><?php echo htmlspecialchars($descripcion); ?></p>
            <h2>¿Cómo te ayuda?</h2>
            <p class="beneficio-detalle"><?php echo htmlspecialchars($beneficio); ?></p>
            <form method="POST" action="carrito.php">
                <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>">
                <input type="hidden" name="precio" value="<?php echo $precio; ?>">
                <input type="hidden" name="imagen" value="<?php echo htmlspecialchars($imagen); ?>">
                <button class="boton-detalle" type="submit">Agregar al carrito</button>
            </form>
        </div>
    </section>

    <section class="reseñas-producto" aria-labelledby="titulo-reseñas">
        <div class="encabezado-reseñas">
            <span class="supertitulo-descuentos">Lo que dicen nuestras clientas</span>
            <h2 id="titulo-reseñas">Reseñas y comentarios</h2>
        </div>
        <?php if ($mensaje): ?>
            <p class="mensaje-reseña <?php echo $tipoMensaje; ?>"><?php echo htmlspecialchars($mensaje); ?></p>
        <?php endif; ?>
        <form class="formulario-reseña" method="POST">
            <input type="hidden" name="accion" value="crear_reseña">
            <h3>Comparte tu experiencia</h3>
            <label for="nombre_autor">Tu nombre</label>
            <input id="nombre_autor" name="nombre_autor" type="text" maxlength="100" value="<?php echo htmlspecialchars($_SESSION['nombre'] ?? ''); ?>" placeholder="Escribe tu nombre">
            <label for="estrellas">Tu valoración</label>
            <select id="estrellas" name="estrellas" required>
                <option value="">Selecciona estrellas</option>
                <option value="5">5 estrellas</option>
                <option value="4">4 estrellas</option>
                <option value="3">3 estrellas</option>
                <option value="2">2 estrellas</option>
                <option value="1">1 estrella</option>
            </select>
            <textarea name="comentario" rows="4" maxlength="1000" placeholder="Escribe tu reseña..." required></textarea>
            <button type="submit">Publicar reseña</button>
        </form>
        <?php if (!$reseñas): ?>
            <p class="reseñas-vacias">Este producto todavía no tiene reseñas.</p>
        <?php endif; ?>
        <?php foreach ($reseñas as $reseña): ?>
            <article class="reseña-item">
                <div class="reseña-autor">
                    <span class="avatar-reseña"><?php echo strtoupper(substr($reseña['nombre'], 0, 1)); ?></span>
                    <div>
                        <strong><?php echo htmlspecialchars($reseña['nombre']); ?></strong>
                        <small><?php echo date('d/m/Y', strtotime($reseña['fecha_creacion'])); ?></small>
                    </div>
                </div>
                <div class="reseña-contenido">
                    <div class="estrellas-reseña" aria-label="<?php echo $reseña['estrellas']; ?> de 5 estrellas"><?php echo estrellas($reseña['estrellas']); ?></div>
                    <p><?php echo htmlspecialchars($reseña['comentario']); ?></p>
                    <div class="acciones-reseña">
                        <form method="POST"><input type="hidden" name="accion" value="like"><input type="hidden" name="reseña_id" value="<?php echo $reseña['id']; ?>"><button type="submit" aria-label="Me gusta">&#128077; <?php echo $reseña['likes']; ?></button></form>
                        <form method="POST"><input type="hidden" name="accion" value="dislike"><input type="hidden" name="reseña_id" value="<?php echo $reseña['id']; ?>"><button type="submit" aria-label="No me gusta">&#128078; <?php echo $reseña['dislikes']; ?></button></form>
                        <?php if ($usuarioId): ?><form method="POST"><input type="hidden" name="accion" value="reportar"><input type="hidden" name="reseña_id" value="<?php echo $reseña['id']; ?>"><input type="hidden" name="motivo" value="Comentario inapropiado"><button class="boton-reportar" type="submit">Reportar</button></form><?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</main>
</body>
</html>
