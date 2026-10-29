<?php
function saldoPuntosUsuario($conexion, $usuarioId) {
    $consulta = mysqli_prepare($conexion, 'SELECT COALESCE(SUM(puntos), 0) AS saldo FROM puntos_movimientos WHERE usuario_id = ?');
    if (!$consulta) {
        return 0;
    }
    mysqli_stmt_bind_param($consulta, 'i', $usuarioId);
    $consultado = mysqli_stmt_execute($consulta);
    $resultado = $consultado ? mysqli_stmt_get_result($consulta) : false;
    $fila = $resultado ? mysqli_fetch_assoc($resultado) : null;
    mysqli_stmt_close($consulta);
    return max(0, (int) ($fila['saldo'] ?? 0));
}

function puntosDisponiblesUsuario($conexion, $usuarioId) {
    $saldo = saldoPuntosUsuario($conexion, $usuarioId);
    $consulta = mysqli_prepare($conexion, "SELECT COALESCE((SELECT SUM(puntos_usados) FROM pedidos WHERE usuario_id = ? AND estado IN ('pendiente', 'procesando_pago')), 0) + COALESCE((SELECT SUM(puntos) FROM solicitudes_beneficios WHERE usuario_id = ? AND estado = 'solicitado'), 0) AS reservados");
    if (!$consulta) {
        return $saldo;
    }
    mysqli_stmt_bind_param($consulta, 'ii', $usuarioId, $usuarioId);
    $consultado = mysqli_stmt_execute($consulta);
    $resultado = $consultado ? mysqli_stmt_get_result($consulta) : false;
    $fila = $resultado ? mysqli_fetch_assoc($resultado) : null;
    mysqli_stmt_close($consulta);
    return max(0, $saldo - (int) ($fila['reservados'] ?? 0));
}

function liberarSolicitudDescuentoPedido($conexion, $pedidoId) {
    $liberar = mysqli_prepare($conexion, "UPDATE solicitudes_beneficios SET estado = 'solicitado', pedido_id = NULL WHERE tipo = 'descuento' AND pedido_id = ? AND estado = 'reservado'");
    if (!$liberar) {
        return false;
    }
    mysqli_stmt_bind_param($liberar, 'i', $pedidoId);
    $liberada = mysqli_stmt_execute($liberar);
    mysqli_stmt_close($liberar);
    return $liberada;
}

function notificarOfertaNuevosUsuarios($conexion, $tipoOferta, $ofertaId, $titulo, $descripcion, $precio, $imagen) {
    $consulta = mysqli_prepare($conexion, "INSERT IGNORE INTO notificaciones_usuarios (usuario_id, tipo, oferta_tipo, oferta_id, titulo, descripcion, precio, imagen) SELECT id, 'promocion', ?, ?, ?, ?, ?, ? FROM usuarios WHERE rol = 'usuario' AND activo = 1");
    if (!$consulta) {
        return false;
    }
    mysqli_stmt_bind_param($consulta, 'sissds', $tipoOferta, $ofertaId, $titulo, $descripcion, $precio, $imagen);
    $notificado = mysqli_stmt_execute($consulta);
    mysqli_stmt_close($consulta);
    return $notificado;
}

function puntosPorCompra($totalPagado, $descuento = 0) {
    $valorCompra = max(0, (int) $totalPagado + (int) $descuento);
    return intdiv($valorCompra, 50000);
}

function registrarPuntosPedido($conexion, $usuarioId, $pedidoId, $total, $puntosUsados = 0, $descuento = 0) {
    $puntosGanados = puntosPorCompra($total, $descuento);
    if ($puntosGanados > 0) {
        $referenciaCompra = 'pedido:' . (int) $pedidoId . ':compra';
        $descripcionCompra = $puntosGanados . ' punto(s) por compra confirmada de $' . number_format((int) $total + (int) $descuento, 0, ',', '.') . ' COP.';
        $compra = mysqli_prepare($conexion, "INSERT IGNORE INTO puntos_movimientos (usuario_id, pedido_id, tipo, puntos, referencia, descripcion) VALUES (?, ?, 'compra', ?, ?, ?)");
        if (!$compra) {
            return false;
        }
        mysqli_stmt_bind_param($compra, 'iiiss', $usuarioId, $pedidoId, $puntosGanados, $referenciaCompra, $descripcionCompra);
        $compraRegistrada = mysqli_stmt_execute($compra);
        mysqli_stmt_close($compra);
        if (!$compraRegistrada) {
            return false;
        }
    }

    if ($puntosUsados > 0) {
        $referenciaCanje = 'pedido:' . (int) $pedidoId . ':canje';
        $descripcionCanje = 'Canje de puntos por descuento del 30%.';
        $canje = mysqli_prepare($conexion, "INSERT IGNORE INTO puntos_movimientos (usuario_id, pedido_id, tipo, puntos, referencia, descripcion) VALUES (?, ?, 'canje', ?, ?, ?)");
        if (!$canje) {
            return false;
        }
        $puntosNegativos = -abs((int) $puntosUsados);
        mysqli_stmt_bind_param($canje, 'iiiss', $usuarioId, $pedidoId, $puntosNegativos, $referenciaCanje, $descripcionCanje);
        $canjeRegistrado = mysqli_stmt_execute($canje);
        mysqli_stmt_close($canje);
        if (!$canjeRegistrado) {
            return false;
        }

        $actualizarSolicitud = mysqli_prepare($conexion, "UPDATE solicitudes_beneficios SET estado = 'completado', actualizado_en = CURRENT_TIMESTAMP WHERE tipo = 'descuento' AND pedido_id = ? AND estado = 'reservado'");
        if (!$actualizarSolicitud) {
            return false;
        }
        mysqli_stmt_bind_param($actualizarSolicitud, 'i', $pedidoId);
        $solicitudActualizada = mysqli_stmt_execute($actualizarSolicitud);
        mysqli_stmt_close($actualizarSolicitud);
        if (!$solicitudActualizada) {
            return false;
        }

        $tituloCanje = 'Puntos utilizados en tu compra';
        $descripcionCanje = 'Se usaron ' . (int) $puntosUsados . ' puntos en el pedido #' . (int) $pedidoId . '. Descuento aplicado: $' . number_format((int) $descuento, 0, ',', '.') . ' COP.';
        $notificacionCanje = mysqli_prepare($conexion, "INSERT IGNORE INTO notificaciones_usuarios (usuario_id, tipo, oferta_tipo, oferta_id, titulo, descripcion) VALUES (?, 'canje', 'pedido', ?, ?, ?)");
        if (!$notificacionCanje) {
            return false;
        }
        mysqli_stmt_bind_param($notificacionCanje, 'iiss', $usuarioId, $pedidoId, $tituloCanje, $descripcionCanje);
        $canjeNotificado = mysqli_stmt_execute($notificacionCanje);
        mysqli_stmt_close($notificacionCanje);
        if (!$canjeNotificado) {
            return false;
        }
    }

    return true;
}