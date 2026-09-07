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

    if ($estado === 'APPROVED' && $transaccion['reference'] === $pendiente['referencia'] &&
        (int) $transaccion['amount_in_cents'] === (int) $pendiente['monto']) {
        $_SESSION['historial'][] = [
            'productos' => $pendiente['carrito'],
            'total' => (int) $pendiente['monto'] / 100,
            'cantidad_total' => array_sum(array_column($pendiente['carrito'], 'cantidad')),
            'fecha' => date('d/m/Y'),
            'hora' => date('H:i:s'),
            'metodo_pago' => $transaccion['payment_method_type'] ?? 'WOMPI',
            'referencia' => $pendiente['referencia']
        ];
        $_SESSION['carrito'] = [];
        guardarDatosUsuario($conexion);
        $mensaje = 'Pago aprobado. Tu compra quedó guardada en el historial.';
    } elseif ($estado === 'PENDING') {
        $mensaje = 'El pago está pendiente de confirmación. Revisa nuevamente en unos momentos.';
    } else {
        $mensaje = 'El pago no fue aprobado. Tu carrito se conserva.';
    }
}

unset($_SESSION['pago_pendiente']);
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