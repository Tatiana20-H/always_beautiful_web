<?php
session_start();
include __DIR__ . '/conexion.php';
$conexion = $GLOBALS['conexion'];
require_once __DIR__ . '/wompi_config.php';
require_once __DIR__ . '/wompi_pago.php';
require_once __DIR__ . '/stripe_config.php';
require_once __DIR__ . '/stripe_pago.php';

if (isset($_GET['session_id']) || isset($_GET['cancelled'])) {
    if (strtolower((string) ($_SESSION['rol'] ?? '')) === 'admin') {
        header('Location: Administrador/index.php');
        exit();
    }
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: inicio.php');
        exit();
    }
    $stripeEstado = 'PENDING';
    $stripeMensaje = 'Stripe aún no ha confirmado el pago. El pedido conserva su estado actual.';
    $stripeSesion = null;
    if (isset($_GET['session_id'])) {
        $stripeSesion = stripeObtenerSesionCheckout($_GET['session_id']);
        if ($stripeSesion && stripeAplicarSesionVerificada($conexion, $stripeSesion)) {
            $referenciaStripe = (string) ($stripeSesion['client_reference_id'] ?? '');
            $usuarioPedido = mysqli_prepare($conexion, 'SELECT id, usuario_id, total FROM pedidos WHERE referencia_pago = ? LIMIT 1');
            $pedidoPropio = null;
            if ($usuarioPedido) {
                mysqli_stmt_bind_param($usuarioPedido, 's', $referenciaStripe);
                if (mysqli_stmt_execute($usuarioPedido)) {
                    $resultadoUsuarioPedido = mysqli_stmt_get_result($usuarioPedido);
                    $filaPedido = $resultadoUsuarioPedido ? mysqli_fetch_assoc($resultadoUsuarioPedido) : null;
                    if ($filaPedido && (int) $filaPedido['usuario_id'] === (int) $_SESSION['usuario_id']) {
                        $pedidoPropio = $filaPedido;
                    }
                }
                mysqli_stmt_close($usuarioPedido);
            }
            if ($pedidoPropio) {
                $facturasEnviadas = stripeEnviarFacturasPedido($conexion, $referenciaStripe);
                if (!$facturasEnviadas) {
                    error_log('No se pudieron enviar todos los correos de factura al volver de Stripe.');
                }
                if (!empty($_SESSION['pagos_confirmados'][$referenciaStripe])) {
                    $_SESSION['notificacion_pago'] = 'Pago exitoso';
                    header('Location: index.php', true, 303);
                    exit();
                }
                $pendienteStripe = $_SESSION['pago_pendiente'] ?? null;
                $carritoPedido = is_array($pendienteStripe) && ($pendienteStripe['referencia'] ?? null) === $referenciaStripe
                    ? ($pendienteStripe['carrito'] ?? [])
                    : [];
                if (!is_array($carritoPedido)) {
                    $carritoPedido = [];
                }
                $yaEnHistorial = false;
                foreach ($_SESSION['historial'] ?? [] as $compraExistente) {
                    if (($compraExistente['referencia'] ?? '') === $referenciaStripe) {
                        $yaEnHistorial = true;
                        break;
                    }
                }
                if (!$yaEnHistorial && $carritoPedido !== []) {
                    $cantidadesCompradas = array_map(static function ($linea) {
                        return (int) ($linea['cantidad'] ?? 0);
                    }, $carritoPedido);
                    $_SESSION['historial'][] = [
                        'productos' => $carritoPedido,
                        'total' => (int) $pedidoPropio['total'],
                        'cantidad_total' => array_sum($cantidadesCompradas),
                        'fecha' => date('d/m/Y'),
                        'hora' => date('H:i:s'),
                        'metodo_pago' => 'stripe',
                        'referencia' => $referenciaStripe,
                    ];
                }
                $_SESSION['carrito'] = [];
                guardarDatosUsuario($conexion);
                unset($_SESSION['pago_pendiente']);
                $_SESSION['pagos_confirmados'][$referenciaStripe] = true;
                $_SESSION['notificacion_pago'] = 'Pago exitoso';
                header('Location: index.php', true, 303);
                exit();
            }
            $stripeEstado = 'NO_VERIFICADO';
            $stripeMensaje = 'El pago fue procesado, pero no se pudo validar la cuenta asociada al pedido.';
        } elseif ($stripeSesion && ($stripeSesion['payment_status'] ?? null) === 'unpaid') {
            $stripeEstado = 'DECLINED';
            $stripeMensaje = 'Stripe todavía no reporta el pago completado. El carrito se conserva.';
        }
    } else {
        $stripeEstado = 'DECLINED';
        $stripeMensaje = 'El checkout fue cancelado. El carrito se conserva.';
    }
    $stripeTitulo = $stripeEstado === 'APPROVED' ? 'Pago aprobado' : ($stripeEstado === 'DECLINED' ? 'Pago no completado' : 'Pago pendiente');
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head><meta charset="UTF-8"><title>Resultado del pago</title><link rel="stylesheet" href="styles.css"></head>
    <body><main class="resultado-pago">
        <h1><?= htmlspecialchars($stripeTitulo, ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($stripeMensaje, ENT_QUOTES, 'UTF-8') ?></p>
        <a href="<?= $stripeEstado === 'APPROVED' ? 'perfil.php' : 'ver_carrito.php' ?>" class="btn-pagar">Continuar</a>
    </main></body>
    </html>
    <?php
    exit();
}

