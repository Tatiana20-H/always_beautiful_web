<?php
session_start();
include __DIR__ . '/conexion.php';
$conexion = $GLOBALS['conexion'];
require_once __DIR__ . '/wompi_config.php';
require_once __DIR__ . '/wompi_pago.php';
require_once __DIR__ . '/stripe_config.php';
require_once __DIR__ . '/stripe_pago.php';

function mostrarInicioPagoError($mensaje, $codigo = 400) {
    http_response_code($codigo);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Pago Stripe</title><link rel="stylesheet" href="styles.css"></head><body><main class="resultado-pago"><h1>No se pudo iniciar el pago</h1><p>' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p><a class="btn-pagar" href="ver_carrito.php">Volver al carrito</a></main></body></html>';
    exit();
}

if (strtolower((string) ($_SESSION['rol'] ?? '')) === 'admin') {
    header('Location: Administrador/index.php');
    exit();
}
if (!isset($_SESSION['usuario_id'])) {
    header('Location: inicio.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !is_string($_POST['csrf_pago'] ?? null) ||
    !is_string($_SESSION['csrf_pago'] ?? null) ||
    !hash_equals($_SESSION['csrf_pago'], $_POST['csrf_pago'])) {
    mostrarInicioPagoError('La solicitud expiró o no es válida. Vuelve al pedido e inténtalo nuevamente.', 403);
}
$datosEntrega = [
    'nombre' => trim((string) ($_POST['nombre_entrega'] ?? '')),
    'correo' => trim((string) ($_POST['correo_entrega'] ?? '')),
    'telefono' => trim((string) ($_POST['telefono_entrega'] ?? '')),
    'direccion' => trim((string) ($_POST['direccion_entrega'] ?? '')),
    'ciudad' => trim((string) ($_POST['ciudad_entrega'] ?? '')),
    'departamento' => trim((string) ($_POST['departamento_entrega'] ?? '')),
];
if ($datosEntrega['nombre'] === '' || strlen($datosEntrega['nombre']) > 100 ||
    !filter_var($datosEntrega['correo'], FILTER_VALIDATE_EMAIL) || strlen($datosEntrega['correo']) > 100 ||
    !preg_match('/^[0-9+().\-\s]{7,30}$/', $datosEntrega['telefono']) ||
    $datosEntrega['direccion'] === '' || strlen($datosEntrega['direccion']) > 200 ||
    $datosEntrega['ciudad'] === '' || strlen($datosEntrega['ciudad']) > 80 ||
    $datosEntrega['departamento'] === '' || strlen($datosEntrega['departamento']) > 80) {
    mostrarInicioPagoError('Completa todos los datos de contacto y entrega correctamente. Vuelve al pedido para corregirlos.');
}
$datosEntregaJson = json_encode($datosEntrega, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (!is_string($datosEntregaJson)) {
    mostrarInicioPagoError('No fue posible preparar los datos de entrega. Inténtalo nuevamente.', 400);
}
if (!stripeModoPruebaDisponible()) {
    $mensajeConfiguracion = STRIPE_SECRET_KEY === ''
        ? 'Falta configurar STRIPE_SECRET_KEY en el archivo .env con una clave de prueba de Stripe (sk_test_ o rk_test_).'
        : 'STRIPE_SECRET_KEY debe ser una clave de prueba que comience por sk_test_ o rk_test_.';
    mostrarInicioPagoError($mensajeConfiguracion, 503);
}

$appUrl = stripeBaseUrl();
$urlAplicacion = parse_url($appUrl);
if (!is_array($urlAplicacion) || !in_array(strtolower((string) ($urlAplicacion['scheme'] ?? '')), ['http', 'https'], true) ||
    empty($urlAplicacion['host']) || isset($urlAplicacion['user']) || isset($urlAplicacion['pass'])) {
    mostrarInicioPagoError('Configura APP_URL con la dirección de Always Beautiful accesible desde el navegador.');
}
$pendiente = $_SESSION['pago_pendiente'] ?? null;
$usuarioId = filter_var($_SESSION['usuario_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($usuarioId === false || !is_array($pendiente) ||
    !isset($pendiente['pedido_id'], $pendiente['referencia']) ||
    !is_string($pendiente['referencia'])) {
    mostrarInicioPagoError('No hay un pedido pendiente válido asociado a esta sesión.');
}
$pedidoId = (int) $pendiente['pedido_id'];
$referencia = $pendiente['referencia'];

if (!mysqli_begin_transaction($conexion)) {
    mostrarInicioPagoError('No fue posible reservar el inicio del pago. Inténtalo nuevamente.', 500);
}
$buscarPedido = mysqli_prepare($conexion, 'SELECT total, descuento, estado, transaccion_id FROM pedidos WHERE id = ? AND usuario_id = ? AND referencia_pago = ? LIMIT 1 FOR UPDATE');
if (!$buscarPedido) {
    mysqli_rollback($conexion);
    mostrarInicioPagoError('No fue posible validar el pedido.', 500);
}
mysqli_stmt_bind_param($buscarPedido, 'iis', $pedidoId, $usuarioId, $referencia);
$pedidoConsultado = mysqli_stmt_execute($buscarPedido);
$resultadoPedido = $pedidoConsultado ? mysqli_stmt_get_result($buscarPedido) : false;
$pedido = $resultadoPedido ? mysqli_fetch_assoc($resultadoPedido) : null;
mysqli_stmt_close($buscarPedido);
if (!$pedido || !in_array($pedido['estado'], ['pendiente', 'procesando_pago'], true)) {
    mysqli_rollback($conexion);
    mostrarInicioPagoError('Este pedido ya no está disponible para iniciar otro checkout.');
}

$totalPedido = (int) $pedido['total'];
if ($totalPedido <= 0 || $totalPedido > intdiv(PHP_INT_MAX, 100)) {
    mysqli_rollback($conexion);
    mostrarInicioPagoError('El importe del pedido no es válido.');
}
$buscarDetalles = mysqli_prepare($conexion, 'SELECT cantidad, precio_unitario, subtotal FROM detalles_pedido WHERE pedido_id = ?');
if (!$buscarDetalles) {
    mysqli_rollback($conexion);
    mostrarInicioPagoError('No fue posible validar los detalles del pedido.', 500);
}
mysqli_stmt_bind_param($buscarDetalles, 'i', $pedidoId);
$detallesConsultados = mysqli_stmt_execute($buscarDetalles);
$resultadoDetalles = $detallesConsultados ? mysqli_stmt_get_result($buscarDetalles) : false;
$detalles = $resultadoDetalles ? mysqli_fetch_all($resultadoDetalles, MYSQLI_ASSOC) : [];
mysqli_stmt_close($buscarDetalles);
if (!$detallesConsultados || !wompiDetallesCoincidenConPedido($totalPedido, $detalles, (int) $pedido['descuento'])) {
    mysqli_rollback($conexion);
    mostrarInicioPagoError('El total del pedido no coincide con sus detalles. No se inició ningún pago.', 409);
}

if ($pedido['estado'] === 'procesando_pago') {
    $sesionExistenteId = (string) ($pedido['transaccion_id'] ?? '');
    mysqli_rollback($conexion);
    if (!preg_match('/^cs_test_[A-Za-z0-9_]+$/D', $sesionExistenteId)) {
        mostrarInicioPagoError('Este pedido ya tiene un pago iniciado con otro método. Revisa el estado del pedido antes de volver a pagar.', 409);
    }
    $sesionExistente = stripeObtenerSesionCheckout($sesionExistenteId);
    if (!$sesionExistente || ($sesionExistente['client_reference_id'] ?? null) !== $referencia ||
        (int) ($sesionExistente['amount_total'] ?? 0) !== $totalPedido * 100) {
        mostrarInicioPagoError('No fue posible verificar la sesión de pago. Inténtalo nuevamente.', 409);
    }
    if (($sesionExistente['payment_status'] ?? '') === 'paid') {
        header('Location: resultado_pago.php?session_id=' . rawurlencode($sesionExistenteId), true, 303);
        exit();
    }
    if (($sesionExistente['status'] ?? '') === 'open' && is_string($sesionExistente['url'] ?? null)) {
        $actualizarEntrega = mysqli_prepare($conexion, 'UPDATE pedidos SET datos_entrega = ? WHERE id = ? AND usuario_id = ? AND estado = \'procesando_pago\' AND transaccion_id = ?');
        if (!$actualizarEntrega) {
            mostrarInicioPagoError('No fue posible guardar los datos de entrega.', 500);
        }
        mysqli_stmt_bind_param($actualizarEntrega, 'siis', $datosEntregaJson, $pedidoId, $usuarioId, $sesionExistenteId);
        $entregaGuardada = mysqli_stmt_execute($actualizarEntrega);
        mysqli_stmt_close($actualizarEntrega);
        if (!$entregaGuardada) {
            mostrarInicioPagoError('No fue posible guardar los datos de entrega.', 500);
        }
        header('Location: ' . $sesionExistente['url'], true, 303);
        exit();
    }
    if (($sesionExistente['status'] ?? '') !== 'expired') {
        mostrarInicioPagoError('El pago todavía está siendo actualizado por Stripe. Inténtalo nuevamente en un momento.', 409);
    }

    try {
        $claveIdempotencia = hash('sha256', 'stripe-checkout-renovado-' . $referencia . '-' . bin2hex(random_bytes(16)));
    } catch (Throwable $error) {
        mostrarInicioPagoError('No fue posible preparar una nueva sesión de pago.', 500);
    }
    $sesionStripe = stripeCrearSesionCheckout($referencia, $pedidoId, $totalPedido, $appUrl, $datosEntrega['correo'], $claveIdempotencia);
    if (!$sesionStripe || !is_string($sesionStripe['id'] ?? null) || !is_string($sesionStripe['url'] ?? null)) {
        $errorStripe = stripeUltimoErrorApi();
        mostrarInicioPagoError($errorStripe !== '' ? $errorStripe : 'Stripe no pudo renovar la sesión de pago. Inténtalo nuevamente.', 502);
    }
    $renovarPedido = mysqli_prepare($conexion, "UPDATE pedidos SET transaccion_id = ?, metodo_pago = 'stripe', datos_entrega = ? WHERE id = ? AND usuario_id = ? AND estado = 'procesando_pago' AND transaccion_id = ?");
    if (!$renovarPedido) {
        mostrarInicioPagoError('No fue posible actualizar la sesión de pago.', 500);
    }
    mysqli_stmt_bind_param($renovarPedido, 'ssiis', $sesionStripe['id'], $datosEntregaJson, $pedidoId, $usuarioId, $sesionExistenteId);
    $renovado = mysqli_stmt_execute($renovarPedido);
    $filasRenovadas = mysqli_stmt_affected_rows($renovarPedido);
    mysqli_stmt_close($renovarPedido);
    if (!$renovado || $filasRenovadas !== 1) {
        mostrarInicioPagoError('El estado del pedido cambió. Vuelve a revisar el carrito.', 409);
    }
    $_SESSION['pago_pendiente']['monto'] = $totalPedido;
    header('Location: ' . $sesionStripe['url'], true, 303);
    exit();
}

$sesionStripe = stripeCrearSesionCheckout($referencia, $pedidoId, $totalPedido, $appUrl, $datosEntrega['correo']);
if (!$sesionStripe || !is_string($sesionStripe['id'] ?? null) || !is_string($sesionStripe['url'] ?? null)) {
    mysqli_rollback($conexion);
    $errorStripe = stripeUltimoErrorApi();
    mostrarInicioPagoError($errorStripe !== '' ? $errorStripe : 'Stripe no pudo crear la sesión de pago. Inténtalo nuevamente.', 502);
}
$actualizarPedido = mysqli_prepare($conexion, "UPDATE pedidos SET estado = 'procesando_pago', transaccion_id = ?, metodo_pago = 'stripe', datos_entrega = ? WHERE id = ? AND estado = 'pendiente' AND transaccion_id IS NULL");
if (!$actualizarPedido) {
    mysqli_rollback($conexion);
    mostrarInicioPagoError('No fue posible reservar el pedido para el pago.', 500);
}
mysqli_stmt_bind_param($actualizarPedido, 'ssi', $sesionStripe['id'], $datosEntregaJson, $pedidoId);
$actualizado = mysqli_stmt_execute($actualizarPedido);
$filasAfectadas = mysqli_stmt_affected_rows($actualizarPedido);
mysqli_stmt_close($actualizarPedido);
if (!$actualizado || $filasAfectadas !== 1 || !mysqli_commit($conexion)) {
    mysqli_rollback($conexion);
    mostrarInicioPagoError('El pago ya fue iniciado o no se pudo reservar el pedido.', 409);
}

$_SESSION['pago_pendiente']['monto'] = $totalPedido;
header('Location: ' . $sesionStripe['url'], true, 303);
exit();
