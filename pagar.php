<?php
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];
include("wompi_config.php");
date_default_timezone_set('America/Bogota');

if (!isset($_SESSION['usuario_id'])) {
    header('Location: inicio.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ver_carrito.php');
    exit();
}

// Crear historial si no existe
if(!isset($_SESSION['historial'])){
    $_SESSION['historial'] = [];
}

// Verificar que haya carrito
if(!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])){
    echo "<script>alert('El carrito está vacío'); window.location='inicio.php';</script>";
    exit();
}

$total = 0;
$totalProductos = 0;

// Calcular totales
foreach($_SESSION['carrito'] as $nombre => $producto){
    $subtotal = $producto['precio'] * $producto['cantidad'];
    $total += $subtotal;
    $totalProductos += $producto['cantidad'];
}

if (WOMPI_PUBLIC_KEY === '' || WOMPI_INTEGRITY_SECRET === '') {
    echo "<script>alert('Configura las claves de Wompi en wompi_config.php.'); window.location='ver_carrito.php';</script>";
    exit();
}

$referencia = 'AB-' . (int) $_SESSION['usuario_id'] . '-' . time();
$montoEnCentavos = (int) $total * 100;
$firma = hash('sha256', $referencia . $montoEnCentavos . 'COP' . WOMPI_INTEGRITY_SECRET);
$redirectUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/resultado_pago.php';

$_SESSION['pago_pendiente'] = [
    'referencia' => $referencia,
    'monto' => $montoEnCentavos,
    'carrito' => $_SESSION['carrito']
];

$checkoutUrl = wompiBaseUrl() . '/p/?public-key=' . rawurlencode(WOMPI_PUBLIC_KEY)
    . '&currency=COP&amount-in-cents=' . $montoEnCentavos
    . '&reference=' . rawurlencode($referencia)
    . '&signature:integrity=' . $firma
    . '&redirect-url=' . rawurlencode($redirectUrl);

header('Location: ' . $checkoutUrl);
exit();
?>