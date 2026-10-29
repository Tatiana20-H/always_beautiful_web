<?php include("Seguridad.php"); ?>
<?php include("../conexion.php"); ?>
<?php $conexion = $GLOBALS['conexion']; ?>
<?php
if (empty($_SESSION['csrf_solicitud_beneficio_ventas'])) {
    $_SESSION['csrf_solicitud_beneficio_ventas'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion_venta'] ?? '') === 'resolver_beneficio') {
    $mensajeAccion = 'No se pudo resolver la solicitud.';
    $tokenAccion = $_POST['csrf_token'] ?? '';
    $solicitudId = filter_var($_POST['solicitud_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $decisionSolicitud = (string) ($_POST['decision_solicitud'] ?? '');
    $transaccionIniciada = is_string($tokenAccion) && hash_equals($_SESSION['csrf_solicitud_beneficio_ventas'], $tokenAccion) && $solicitudId !== false && in_array($decisionSolicitud, ['aprobar', 'rechazar'], true) && mysqli_begin_transaction($conexion);
    if ($transaccionIniciada) {
        $movimientoCreado = false;
        $buscarSolicitud = mysqli_prepare($conexion, "SELECT id, usuario_id, puntos FROM solicitudes_beneficios WHERE id = ? AND tipo = 'obsequio' AND estado = 'solicitado' FOR UPDATE");
        if ($buscarSolicitud) {
            mysqli_stmt_bind_param($buscarSolicitud, 'i', $solicitudId);
            $solicitudConsultada = mysqli_stmt_execute($buscarSolicitud);
            $resultadoSolicitud = $solicitudConsultada ? mysqli_stmt_get_result($buscarSolicitud) : false;
            $solicitud = $resultadoSolicitud ? mysqli_fetch_assoc($resultadoSolicitud) : null;
            mysqli_stmt_close($buscarSolicitud);
        } else {
            $solicitud = null;
        }
        if (!$solicitud) {
            $mensajeAccion = 'La solicitud ya fue atendida o no existe.';
            mysqli_rollback($conexion);
        } else {
            $clienteSolicitudId = (int) $solicitud['usuario_id'];
            $puntosSolicitud = (int) $solicitud['puntos'];
            $bloquearCliente = mysqli_prepare($conexion, 'SELECT id FROM usuarios WHERE id = ? FOR UPDATE');
            if ($bloquearCliente) {
                mysqli_stmt_bind_param($bloquearCliente, 'i', $clienteSolicitudId);
                $clienteBloqueado = mysqli_stmt_execute($bloquearCliente);
                $resultadoClienteBloqueado = $clienteBloqueado ? mysqli_stmt_get_result($bloquearCliente) : false;
                $clienteValido = $resultadoClienteBloqueado && mysqli_fetch_assoc($resultadoClienteBloqueado);
                mysqli_stmt_close($bloquearCliente);
            } else {
                $clienteValido = false;
            }
            if (!$clienteValido) {
                mysqli_rollback($conexion);
                $solicitudActualizada = false;
            } else {
            $nuevoEstadoSolicitud = $decisionSolicitud === 'aprobar' ? 'completado' : 'cancelado';
            $actualizarSolicitud = mysqli_prepare($conexion, 'UPDATE solicitudes_beneficios SET estado = ? WHERE id = ? AND estado = \'solicitado\'');
            $solicitudActualizada = false;
            if ($actualizarSolicitud) {
                mysqli_stmt_bind_param($actualizarSolicitud, 'si', $nuevoEstadoSolicitud, $solicitudId);
                $solicitudActualizada = mysqli_stmt_execute($actualizarSolicitud) && mysqli_stmt_affected_rows($actualizarSolicitud) === 1;
                mysqli_stmt_close($actualizarSolicitud);
            }
            if (!$solicitudActualizada) {
                mysqli_rollback($conexion);
            } elseif ($decisionSolicitud === 'aprobar' && puntosDisponiblesUsuario($conexion, $clienteSolicitudId) >= $puntosSolicitud) {
                $referenciaObsequio = 'obsequio:solicitud:' . $solicitudId;
                $descripcionMovimiento = 'Obsequio aprobado por administración.';
                $descontarPuntos = mysqli_prepare($conexion, "INSERT INTO puntos_movimientos (usuario_id, tipo, puntos, referencia, descripcion) VALUES (?, 'obsequio', ?, ?, ?)");
                if ($descontarPuntos) {
                    $puntosNegativos = -$puntosSolicitud;
                    mysqli_stmt_bind_param($descontarPuntos, 'iiss', $clienteSolicitudId, $puntosNegativos, $referenciaObsequio, $descripcionMovimiento);
                    $movimientoCreado = mysqli_stmt_execute($descontarPuntos);
                    mysqli_stmt_close($descontarPuntos);
                } else {
                    $movimientoCreado = false;
                }
                $tituloResolucion = 'Tu obsequio fue aprobado';
                $textoResolucion = 'Tu solicitud de obsequio fue aprobada por el equipo. Se descontaron ' . $puntosSolicitud . ' puntos.';
            } elseif ($decisionSolicitud === 'aprobar') {
                $mensajeAccion = 'El cliente ya no tiene puntos suficientes. La solicitud sigue pendiente.';
                mysqli_rollback($conexion);
                $solicitudActualizada = false;
            } else {
                $movimientoCreado = true;
                $tituloResolucion = 'Actualización de tu solicitud de obsequio';
                $textoResolucion = 'El equipo no pudo aprobar tu solicitud de obsequio en esta ocasión. Tus puntos siguen disponibles.';
            }
            if ($solicitudActualizada) {
                $notificarResolucion = mysqli_prepare($conexion, "INSERT INTO notificaciones_usuarios (usuario_id, tipo, oferta_tipo, oferta_id, titulo, descripcion) VALUES (?, 'obsequio', 'solicitud', ?, ?, ?)");
                if ($notificarResolucion) {
                    mysqli_stmt_bind_param($notificarResolucion, 'iiss', $clienteSolicitudId, $solicitudId, $tituloResolucion, $textoResolucion);
                    $notificacionCreada = mysqli_stmt_execute($notificarResolucion);
                    mysqli_stmt_close($notificarResolucion);
                } else {
                    $notificacionCreada = false;
                }
                if ($movimientoCreado && $notificacionCreada && mysqli_commit($conexion)) {
                    $mensajeAccion = $decisionSolicitud === 'aprobar' ? 'Obsequio aprobado, puntos descontados y cliente notificado.' : 'Solicitud rechazada y cliente notificado.';
                } else {
                    mysqli_rollback($conexion);
                }
            }
            }
        }
    }
    $_SESSION['flash_beneficio_ventas'] = $mensajeAccion;
    $estadoRedireccion = is_string($_GET['estado'] ?? null) ? $_GET['estado'] : 'todos';
    header('Location: ventas.php?estado=' . rawurlencode($estadoRedireccion), true, 303);
    exit();
}
$mensajeBeneficioVentas = (string) ($_SESSION['flash_beneficio_ventas'] ?? '');
unset($_SESSION['flash_beneficio_ventas']);
?>
<?php include("header-admin.php"); ?>

<style>
    .ventas-container {
        padding: 30px;
        background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
        min-height: calc(100vh - 100px);
    }

    .ventas-container h2 {
        color: #dc3545;
        font-size: 2em;
        margin-bottom: 30px;
        font-weight: bold;
    }

    .tabla-ventas {
        background: white;
        border-radius: 8px;
        overflow-x: auto;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }

    .tabla-ventas table {
        min-width: 1120px;
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .tabla-ventas th {
        background: linear-gradient(135deg, #dc3545 0%, #e85b5b 100%);
        color: white;
        padding: 20px;
        text-align: left;
        font-weight: 600;
        border: none;
    }

    .tabla-ventas td {
        padding: 18px 20px;
        border-bottom: 1px solid #e9ecef;
        color: #555;
    }

    .tabla-ventas tr:hover {
        background: #f8f9fa;
    }

    .tabla-ventas tr:last-child td {
        border-bottom: none;
    }

    .badge-accion {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85em;
        font-weight: bold;
        background: #e85b5b;
        color: white;
    }

    .sin-ventas {
        text-align: center;
        padding: 50px;
        color: #999;
        background: white;
        border-radius: 15px;
    }

    .estadisticas-ventas {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        text-align: center;
        border-left: 5px solid #dc3545;
        color: inherit;
        display: block;
        text-decoration: none;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .stat-card:hover, .stat-card.activa { box-shadow: 0 8px 22px rgba(58, 39, 34, .16); transform: translateY(-2px); }
    .stat-card:focus-visible { outline: 3px solid #27675d; outline-offset: 3px; }
    .stat-card.activa { outline: 2px solid #27675d; outline-offset: 2px; }
    .tabla-ventas td { vertical-align: top; }
    .tabla-ventas .cliente-ventas strong, .tabla-ventas .cliente-ventas small { display: block; }
    .tabla-ventas .cliente-ventas small { margin-top: 4px; overflow-wrap: anywhere; }
    .pedido-lineas { display: grid; gap: 7px; min-width: 230px; }
    .pedido-linea { border-bottom: 1px solid #eee8e0; padding-bottom: 7px; }
    .pedido-linea:last-child { border-bottom: 0; padding-bottom: 0; }
    .pedido-linea small, .pedido-entrega small { color: #716a63; display: block; margin-top: 3px; }
    .pedido-entrega { line-height: 1.45; min-width: 190px; }
    .badge-estado { border-radius: 999px; display: inline-block; font-size: 12px; font-weight: 700; padding: 5px 9px; white-space: nowrap; }
    .badge-estado.pendiente { background: #fff1d2; color: #795300; }
    .badge-estado.completado { background: #e4f2e8; color: #27623a; }
    .badge-estado.otro { background: #f0ece8; color: #5e5148; }
    .badge-puntos { background: #e4f2e8; border-radius: 4px; color: #27623a; display: inline-block; font-weight: 700; padding: 5px 8px; white-space: nowrap; }
    .form-resolver-beneficio { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 7px; }
    .form-resolver-beneficio input { display: none; }
    .form-resolver-beneficio button { background: #27623a; border: 0; border-radius: 4px; color: #fff; cursor: pointer; font-size: 11px; font-weight: 700; padding: 7px 8px; width: auto; }
    .form-resolver-beneficio button.rechazar { background: #8c4238; }
    .solicitud-obsequio-pendiente { color: #795300; display: block; font-size: 12px; margin-top: 8px; }
    .ventas-aviso-beneficio { background: #e4f2e8; border-left: 4px solid #27623a; color: #275b37; margin: 0 0 18px; padding: 12px 14px; }
    .ventas-filtro { color: #5c514b; font-size: 14px; margin: -12px 0 18px; }

    .stat-card h3 {
        margin-top: 0;
        color: #555;
        font-size: 0.95em;
    }

    .stat-numero {
        font-size: 2em;
        font-weight: 700;
        color: #dc3545;
        margin: 10px 0;
    }

    @media (max-width: 768px) {
        .ventas-container {
            padding: 15px;
        }
    }
</style>

<div class="ventas-container">
    <h2>Registro de Ventas</h2>

    <?php
    $estadisticas = mysqli_query($conexion, "SELECT COUNT(*) AS total, SUM(estado IN ('pendiente', 'procesando_pago')) AS pendientes, SUM(estado = 'completado') AS completados, COALESCE(SUM(CASE WHEN estado = 'completado' THEN total ELSE 0 END), 0) AS monto_completado FROM pedidos");
    $resumenVentas = $estadisticas ? mysqli_fetch_assoc($estadisticas) : [];
    $total_ventas = (int) ($resumenVentas['total'] ?? 0);
    $ventas_pendientes = (int) ($resumenVentas['pendientes'] ?? 0);
    $ventas_completadas = (int) ($resumenVentas['completados'] ?? 0);
    $monto_total = (int) ($resumenVentas['monto_completado'] ?? 0);
    $filtro = is_string($_GET['estado'] ?? null) ? $_GET['estado'] : 'todos';
    if (!in_array($filtro, ['todos', 'pendientes', 'completados', 'monto'], true)) {
        $filtro = 'todos';
    }
    $condiciones = [
        'todos' => '',
        'pendientes' => " WHERE p.estado IN ('pendiente', 'procesando_pago')",
        'completados' => " WHERE p.estado = 'completado'",
        'monto' => " WHERE p.estado = 'completado'",
    ];
    $resultadoPedidos = mysqli_query($conexion, "SELECT p.id, p.usuario_id, p.total, p.descuento, p.puntos_usados, p.estado, p.metodo_pago, p.referencia_pago, p.fecha_pedido, p.fecha_pago, p.datos_entrega, p.observacion_inventario, u.nombre AS usuario_nombre, u.correo AS usuario_correo, GREATEST(0, COALESCE((SELECT SUM(pm.puntos) FROM puntos_movimientos pm WHERE pm.usuario_id = p.usuario_id), 0) - COALESCE((SELECT SUM(pp.puntos_usados) FROM pedidos pp WHERE pp.usuario_id = p.usuario_id AND pp.estado IN ('pendiente', 'procesando_pago')), 0) - COALESCE((SELECT SUM(sb.puntos) FROM solicitudes_beneficios sb WHERE sb.usuario_id = p.usuario_id AND sb.estado = 'solicitado'), 0)) AS puntos_cliente FROM pedidos p LEFT JOIN usuarios u ON u.id = p.usuario_id" . $condiciones[$filtro] . ' ORDER BY p.fecha_pedido DESC, p.id DESC');
    ?>

    <div class="estadisticas-ventas">
        <a class="stat-card <?= $filtro === 'todos' ? 'activa' : '' ?>" href="ventas.php?estado=todos" aria-current="<?= $filtro === 'todos' ? 'page' : 'false' ?>">
            <h3>Total de Pedidos</h3>
            <div class="stat-numero"><?php echo $total_ventas; ?></div>
        </a>
        <a class="stat-card <?= $filtro === 'pendientes' ? 'activa' : '' ?>" href="ventas.php?estado=pendientes" style="border-left-color: #ffc107;" aria-current="<?= $filtro === 'pendientes' ? 'page' : 'false' ?>">
            <h3>Pendientes</h3>
            <div class="stat-numero" style="color: #ffc107;"><?php echo $ventas_pendientes; ?></div>
        </a>
        <a class="stat-card <?= $filtro === 'completados' ? 'activa' : '' ?>" href="ventas.php?estado=completados" style="border-left-color: #28a745;" aria-current="<?= $filtro === 'completados' ? 'page' : 'false' ?>">
            <h3>Completados</h3>
            <div class="stat-numero" style="color: #28a745;"><?php echo $ventas_completadas; ?></div>
        </a>
        <a class="stat-card <?= $filtro === 'monto' ? 'activa' : '' ?>" href="ventas.php?estado=monto" style="border-left-color: #17a2b8;" aria-current="<?= $filtro === 'monto' ? 'page' : 'false' ?>">
            <h3>Monto Total</h3>
            <div class="stat-numero" style="color: #17a2b8;">$<?php echo number_format($monto_total, 0, ',', '.'); ?></div>
        </a>
    </div>

    <p class="ventas-filtro">
        <?php if ($filtro === 'pendientes'): ?>Pedidos pendientes de pago, incluidos los que ya tienen datos de entrega guardados.
        <?php elseif ($filtro === 'completados' || $filtro === 'monto'): ?>Pedidos cuyo pago fue confirmado.
        <?php else: ?>Todos los pedidos, pendientes y pagados.<?php endif; ?>
    </p>

    <?php if ($resultadoPedidos && mysqli_num_rows($resultadoPedidos) > 0): ?>
        <div class="tabla-ventas">
            <table>
                <thead>
                    <tr>
                        <th>N.º</th>
                        <th>Cliente</th>
                        <th>Puntos</th>
                        <th>Estado</th>
                        <th>Productos</th>
                        <th>Entrega</th>
                        <th>Total</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $clientesObsequioMostrado = []; while ($pedido = mysqli_fetch_assoc($resultadoPedidos)): ?>
                        <tr>
                            <td><strong><?= (int) $pedido['id'] ?></strong></td>
                            <td class="cliente-ventas"><strong><?= htmlspecialchars((string) ($pedido['usuario_nombre'] ?: 'Usuario'), ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars((string) ($pedido['usuario_correo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></td>
                            <td>
                                <span class="badge-puntos"><?= max(0, (int) $pedido['puntos_cliente']) ?> puntos</span>
                                <?php
                                $solicitudesObsequio = [];
                                $idClienteObsequios = (int) $pedido['usuario_id'];
                                if ($idClienteObsequios > 0 && !isset($clientesObsequioMostrado[$idClienteObsequios])) {
                                    $clientesObsequioMostrado[$idClienteObsequios] = true;
                                    $buscarObsequiosCliente = mysqli_prepare($conexion, "SELECT id, puntos FROM solicitudes_beneficios WHERE usuario_id = ? AND tipo = 'obsequio' AND estado = 'solicitado' ORDER BY fecha_solicitud ASC");
                                } else {
                                    $buscarObsequiosCliente = false;
                                }
                                if ($buscarObsequiosCliente) {
                                    mysqli_stmt_bind_param($buscarObsequiosCliente, 'i', $idClienteObsequios);
                                    if (mysqli_stmt_execute($buscarObsequiosCliente)) {
                                        $resultadoObsequiosCliente = mysqli_stmt_get_result($buscarObsequiosCliente);
                                        $solicitudesObsequio = $resultadoObsequiosCliente ? mysqli_fetch_all($resultadoObsequiosCliente, MYSQLI_ASSOC) : [];
                                    }
                                    mysqli_stmt_close($buscarObsequiosCliente);
                                }
                                ?>
                                <?php foreach ($solicitudesObsequio as $solicitudObsequio): ?>
                                    <span class="solicitud-obsequio-pendiente">Solicita obsequio · <?= (int) $solicitudObsequio['puntos'] ?> puntos</span>
                                    <form method="post" class="form-resolver-beneficio">
                                        <input type="hidden" name="accion_venta" value="resolver_beneficio">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_solicitud_beneficio_ventas'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="solicitud_id" value="<?= (int) $solicitudObsequio['id'] ?>">
                                        <button type="submit" name="decision_solicitud" value="aprobar">Aprobar</button>
                                        <button type="submit" name="decision_solicitud" value="rechazar" class="rechazar">Rechazar</button>
                                    </form>
                                <?php endforeach; ?>
                            </td>
                            <td><span class="badge-estado <?= $pedido['estado'] === 'completado' ? 'completado' : (in_array($pedido['estado'], ['pendiente', 'procesando_pago'], true) ? 'pendiente' : 'otro') ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) $pedido['estado'])), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if (!empty($pedido['observacion_inventario'])): ?><small><?= htmlspecialchars((string) $pedido['observacion_inventario'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                            </td>
                            <td>
                                <div class="pedido-lineas">
                                    <?php
                                    $idPedidoActual = (int) $pedido['id'];
                                    $consultaDetalles = mysqli_prepare($conexion, 'SELECT producto_nombre, cantidad, precio_unitario, subtotal FROM detalles_pedido WHERE pedido_id = ? ORDER BY id');
                                    $detallesPedido = [];
                                    if ($consultaDetalles) {
                                        mysqli_stmt_bind_param($consultaDetalles, 'i', $idPedidoActual);
                                        if (mysqli_stmt_execute($consultaDetalles)) {
                                            $resultadoDetalles = mysqli_stmt_get_result($consultaDetalles);
                                            $detallesPedido = $resultadoDetalles ? mysqli_fetch_all($resultadoDetalles, MYSQLI_ASSOC) : [];
                                        }
                                        mysqli_stmt_close($consultaDetalles);
                                    }
                                    ?>
                                    <?php foreach ($detallesPedido as $detalle): ?>
                                        <div class="pedido-linea">
                                            <strong><?= htmlspecialchars((string) $detalle['producto_nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <small><?= (int) $detalle['cantidad'] ?> × $<?= number_format((int) $detalle['precio_unitario'], 0, ',', '.') ?> = $<?= number_format((int) $detalle['subtotal'], 0, ',', '.') ?> COP</small>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if ($detallesPedido === []): ?><span>Sin detalle disponible</span><?php endif; ?>
                                </div>
                            </td>
                            <td class="pedido-entrega">
                                <?php $entrega = json_decode((string) ($pedido['datos_entrega'] ?? ''), true); $entrega = is_array($entrega) ? $entrega : []; ?>
                                <?php if ($entrega !== []): ?>
                                    <?= htmlspecialchars((string) ($entrega['direccion'] ?? 'Dirección pendiente'), ENT_QUOTES, 'UTF-8') ?>
                                    <small><?= htmlspecialchars((string) ($entrega['ciudad'] ?? ''), ENT_QUOTES, 'UTF-8') ?><?= !empty($entrega['departamento']) ? ', ' . htmlspecialchars((string) $entrega['departamento'], ENT_QUOTES, 'UTF-8') : '' ?></small>
                                    <small><?= htmlspecialchars((string) ($entrega['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?><?= !empty($entrega['telefono']) ? ' · ' . htmlspecialchars((string) $entrega['telefono'], ENT_QUOTES, 'UTF-8') : '' ?></small>
                                <?php else: ?>Pendiente de datos<?php endif; ?>
                            </td>
                            <td><strong>$<?= number_format((int) $pedido['total'], 0, ',', '.') ?> COP</strong><?php if ((int) $pedido['descuento'] > 0): ?><small>Descuento: $<?= number_format((int) $pedido['descuento'], 0, ',', '.') ?> · <?= (int) $pedido['puntos_usados'] ?> puntos</small><?php endif; ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) (($pedido['fecha_pago'] ?: $pedido['fecha_pedido'])))), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php
    else:
    ?>
        <div class="sin-ventas">
            <h3>No hay pedidos en esta vista</h3>
            <p>Prueba otra categoría de pedidos.</p>
        </div>
    <?php
    endif;
    ?>
</div>