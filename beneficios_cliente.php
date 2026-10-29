<?php
session_start();
if (strtolower((string) ($_SESSION['rol'] ?? '')) === 'admin') {
    header('Location: Administrador/index.php');
    exit();
}
if (empty($_SESSION['usuario_id'])) {
    header('Location: inicio.php');
    exit();
}
include_once __DIR__ . '/conexion.php';
$conexion = $GLOBALS['conexion'];
$usuarioId = (int) $_SESSION['usuario_id'];

if (empty($_SESSION['csrf_beneficios'])) {
    $_SESSION['csrf_beneficios'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_string($_POST['csrf_token'] ?? null) &&
    hash_equals($_SESSION['csrf_beneficios'], $_POST['csrf_token'])) {
    $accionBeneficio = (string) ($_POST['accion_beneficio'] ?? '');
    if ($accionBeneficio === 'leer_notificaciones') {
        $marcarLeidas = mysqli_prepare($conexion, 'UPDATE notificaciones_usuarios SET leida = 1 WHERE usuario_id = ?');
        if ($marcarLeidas) {
            mysqli_stmt_bind_param($marcarLeidas, 'i', $usuarioId);
            mysqli_stmt_execute($marcarLeidas);
            mysqli_stmt_close($marcarLeidas);
        }
    } elseif (in_array($accionBeneficio, ['pedir_descuento', 'pedir_obsequio'], true)) {
        $tipoSolicitud = $accionBeneficio === 'pedir_descuento' ? 'descuento' : 'obsequio';
        $puntosSolicitud = $tipoSolicitud === 'descuento' ? 7 : 5;
        $resultadoSolicitud = 'No se pudo enviar la solicitud. Inténtalo nuevamente.';
        if (mysqli_begin_transaction($conexion)) {
            $bloquearCuenta = mysqli_prepare($conexion, 'SELECT id FROM usuarios WHERE id = ? AND activo = 1 FOR UPDATE');
            if ($bloquearCuenta) {
                mysqli_stmt_bind_param($bloquearCuenta, 'i', $usuarioId);
                $cuentaConsultada = mysqli_stmt_execute($bloquearCuenta);
                $resultadoCuenta = $cuentaConsultada ? mysqli_stmt_get_result($bloquearCuenta) : false;
                $cuentaValida = $resultadoCuenta && mysqli_fetch_assoc($resultadoCuenta);
                mysqli_stmt_close($bloquearCuenta);
            } else {
                $cuentaValida = false;
            }
            $buscarActiva = mysqli_prepare($conexion, "SELECT id FROM solicitudes_beneficios WHERE usuario_id = ? AND tipo = ? AND estado IN ('solicitado', 'reservado') LIMIT 1 FOR UPDATE");
            if ($buscarActiva) {
                mysqli_stmt_bind_param($buscarActiva, 'is', $usuarioId, $tipoSolicitud);
                $solicitudConsultada = mysqli_stmt_execute($buscarActiva);
                $resultadoSolicitudActiva = $solicitudConsultada ? mysqli_stmt_get_result($buscarActiva) : false;
                $solicitudActiva = $resultadoSolicitudActiva ? mysqli_fetch_assoc($resultadoSolicitudActiva) : null;
                mysqli_stmt_close($buscarActiva);
            } else {
                $solicitudActiva = ['id' => 0];
            }
            if (!$cuentaValida) {
                $resultadoSolicitud = 'No se encontró una cuenta activa.';
                mysqli_rollback($conexion);
            } elseif ($solicitudActiva) {
                $resultadoSolicitud = 'Ya tienes una solicitud de este beneficio en curso.';
                mysqli_rollback($conexion);
            } elseif (puntosDisponiblesUsuario($conexion, $usuarioId) < $puntosSolicitud) {
                $resultadoSolicitud = 'Necesitas ' . $puntosSolicitud . ' puntos disponibles para pedir este beneficio.';
                mysqli_rollback($conexion);
            } else {
                $crearSolicitud = mysqli_prepare($conexion, "INSERT INTO solicitudes_beneficios (usuario_id, tipo, puntos) VALUES (?, ?, ?)");
                if ($crearSolicitud) {
                    mysqli_stmt_bind_param($crearSolicitud, 'isi', $usuarioId, $tipoSolicitud, $puntosSolicitud);
                    $solicitudCreada = mysqli_stmt_execute($crearSolicitud);
                    mysqli_stmt_close($crearSolicitud);
                } else {
                    $solicitudCreada = false;
                }
                if ($solicitudCreada && mysqli_commit($conexion)) {
                    $resultadoSolicitud = $tipoSolicitud === 'descuento'
                        ? 'Solicitud enviada. El descuento se aplicará automáticamente a tu siguiente compra elegible.'
                        : 'Solicitud de obsequio enviada. El equipo revisará tu pedido.';
                } else {
                    mysqli_rollback($conexion);
                }
            }
        }
        $_SESSION['flash_beneficios'] = $resultadoSolicitud;
    }
    header('Location: beneficios_cliente.php', true, 303);
    exit();
}

$puntos = puntosDisponiblesUsuario($conexion, $usuarioId);
$mensajeBeneficio = (string) ($_SESSION['flash_beneficios'] ?? '');
unset($_SESSION['flash_beneficios']);
$solicitudes = [];
$consultaSolicitudes = mysqli_prepare($conexion, 'SELECT tipo, estado, puntos, fecha_solicitud FROM solicitudes_beneficios WHERE usuario_id = ? ORDER BY fecha_solicitud DESC, id DESC LIMIT 20');
if ($consultaSolicitudes) {
    mysqli_stmt_bind_param($consultaSolicitudes, 'i', $usuarioId);
    if (mysqli_stmt_execute($consultaSolicitudes)) {
        $resultadoSolicitudes = mysqli_stmt_get_result($consultaSolicitudes);
        $solicitudes = $resultadoSolicitudes ? mysqli_fetch_all($resultadoSolicitudes, MYSQLI_ASSOC) : [];
    }
    mysqli_stmt_close($consultaSolicitudes);
}
$solicitudActivaPorTipo = [];
foreach ($solicitudes as $solicitud) {
    if (!isset($solicitudActivaPorTipo[$solicitud['tipo']]) && in_array($solicitud['estado'], ['solicitado', 'reservado'], true)) {
        $solicitudActivaPorTipo[$solicitud['tipo']] = $solicitud;
    }
}
$notificaciones = [];
$consultaNotificaciones = mysqli_prepare($conexion, 'SELECT tipo, titulo, descripcion, precio, imagen, fecha_creacion FROM notificaciones_usuarios WHERE usuario_id = ? ORDER BY fecha_creacion DESC, id DESC LIMIT 100');
if ($consultaNotificaciones) {
    mysqli_stmt_bind_param($consultaNotificaciones, 'i', $usuarioId);
    if (mysqli_stmt_execute($consultaNotificaciones)) {
        $resultadoNotificaciones = mysqli_stmt_get_result($consultaNotificaciones);
        $notificaciones = $resultadoNotificaciones ? mysqli_fetch_all($resultadoNotificaciones, MYSQLI_ASSOC) : [];
    }
    mysqli_stmt_close($consultaNotificaciones);
}
?>
<?php include __DIR__ . '/header.php'; ?>
<main class="beneficios-cliente">
    <header class="beneficios-cabecera">
        <div>
            <p class="beneficios-etiqueta">ALWAYS BEAUTIFUL · CLIENTE</p>
            <h1>Mis beneficios</h1>
        </div>
        <div class="beneficios-saldo"><strong><?= (int) $puntos ?></strong><span>puntos disponibles</span></div>
    </header>

    <section class="reglas-puntos" aria-label="Beneficios por puntos">
        <p><strong>Acumulación:</strong> recibes 1 punto por cada $50.000 COP de compra pagada. Una compra de $100.000 da 2 puntos.</p>
        <p><strong>Descuento:</strong> solicita con 7 puntos un 30% para una compra superior a $50.000; se aplica al siguiente pedido elegible.</p>
        <p><strong>Obsequio:</strong> solicita con 5 puntos; el equipo confirmará el regalo disponible.</p>
    </section>

    <?php if ($mensajeBeneficio !== ''): ?><p class="beneficios-mensaje" role="status"><?= htmlspecialchars($mensajeBeneficio, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <section class="solicitud-beneficios" aria-label="Solicitar un beneficio">
        <h2>Canjear puntos</h2>
        <div class="solicitud-beneficios-acciones">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_beneficios'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="accion_beneficio" value="pedir_obsequio">
                <button type="submit" <?= ($puntos < 5 || isset($solicitudActivaPorTipo['obsequio'])) ? 'disabled' : '' ?>>Pedir obsequio · 5 puntos</button>
                <?php if (isset($solicitudActivaPorTipo['obsequio'])): ?><small>Solicitud <?= $solicitudActivaPorTipo['obsequio']['estado'] === 'reservado' ? 'en revisión' : 'enviada' ?></small><?php endif; ?>
            </form>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_beneficios'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="accion_beneficio" value="pedir_descuento">
                <button type="submit" <?= ($puntos < 7 || isset($solicitudActivaPorTipo['descuento'])) ? 'disabled' : '' ?>>Pedir descuento · 7 puntos</button>
                <?php if (isset($solicitudActivaPorTipo['descuento'])): ?><small>El descuento <?= $solicitudActivaPorTipo['descuento']['estado'] === 'reservado' ? 'está reservado para tu pedido' : 'se aplicará en tu próxima compra elegible' ?>.</small><?php endif; ?>
            </form>
        </div>
    </section>

    <?php if ($solicitudes !== []): ?>
        <section class="historial-solicitudes" aria-label="Historial de solicitudes">
            <h2>Mis solicitudes</h2>
            <?php foreach ($solicitudes as $solicitud): ?>
                <p><strong><?= $solicitud['tipo'] === 'descuento' ? 'Descuento del 30%' : 'Obsequio' ?></strong> · <?= (int) $solicitud['puntos'] ?> puntos · <?= htmlspecialchars(ucfirst($solicitud['estado']), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars(date('d/m/Y', strtotime((string) $solicitud['fecha_solicitud'])), ENT_QUOTES, 'UTF-8') ?></p>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <div class="beneficios-lista-cabecera">
        <h2>Notificaciones</h2>
        <?php if ($notificaciones !== []): ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_beneficios'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="accion_beneficio" value="leer_notificaciones">
                <button type="submit">Marcar todo como leído</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($notificaciones === []): ?>
        <p class="beneficios-vacio">Todavía no tienes notificaciones.</p>
    <?php else: ?>
        <div class="notificaciones-lista">
            <?php foreach ($notificaciones as $notificacion):
                $rutaImagen = trim((string) ($notificacion['imagen'] ?? ''));
                if ($rutaImagen === '' || strpos($rutaImagen, 'IMG/') !== 0 || strpos($rutaImagen, '..') !== false || !is_file(__DIR__ . '/' . $rutaImagen)) {
                    $rutaImagen = 'IMG/default.jpg';
                }
            ?>
                <article class="notificacion-beneficio">
                    <?php if ($notificacion['tipo'] === 'promocion'): ?>
                        <img src="<?= htmlspecialchars($rutaImagen, ENT_QUOTES, 'UTF-8') ?>" alt="Imagen de <?= htmlspecialchars((string) $notificacion['titulo'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                    <div class="notificacion-beneficio-contenido">
                        <span class="notificacion-beneficio-tipo"><?php
                            $etiquetasNotificacion = [
                                'promocion' => 'PROMOCIÓN DISPONIBLE',
                                'obsequio' => 'SOLICITUD DE OBSEQUIO',
                                'canje' => 'CANJE DE PUNTOS',
                            ];
                            echo htmlspecialchars($etiquetasNotificacion[$notificacion['tipo']] ?? 'BENEFICIO', ENT_QUOTES, 'UTF-8');
                        ?></span>
                        <h3><?= htmlspecialchars((string) $notificacion['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= nl2br(htmlspecialchars((string) $notificacion['descripcion'], ENT_QUOTES, 'UTF-8')) ?></p>
                        <?php if ($notificacion['precio'] !== null): ?><strong class="notificacion-precio">$<?= number_format((float) $notificacion['precio'], 0, ',', '.') ?> COP</strong><?php endif; ?>
                        <time datetime="<?= htmlspecialchars((string) $notificacion['fecha_creacion'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(date('d/m/Y', strtotime((string) $notificacion['fecha_creacion'])), ENT_QUOTES, 'UTF-8') ?></time>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/footer.php'; ?>