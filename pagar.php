<?php
session_start();
date_default_timezone_set('America/Bogota');

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

// GUARDAR HISTORIAL (ANTES de borrar carrito)
$_SESSION['historial'][] = [
    "productos" => $_SESSION['carrito'],
    "total" => $total,
    "cantidad_total" => $totalProductos,
    "fecha" => date("d/m/Y"),
    "hora" => date("H:i:s")
];

// Ahora sí vaciar carrito
unset($_SESSION['carrito']);

// Redirigir
echo "<script>
alert('Pago finalizado, gracias por su compra 💖');
window.location='perfil.php';
</script>";
?>