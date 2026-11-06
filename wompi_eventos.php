<?php
include __DIR__ . '/conexion.php';
$conexion = $GLOBALS['conexion'];
require_once __DIR__ . '/wompi_config.php';
require_once __DIR__ . '/wompi_pago.php';
require_once __DIR__ . '/stripe_pago.php';

function responderEventoWompi($codigo, $mensaje) {
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $codigo >= 200 && $codigo < 300, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    responderEventoWompi(405, 'Método no permitido.');
}
if (!wompiCredencialesSandboxDisponibles()) {
    responderEventoWompi(503, 'El procesamiento de eventos Sandbox no está configurado.');
}

$cuerpo = file_get_contents('php://input');
$checksumHeader = (string) ($_SERVER['HTTP_X_EVENT_CHECKSUM'] ?? '');
$evento = wompiValidarEvento($cuerpo, $checksumHeader);
if (!$evento) {
    responderEventoWompi(400, 'Evento no válido.');
}

$transaccionEvento = $evento['data']['transaction'] ?? null;
$estadosPermitidos = ['PENDING', 'APPROVED', 'DECLINED', 'VOIDED', 'ERROR'];
if (!is_array($transaccionEvento) ||
    !is_string($transaccionEvento['id'] ?? null) ||
    !is_string($transaccionEvento['reference'] ?? null) ||
    !in_array($transaccionEvento['status'] ?? null, $estadosPermitidos, true) ||
    ($transaccionEvento['currency'] ?? null) !== 'COP' ||
    filter_var($transaccionEvento['amount_in_cents'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
    responderEventoWompi(400, 'Los datos del evento no son válidos.');
}

$transaccionActual = wompiObtenerTransaccion($transaccionEvento['id']);
if (!$transaccionActual) {
    responderEventoWompi(503, 'No fue posible verificar la transacción con Wompi.');
}
$datosCoinciden = ($transaccionActual['id'] ?? null) === $transaccionEvento['id']
    && ($transaccionActual['reference'] ?? null) === $transaccionEvento['reference']
    && ($transaccionActual['currency'] ?? null) === 'COP'
    && filter_var($transaccionActual['amount_in_cents'] ?? null, FILTER_VALIDATE_INT) === filter_var($transaccionEvento['amount_in_cents'], FILTER_VALIDATE_INT);
if (!$datosCoinciden) {
    responderEventoWompi(400, 'La transacción consultada no coincide con el evento.');
}

if (!wompiAplicarTransaccionVerificada($conexion, $transaccionActual)) {
    responderEventoWompi(503, 'No fue posible aplicar la confirmación al pedido.');
}
if (($transaccionActual['status'] ?? '') === 'APPROVED' && !enviarFacturasPedido($conexion, (string) $transaccionActual['reference'])) {
    error_log('No se pudieron enviar todos los correos de factura para la compra Wompi.');
    responderEventoWompi(503, 'El pago se confirmó, pero el correo de la factura se reintentará.');
}
responderEventoWompi(200, 'Evento procesado.');
