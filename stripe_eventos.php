<?php
include __DIR__ . '/conexion.php';
$conexion = $GLOBALS['conexion'];
require_once __DIR__ . '/stripe_config.php';
require_once __DIR__ . '/stripe_pago.php';

function responderEventoStripe($codigo, $mensaje) {
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $codigo >= 200 && $codigo < 300, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    responderEventoStripe(405, 'Método no permitido.');
}
if (!stripeModoPruebaDisponible() || STRIPE_WEBHOOK_SECRET === '') {
    responderEventoStripe(503, 'El webhook de Stripe en modo de prueba no está configurado.');
}

$cuerpo = file_get_contents('php://input');
$firma = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
$evento = stripeValidarFirmaWebhook($cuerpo, $firma, STRIPE_WEBHOOK_SECRET);
if (!$evento) {
    responderEventoStripe(400, 'La firma del evento no es válida.');
}

$tiposAceptados = ['checkout.session.completed', 'checkout.session.async_payment_succeeded'];
if (!in_array($evento['type'] ?? null, $tiposAceptados, true)) {
    responderEventoStripe(200, 'Evento recibido sin acciones requeridas.');
}
$sesionEvento = $evento['data']['object'] ?? null;
if (!is_array($sesionEvento) || !is_string($sesionEvento['id'] ?? null)) {
    responderEventoStripe(400, 'Los datos del checkout no son válidos.');
}
$sesionActual = stripeObtenerSesionCheckout($sesionEvento['id']);
if (!$sesionActual) {
    responderEventoStripe(503, 'No fue posible verificar el checkout directamente con Stripe.');
}
if (!stripeAplicarSesionVerificada($conexion, $sesionActual)) {
    responderEventoStripe(400, 'El checkout no coincide con un pedido pagado válido.');
}
if (!stripeEnviarFacturasPedido($conexion, (string) $sesionActual['client_reference_id'])) {
    error_log('No se pudieron enviar todos los correos de factura para la compra Stripe.');
    responderEventoStripe(503, 'El pago se confirmó, pero la notificación por correo se reintentará.');
}
responderEventoStripe(200, 'Pago de prueba confirmado.');