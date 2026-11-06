<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/stripe_config.php';

$pagado = false;
$mensaje = 'No se pudo verificar el pago. Si ya completaste el cobro, contacta con soporte antes de volver a pagar.';
$pendiente = $_SESSION['pago_pendiente'] ?? null;
$sessionId = trim((string) ($_GET['session_id'] ?? ''));

if (isset($_GET['cancel'])) {
    $mensaje = 'El pago fue cancelado. Tu carrito se conserva.';
} elseif (!isset($_SESSION['usuario_id'])) {
    $mensaje = 'Inicia sesión con la cuenta que realizó la compra para consultar su resultado.';
} elseif (!$pendiente || $sessionId === '' || $sessionId !== ($pendiente['stripe_session_id'] ?? '')) {
    $mensaje = 'No hay un pago pendiente asociado a esta sesión.';
} elseif (stripeSecretKey() === '') {
    $mensaje = 'Stripe no está configurado en el servidor.';
} else {
    $resultado = stripeRequest('GET', '/v1/checkout/sessions/' . rawurlencode($sessionId));
    $checkout = $resultado['data'] ?? [];
    $usuarioId = (string) ($_SESSION['usuario_id'] ?? '');

    if (!$resultado['success']) {
        error_log('Stripe Checkout verification error: ' . ($resultado['error'] ?? 'respuesta inválida'));
        $mensaje = 'Stripe no está disponible para verificar el pago en este momento. Inténtalo de nuevo.';
    } elseif (($checkout['payment_status'] ?? '') === 'paid'
        && (string) ($checkout['metadata']['usuario_id'] ?? '') === $usuarioId
        && (string) ($checkout['client_reference_id'] ?? '') === $usuarioId
        && (int) ($checkout['amount_total'] ?? 0) === (int) $pendiente['total']
        && strtolower((string) ($checkout['currency'] ?? '')) === 'cop') {
        $pagado = true;
        $correoCompraEnviado = false;
        $conexion->begin_transaction();
        $usuarioIdInt = (int) $_SESSION['usuario_id'];
        $transaccionStripe = is_string($checkout['payment_intent'] ?? null) ? $checkout['payment_intent'] : null;
        $metodoPago = 'Stripe';
        $stmtPedido = $conexion->prepare("INSERT INTO pedidos (usuario_id, total, estado, referencia_pago, transaccion_id, metodo_pago) VALUES (?, ?, 'completado', ?, ?, ?)");
        $totalCompra = (int) $pendiente['total'];
        if (!$stmtPedido) {
            $conexion->rollback();
            $pagado = false;
            $mensaje = 'No se pudo registrar la compra. Contacta con soporte.';
        } else {
        $stmtPedido->bind_param('iisss', $usuarioIdInt, $totalCompra, $sessionId, $transaccionStripe, $metodoPago);
        $pedidoNuevo = $stmtPedido->execute();
        $errorPedido = $stmtPedido->errno;
        $pedidoId = $conexion->insert_id;
        $stmtPedido->close();
        $pedidoDuplicado = !$pedidoNuevo && $errorPedido === 1062;

        if ($pedidoNuevo) {
            $detalleFallido = false;
            foreach ($pendiente['carrito'] as $nombreProducto => $productoCompra) {
                $cantidad = (int) ($productoCompra['cantidad'] ?? 0);
                $precioUnitario = (int) ($productoCompra['precio'] ?? 0);
                $subtotal = $cantidad * $precioUnitario;
                $stmtProducto = $conexion->prepare('SELECT id FROM productos WHERE nombre = ? LIMIT 1');
                $stmtProducto->bind_param('s', $nombreProducto);
                $stmtProducto->execute();
                $productoEncontrado = $stmtProducto->get_result()->fetch_assoc();
                $stmtProducto->close();
                $productoId = $productoEncontrado ? (int) $productoEncontrado['id'] : null;
                $stmtDetalle = $conexion->prepare('INSERT INTO detalles_pedido (pedido_id, producto_id, producto_nombre, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)');
                if (!$stmtDetalle) {
                    $detalleFallido = true;
                    break;
                }
                $stmtDetalle->bind_param('iisiii', $pedidoId, $productoId, $nombreProducto, $cantidad, $precioUnitario, $subtotal);
                $detalleGuardado = $stmtDetalle->execute();
                $stmtDetalle->close();
                if (!$detalleGuardado) {
                    $detalleFallido = true;
                    break;
                }

                if ($productoId !== null) {
                    $stmtStock = $conexion->prepare('UPDATE productos SET stock = GREATEST(stock - ?, 0) WHERE id = ?');
                    $stmtStock->bind_param('ii', $cantidad, $productoId);
                    $stmtStock->execute();
                    $stmtStock->close();
                }
            }

            if ($detalleFallido) {
                $conexion->rollback();
                $pagado = false;
                $mensaje = 'El pago se confirmó, pero no se pudo registrar el detalle. Contacta con soporte.';
            }

            if ($pagado) {
            $stmtPuntos = $conexion->prepare('SELECT puntos, puntos_resto FROM usuarios WHERE id = ? FOR UPDATE');
            $stmtPuntos->bind_param('i', $usuarioIdInt);
            $stmtPuntos->execute();
            $saldo = $stmtPuntos->get_result()->fetch_assoc();
            $stmtPuntos->close();
            $acumulado = (int) ($saldo['puntos_resto'] ?? 0) + $totalCompra;
            $puntosGanados = intdiv($acumulado, 50000);
            $resto = $acumulado % 50000;
            $stmtActualizarPuntos = $conexion->prepare('UPDATE usuarios SET puntos = puntos + ?, puntos_resto = ? WHERE id = ?');
            $stmtActualizarPuntos->bind_param('iii', $puntosGanados, $resto, $usuarioIdInt);
            $stmtActualizarPuntos->execute();
            $stmtActualizarPuntos->close();
            $correoCompraEnviado = true;
            }
        } elseif (!$pedidoDuplicado) {
            $conexion->rollback();
            $pagado = false;
            $mensaje = 'El pago se confirmó, pero no se pudo registrar la compra. Contacta con soporte antes de volver a pagar.';
        }

        if ($pagado) {
            $conexion->commit();
            $historialExiste = false;
            foreach ($_SESSION['historial'] ?? [] as $compraRegistrada) {
                if (($compraRegistrada['referencia'] ?? '') === $sessionId) {
                    $historialExiste = true;
                    break;
                }
            }
            if (!$historialExiste) {
                $_SESSION['historial'][] = [
                'productos' => $pendiente['carrito'],
                'total' => (int) $pendiente['total'],
                'cantidad_total' => array_sum(array_column($pendiente['carrito'], 'cantidad')),
                'fecha' => date('d/m/Y'),
                'hora' => date('H:i:s'),
                'metodo_pago' => 'Stripe',
                'referencia' => $sessionId,
                ];
            }
            $_SESSION['carrito'] = [];
            guardarDatosUsuario($conexion);
            if ($correoCompraEnviado) {
                $stmtUsuario = $conexion->prepare('SELECT nombre, correo FROM usuarios WHERE id = ? LIMIT 1');
                $stmtUsuario->bind_param('i', $usuarioIdInt);
                $stmtUsuario->execute();
                $usuarioCompra = $stmtUsuario->get_result()->fetch_assoc();
                $stmtUsuario->close();
                if ($usuarioCompra) {
                    $lineasCompra = [];
                    foreach ($pendiente['carrito'] as $nombreProducto => $productoCompra) {
                        $lineasCompra[] = $nombreProducto . ' x ' . (int) $productoCompra['cantidad'];
                    }
                    enviarNotificacion($usuarioCompra['correo'], 'Compra confirmada - Always Beautiful', "Hola {$usuarioCompra['nombre']},\n\nConfirmamos tu compra por $" . number_format($totalCompra, 0, ',', '.') . " COP.\n\n" . implode("\n", $lineasCompra) . "\n\nGracias por comprar en Always Beautiful.");
                }
            }
        }
        }

        if ($pagado) {
            unset($_SESSION['pago_pendiente']);
            $mensaje = 'Pago aprobado. Tu compra quedó guardada y tus puntos se actualizaron.';
        }
    } elseif (($checkout['payment_status'] ?? '') === 'unpaid') {
        $mensaje = 'El pago todavía está pendiente de confirmación. Puedes volver a consultar en unos momentos.';
    } else {
        $mensaje = 'El pago no fue aprobado. Tu carrito se conserva.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Resultado del pago</title><link rel="stylesheet" href="styles.css"></head>
<body>
<main class="resultado-pago">
    <h1><?= $pagado ? 'Pago aprobado' : 'Resultado del pago' ?></h1>
    <p><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
    <a href="<?= $pagado ? 'perfil.php' : 'ver_carrito.php' ?>" class="btn-pagar">Continuar</a>
</main>
</body>
</html>