$estado = 'NO_VERIFICADO';
$mensaje = 'No fue posible verificar el resultado con Wompi. El pedido conserva su estado actual.';
$transaccionId = is_string($_GET['id'] ?? null) ? trim($_GET['id']) : '';

if (preg_match('/^[A-Za-z0-9-]{1,100}$/D', $transaccionId) && wompiCredencialesSandboxDisponibles()) {
    $transaccion = wompiObtenerTransaccion($transaccionId);
    if ($transaccion && ($transaccion['id'] ?? null) === $transaccionId) {
        $estado = (string) ($transaccion['status'] ?? 'NO_VERIFICADO');
        $sincronizado = wompiAplicarTransaccionVerificada($conexion, $transaccion);
        if ($sincronizado) {
            $consultaEstadoPedido = mysqli_prepare($conexion, 'SELECT estado FROM pedidos WHERE referencia_pago = ? LIMIT 1');
            if ($consultaEstadoPedido) {
                $referenciaVerificada = (string) ($transaccion['reference'] ?? '');
                mysqli_stmt_bind_param($consultaEstadoPedido, 's', $referenciaVerificada);
                if (mysqli_stmt_execute($consultaEstadoPedido)) {
                    $resultadoEstadoPedido = mysqli_stmt_get_result($consultaEstadoPedido);
                    $filaEstadoPedido = $resultadoEstadoPedido ? mysqli_fetch_assoc($resultadoEstadoPedido) : null;
                    $estado = match ($filaEstadoPedido['estado'] ?? '') {
                        'completado' => 'APPROVED',
                        'rechazado' => in_array($estado, ['DECLINED', 'VOIDED', 'ERROR'], true) ? $estado : 'DECLINED',
                        'pendiente', 'procesando_pago' => 'PENDING',
                        default => 'NO_VERIFICADO',
                    };
                } else {
                    $estado = 'NO_VERIFICADO';
                }
                mysqli_stmt_close($consultaEstadoPedido);
            } else {
                $estado = 'NO_VERIFICADO';
            }
            $mensajesEstado = [
                'APPROVED' => 'Wompi confirmó el pago. El pedido existente fue actualizado.',
                'PENDING' => 'Wompi reporta el pago pendiente. El pedido aún no está aprobado.',
                'DECLINED' => 'Wompi rechazó el pago. El carrito se conserva.',
                'VOIDED' => 'Wompi anuló el pago. El carrito se conserva.',
                'ERROR' => 'Wompi reportó un error en el pago. El carrito se conserva.',
            ];
            $mensaje = $mensajesEstado[$estado] ?? 'Wompi devolvió un estado no reconocido. El pedido no se marcó como pagado.';

            $pendienteSesion = $_SESSION['pago_pendiente'] ?? null;
            if ($estado === 'APPROVED' && !empty($_SESSION['usuario_id']) &&
                is_array($pendienteSesion) && ($pendienteSesion['referencia'] ?? null) === ($transaccion['reference'] ?? null) &&
                is_array($pendienteSesion['carrito'] ?? null)) {
                $usuarioPedido = mysqli_prepare($conexion, 'SELECT usuario_id FROM pedidos WHERE referencia_pago = ? LIMIT 1');
                $esPropietarioPedido = false;
                if ($usuarioPedido) {
                    mysqli_stmt_bind_param($usuarioPedido, 's', $pendienteSesion['referencia']);
                    if (mysqli_stmt_execute($usuarioPedido)) {
                        $resultadoUsuarioPedido = mysqli_stmt_get_result($usuarioPedido);
                        $filaUsuarioPedido = $resultadoUsuarioPedido ? mysqli_fetch_assoc($resultadoUsuarioPedido) : null;
                        $esPropietarioPedido = $filaUsuarioPedido && (int) $filaUsuarioPedido['usuario_id'] === (int) $_SESSION['usuario_id'];
                    }
                    mysqli_stmt_close($usuarioPedido);
                }
                if (!$esPropietarioPedido) {
                    unset($_SESSION['pago_pendiente']);
                } else {
                $carritoActual = $_SESSION['carrito'] ?? [];
                $carritoPedido = $pendienteSesion['carrito'] ?? [];
                $yaEnHistorial = false;
                foreach ($_SESSION['historial'] ?? [] as $compraExistente) {
                    if (($compraExistente['referencia'] ?? '') === $pendienteSesion['referencia']) {
                        $yaEnHistorial = true;
                        break;
                    }
                }
                if (!$yaEnHistorial) {
                    $cantidadesCompradas = array_map(static function ($linea) {
                        return (int) ($linea['cantidad'] ?? 0);
                    }, $carritoPedido);
                    $_SESSION['historial'][] = [
                        'productos' => $carritoPedido,
                        'total' => (int) $transaccion['amount_in_cents'] / 100,
                        'cantidad_total' => array_sum($cantidadesCompradas),
                        'fecha' => date('d/m/Y'),
                        'hora' => date('H:i:s'),
                        'metodo_pago' => (string) ($transaccion['payment_method_type'] ?? 'WOMPI'),
                        'referencia' => (string) $pendienteSesion['referencia'],
                    ];
                }
                $cantidadesActuales = [];
                foreach ($carritoActual as $nombre => $linea) {
                    if (!is_array($linea)) {
                        $cantidadesActuales = [];
                        break;
                    }
                    $cantidadesActuales[(string) $nombre] = (int) ($linea['cantidad'] ?? 0);
                }
                $cantidadesPedido = [];
                foreach ($carritoPedido as $nombre => $linea) {
                    if (!is_array($linea)) {
                        $cantidadesPedido = [];
                        break;
                    }
                    $cantidadesPedido[(string) $nombre] = (int) ($linea['cantidad'] ?? 0);
                }
                ksort($cantidadesActuales, SORT_STRING);
                ksort($cantidadesPedido, SORT_STRING);
                if ($cantidadesActuales !== [] && $cantidadesActuales === $cantidadesPedido) {
                    $_SESSION['carrito'] = [];
                }
                guardarDatosUsuario($conexion);
                unset($_SESSION['pago_pendiente']);
                }
            }
        } elseif ($estado === 'APPROVED') {
            $mensaje = 'Wompi reportó una aprobación, pero no se pudo validar contra el pedido. No se confirmó la compra.';
            $estado = 'NO_VERIFICADO';
        } else {
            $mensaje = 'La transacción fue consultada, pero no se pudo asociar de forma segura al pedido.';
            $estado = 'NO_VERIFICADO';
        }
    }
}

$titulo = match ($estado) {
    'APPROVED' => 'Pago aprobado',
    'PENDING' => 'Pago pendiente',
    'DECLINED', 'VOIDED', 'ERROR' => 'Pago no aprobado',
    default => 'Resultado del pago',
};
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Resultado del pago</title><link rel="stylesheet" href="styles.css"></head>
<body>
<main class="resultado-pago">
    <h1><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
    <a href="<?= $estado === 'APPROVED' ? 'perfil.php' : 'ver_carrito.php' ?>" class="btn-pagar">Continuar</a>
</main>
</body>
</html>