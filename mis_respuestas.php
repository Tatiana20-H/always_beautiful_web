<?php
session_start();
include 'conexion.php';
$conexion = $GLOBALS['conexion'];
require_once __DIR__ . '/migracion_respuestas_leidas.php';

if (empty($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') === 'admin') {
    header('Location: index.php');
    exit();
}

$usuarioId = (int) $_SESSION['usuario_id'];
$correoUsuario = (string) ($_SESSION['correo'] ?? '');
$tokenCsrf = $_SESSION['token_csrf_respuestas'] ?? bin2hex(random_bytes(32));
$_SESSION['token_csrf_respuestas'] = $tokenCsrf;
$mensajeAccion = $_SESSION['mensaje_bandeja'] ?? '';
unset($_SESSION['mensaje_bandeja']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenEnviado = $_POST['token_csrf'] ?? '';
    if (!is_string($tokenEnviado) || !hash_equals($tokenCsrf, $tokenEnviado)) {
        $_SESSION['mensaje_bandeja'] = 'La sesión del formulario expiró. Recarga la página e inténtalo de nuevo.';
    } elseif (isset($_POST['responder_mensaje'])) {
        $mensajeId = (int) ($_POST['mensaje_id'] ?? 0);
        $textoRespuesta = trim((string) ($_POST['respuesta'] ?? ''));
        $stmtPropietario = mysqli_prepare($conexion, 'SELECT id FROM mensajes_contacto WHERE id = ? AND (usuario_id = ? OR (usuario_id IS NULL AND correo = ?)) LIMIT 1');
        $esPropietario = false;
        if ($stmtPropietario) {
            mysqli_stmt_bind_param($stmtPropietario, 'iis', $mensajeId, $usuarioId, $correoUsuario);
            mysqli_stmt_execute($stmtPropietario);
            $resultadoPropietario = mysqli_stmt_get_result($stmtPropietario);
            $esPropietario = $resultadoPropietario && mysqli_num_rows($resultadoPropietario) > 0;
            mysqli_stmt_close($stmtPropietario);
        }
        if (!$esPropietario || $textoRespuesta === '' || strlen($textoRespuesta) > 5000) {
            $_SESSION['mensaje_bandeja'] = 'Escribe una respuesta de hasta 5000 caracteres en una conversación válida.';
        } else {
            $stmtRespuesta = mysqli_prepare($conexion, "INSERT INTO respuestas_mensajes (mensaje_id, admin_id, usuario_id, remitente, respuesta, visto_usuario) VALUES (?, NULL, ?, 'usuario', ?, 1)");
            if ($stmtRespuesta) {
                mysqli_stmt_bind_param($stmtRespuesta, 'iis', $mensajeId, $usuarioId, $textoRespuesta);
                $guardado = mysqli_stmt_execute($stmtRespuesta);
                mysqli_stmt_close($stmtRespuesta);
                if ($guardado) {
                    $stmtEstado = mysqli_prepare($conexion, "UPDATE mensajes_contacto SET estado = 'no_leido' WHERE id = ?");
                    if ($stmtEstado) {
                        mysqli_stmt_bind_param($stmtEstado, 'i', $mensajeId);
                        mysqli_stmt_execute($stmtEstado);
                        mysqli_stmt_close($stmtEstado);
                    }
                    $_SESSION['mensaje_bandeja'] = 'Tu respuesta fue enviada al equipo de atención.';
                } else {
                    $_SESSION['mensaje_bandeja'] = 'No fue posible enviar tu respuesta. Inténtalo de nuevo.';
                }
            } else {
                $_SESSION['mensaje_bandeja'] = 'No fue posible preparar tu respuesta. Inténtalo de nuevo.';
            }
        }
    } elseif (isset($_POST['eliminar_mensaje'])) {
        $mensajeId = (int) ($_POST['mensaje_id'] ?? 0);
        $stmtEliminar = mysqli_prepare($conexion, 'DELETE FROM mensajes_contacto WHERE id = ? AND (usuario_id = ? OR (usuario_id IS NULL AND correo = ?))');
        if ($stmtEliminar) {
            mysqli_stmt_bind_param($stmtEliminar, 'iis', $mensajeId, $usuarioId, $correoUsuario);
            $eliminado = mysqli_stmt_execute($stmtEliminar) && mysqli_stmt_affected_rows($stmtEliminar) === 1;
            mysqli_stmt_close($stmtEliminar);
            $_SESSION['mensaje_bandeja'] = $eliminado ? 'El mensaje y su conversación fueron eliminados.' : 'No se encontró la conversación para eliminar.';
        }
    } elseif (isset($_POST['marcar_leidas'])) {
        $stmtMarcar = mysqli_prepare($conexion, "UPDATE respuestas_mensajes r JOIN mensajes_contacto m ON m.id = r.mensaje_id SET r.visto_usuario = 1 WHERE r.visto_usuario = 0 AND r.remitente = 'admin' AND (m.usuario_id = ? OR (m.usuario_id IS NULL AND m.correo = ?))");
        if ($stmtMarcar) {
            mysqli_stmt_bind_param($stmtMarcar, 'is', $usuarioId, $correoUsuario);
            mysqli_stmt_execute($stmtMarcar);
            mysqli_stmt_close($stmtMarcar);
            $_SESSION['mensaje_bandeja'] = 'Las respuestas quedaron marcadas como leídas.';
        }
    }
    header('Location: mis_respuestas.php');
    exit();
}

$stmtBandeja = mysqli_prepare($conexion, 'SELECT m.id AS mensaje_id, m.asunto, m.mensaje, m.fecha_creacion AS fecha_mensaje, r.respuesta, r.remitente, r.fecha_creacion AS fecha_respuesta, r.visto_usuario, a.nombre AS nombre_admin FROM mensajes_contacto m JOIN respuestas_mensajes r ON r.mensaje_id = m.id LEFT JOIN usuarios a ON a.id = r.admin_id WHERE m.usuario_id = ? OR (m.usuario_id IS NULL AND m.correo = ?) ORDER BY m.id DESC, r.fecha_creacion ASC');
$conversaciones = [];
if ($stmtBandeja) {
    mysqli_stmt_bind_param($stmtBandeja, 'is', $usuarioId, $correoUsuario);
    mysqli_stmt_execute($stmtBandeja);
    $resultadoBandeja = mysqli_stmt_get_result($stmtBandeja);
    while ($fila = mysqli_fetch_assoc($resultadoBandeja)) {
        $mensajeId = (int) $fila['mensaje_id'];
        if (!isset($conversaciones[$mensajeId])) {
            $conversaciones[$mensajeId] = [
                'id' => $mensajeId,
                'asunto' => $fila['asunto'],
                'mensaje' => $fila['mensaje'],
                'fecha_mensaje' => $fila['fecha_mensaje'],
                'respuestas_nuevas' => 0,
                'mensajes' => [],
            ];
        }
        $conversaciones[$mensajeId]['mensajes'][] = $fila;
        if ($fila['remitente'] === 'admin' && (int) $fila['visto_usuario'] === 0) {
            $conversaciones[$mensajeId]['respuestas_nuevas']++;
        }
    }
    mysqli_stmt_close($stmtBandeja);
}
$sinLeer = array_sum(array_column($conversaciones, 'respuestas_nuevas'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis respuestas - Always Beautiful</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include 'header.php'; ?>

<main class="bandeja-respuestas">
    <header class="bandeja-encabezado">
        <div>
            <p class="bandeja-eyebrow">Atención al cliente</p>
            <h1>Mis conversaciones</h1>
            <p>Consulta las respuestas del equipo y continúa la conversación desde aquí.</p>
        </div>
        <?php if ($sinLeer > 0): ?>
            <form method="POST" class="bandeja-accion">
                <input type="hidden" name="token_csrf" value="<?= htmlspecialchars($tokenCsrf, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" name="marcar_leidas" value="1">Marcar como leídas (<?= $sinLeer ?>)</button>
            </form>
        <?php endif; ?>
    </header>

    <?php if ($mensajeAccion): ?><p class="bandeja-confirmacion" role="status"><?= htmlspecialchars($mensajeAccion, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

    <?php if ($conversaciones): ?>
        <section class="lista-respuestas" aria-label="Conversaciones">
            <?php foreach ($conversaciones as $conversacion): ?>
                <?php $ultimaActividad = end($conversacion['mensajes']); reset($conversacion['mensajes']); ?>
                <article class="respuesta-item <?= $conversacion['respuestas_nuevas'] > 0 ? 'respuesta-nueva' : '' ?>">
                    <header class="respuesta-meta">
                        <div>
                            <h2><?= htmlspecialchars($conversacion['asunto'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <p>Iniciada el <?= date('d/m/Y H:i', strtotime($conversacion['fecha_mensaje'])) ?></p>
                        </div>
                        <div class="respuesta-fecha">
                            <time datetime="<?= htmlspecialchars($ultimaActividad['fecha_respuesta'], ENT_QUOTES, 'UTF-8') ?>"><?= date('d/m/Y H:i', strtotime($ultimaActividad['fecha_respuesta'])) ?></time>
                            <?php if ($conversacion['respuestas_nuevas'] > 0): ?><span><?= $conversacion['respuestas_nuevas'] ?> nuevas</span><?php endif; ?>
                        </div>
                    </header>
                    <div class="respuesta-contenido">
                        <div class="mensaje-hilo mensaje-hilo-usuario">
                            <h3>Tu mensaje</h3>
                            <p><?= nl2br(htmlspecialchars($conversacion['mensaje'], ENT_QUOTES, 'UTF-8')) ?></p>
                        </div>
                        <?php foreach ($conversacion['mensajes'] as $mensajeHilo): ?>
                            <?php $esRespuestaUsuario = $mensajeHilo['remitente'] === 'usuario'; ?>
                            <div class="mensaje-hilo <?= $esRespuestaUsuario ? 'mensaje-hilo-usuario' : 'mensaje-hilo-admin' ?>">
                                <div class="mensaje-hilo-encabezado">
                                    <h3><?= $esRespuestaUsuario ? 'Tu respuesta' : 'Respuesta de ' . htmlspecialchars($mensajeHilo['nombre_admin'] ?: 'Administración', ENT_QUOTES, 'UTF-8') ?></h3>
                                    <time datetime="<?= htmlspecialchars($mensajeHilo['fecha_respuesta'], ENT_QUOTES, 'UTF-8') ?>"><?= date('d/m/Y H:i', strtotime($mensajeHilo['fecha_respuesta'])) ?></time>
                                </div>
                                <p><?= nl2br(htmlspecialchars($mensajeHilo['respuesta'], ENT_QUOTES, 'UTF-8')) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <footer class="respuesta-acciones">
                        <form method="POST" class="formulario-replica">
                            <input type="hidden" name="token_csrf" value="<?= htmlspecialchars($tokenCsrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="mensaje_id" value="<?= $conversacion['id'] ?>">
                            <label for="replica-<?= $conversacion['id'] ?>">Responder a la conversación</label>
                            <textarea id="replica-<?= $conversacion['id'] ?>" name="respuesta" maxlength="5000" rows="3" placeholder="Escribe tu respuesta para el equipo..." required></textarea>
                            <button type="submit" name="responder_mensaje" value="1">Enviar respuesta</button>
                        </form>
                        <form method="POST" class="formulario-eliminar-conversacion" onsubmit="return confirm('¿Eliminar este mensaje y toda su conversación? Esta acción no se puede deshacer.');">
                            <input type="hidden" name="token_csrf" value="<?= htmlspecialchars($tokenCsrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="mensaje_id" value="<?= $conversacion['id'] ?>">
                            <button type="submit" name="eliminar_mensaje" value="1">Eliminar conversación</button>
                        </form>
                    </footer>
                </article>
            <?php endforeach; ?>
        </section>
    <?php else: ?>
        <section class="bandeja-vacia">
            <span aria-hidden="true">✉</span>
            <h2>Aún no tienes conversaciones</h2>
            <p>Cuando envíes un mensaje y el equipo responda, podrás continuar la conversación aquí.</p>
            <a href="formulario.php">Enviar un mensaje</a>
        </section>
    <?php endif; ?>
</main>
</body>
</html>