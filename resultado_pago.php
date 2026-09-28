<?php
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];
include("wompi_config.php");

if (!isset($_SESSION['usuario_id'], $_SESSION['pago_pendiente'])) {
    header('Location: inicio.php');
    exit();
}

$transaccionId = trim($_GET['id'] ?? '');
$pendiente = $_SESSION['pago_pendiente'];
$estado = 'ERROR';
$mensaje = 'No fue posible verificar el pago.';
$conservarPagoPendiente = false;

if ($transaccionId !== '' && WOMPI_PUBLIC_KEY !== '') {
    $curl = curl_init(wompiBaseUrl() . '/v1/transactions/' . rawurlencode($transaccionId));
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . WOMPI_PUBLIC_KEY],
        CURLOPT_TIMEOUT => 15
    ]);
    $respuesta = curl_exec($curl);
    curl_close($curl);
    $datos = json_decode($respuesta ?: '', true);
    $transaccion = $datos['data'] ?? [];
    $estado = $transaccion['status'] ?? 'ERROR';

    if ($estado === 'APPROVED' && ($transaccion['reference'] ?? '') === $pendiente['referencia'] &&
        (int) ($transaccion['amount_in_cents'] ?? 0) === (int) $pendiente['monto']) {
        $referencia = $pendiente['referencia'];
        $transaccionIdDb = (string) $transaccionId;
        $metodoPago = (string) ($transaccion['payment_method_type'] ?? 'WOMPI');
        $usuarioId = (int) $_SESSION['usuario_id'];
        $totalCompra = (int) $pendiente['monto'] / 100;
        $consultaPedido = mysqli_prepare($conexion, 'SELECT id FROM pedidos WHERE referencia_pago = ? LIMIT 1');
        $pedidoId = 0;

        if ($consultaPedido) {
            mysqli_stmt_bind_param($consultaPedido, 's', $referencia);
            mysqli_stmt_execute($consultaPedido);
            $resultadoPedido = mysqli_stmt_get_result($consultaPedido);
            $pedidoExistente = $resultadoPedido ? mysqli_fetch_assoc($resultadoPedido) : null;
            if ($pedidoExistente) {
                $pedidoId = (int) $pedidoExistente['id'];
            }
            mysqli_stmt_close($consultaPedido);
        }

        $pedidoGuardado = $pedidoId > 0;
        if (!$pedidoGuardado) {
            mysqli_begin_transaction($conexion);
            $insertarPedido = mysqli_prepare($conexion, "INSERT INTO pedidos (usuario_id, total, estado, referencia_pago, transaccion_id, metodo_pago) VALUES (?, ?, 'completado', ?, ?, ?)");
            if ($insertarPedido) {
                mysqli_stmt_bind_param($insertarPedido, 'iisss', $usuarioId, $totalCompra, $referencia, $transaccionIdDb, $metodoPago);
                $pedidoGuardado = mysqli_stmt_execute($insertarPedido);
                if ($pedidoGuardado) {
                    $pedidoId = (int) mysqli_insert_id($conexion);
                }
                mysqli_stmt_close($insertarPedido);
            }

            $buscarProducto = mysqli_prepare($conexion, 'SELECT id FROM productos WHERE nombre = ? LIMIT 1');
            $insertarDetalle = mysqli_prepare($conexion, 'INSERT INTO detalles_pedido (pedido_id, producto_id, producto_nombre, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)');
            if (!$buscarProducto || !$insertarDetalle) {
                $pedidoGuardado = false;
            }

            if ($pedidoGuardado) {
                foreach ($pendiente['carrito'] as $nombreProducto => $producto) {
                    $cantidad = max(1, (int) ($producto['cantidad'] ?? 1));
                    $precioUnitario = max(0, (int) ($producto['precio'] ?? 0));
                    $subtotal = $cantidad * $precioUnitario;
                    $productoId = null;

                    mysqli_stmt_bind_param($buscarProducto, 's', $nombreProducto);
                    if (mysqli_stmt_execute($buscarProducto)) {
                        $resultadoProducto = mysqli_stmt_get_result($buscarProducto);
                        $filaProducto = $resultadoProducto ? mysqli_fetch_assoc($resultadoProducto) : null;
                        if ($filaProducto) {
                            $productoId = (int) $filaProducto['id'];
                        }
                    } else {
                        $pedidoGuardado = false;
                        break;
                    }

                    mysqli_stmt_bind_param($insertarDetalle, 'iisiii', $pedidoId, $productoId, $nombreProducto, $cantidad, $precioUnitario, $subtotal);
                    if (!mysqli_stmt_execute($insertarDetalle)) {
                        $pedidoGuardado = false;
                        break;
                    }
                }
            }

            if ($buscarProducto) mysqli_stmt_close($buscarProducto);
            if ($insertarDetalle) mysqli_stmt_close($insertarDetalle);
            if ($pedidoGuardado) {
                mysqli_commit($conexion);
            } else {
                mysqli_rollback($conexion);
            }
        }

        if ($pedidoGuardado) {
            $yaEnHistorial = false;
            foreach ($_SESSION['historial'] ?? [] as $compraExistente) {
                if (($compraExistente['referencia'] ?? '') === $referencia) {
                    $yaEnHistorial = true;
                    break;
                }
            }
            if (!$yaEnHistorial) {
                $_SESSION['historial'][] = [
                    'productos' => $pendiente['carrito'],
                    'total' => $totalCompra,
                    'cantidad_total' => array_sum(array_column($pendiente['carrito'], 'cantidad')),
                    'fecha' => date('d/m/Y'),
                    'hora' => date('H:i:s'),
                    'metodo_pago' => $metodoPago,
                    'referencia' => $referencia
                ];
            }
            $_SESSION['carrito'] = [];
            guardarDatosUsuario($conexion);
            $mensaje = 'Pago aprobado. Tu compra quedó guardada en el historial.';
        } else {
            $estado = 'ERROR';
            $mensaje = 'El pago fue aprobado, pero no se pudo registrar la compra. Conservamos la referencia para poder revisarla.';
            $conservarPagoPendiente = true;
        }
    } elseif ($estado === 'PENDING') {
        $mensaje = 'El pago está pendiente de confirmación. Revisa nuevamente en unos momentos.';
    } else {
        $mensaje = 'El pago no fue aprobado. Tu carrito se conserva.';
    }
}

if (!$conservarPagoPendiente) {
    unset($_SESSION['pago_pendiente']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Resultado del pago</title><link rel="stylesheet" href="styles.css"></head>
<body>
<main class="resultado-pago">
    <h1><?= $estado === 'APPROVED' ? 'Pago aprobado' : 'Resultado del pago' ?></h1>
    <p><?= htmlspecialchars($mensaje) ?></p>
    <a href="<?= $estado === 'APPROVED' ? 'perfil.php' : 'ver_carrito.php' ?>" class="btn-pagar">Continuar</a>
</main>
</body>
</html>