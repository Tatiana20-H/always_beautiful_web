<?php
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];
require_once __DIR__ . '/precios_carrito.php';

if (strtolower((string) ($_SESSION['rol'] ?? '')) === 'admin') {
    header('Location: Administrador/index.php');
    exit();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}

if(!isset($_SESSION['carrito'])){
    $_SESSION['carrito'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreSolicitado = is_string($_POST['nombre'] ?? null) ? trim($_POST['nombre']) : '';
    $articulo = resolverArticuloCarrito($conexion, $nombreSolicitado);
    $mensaje = '';

    if (!$articulo['valido']) {
        $mensaje = $articulo['error'];
    } else {
        $nombre = $articulo['nombre'];
        $cantidadActual = isset($_SESSION['carrito'][$nombre])
            ? normalizarCantidadCarrito($_SESSION['carrito'][$nombre]['cantidad'] ?? null)
            : 0;
        if ($cantidadActual === null) {
            $mensaje = 'La cantidad guardada en el carrito no es válida.';
        } else {
            $cantidadNueva = $cantidadActual + 1;
            if (!validarCantidadDisponible($articulo, $cantidadNueva)) {
                $mensaje = 'La cantidad supera el inventario disponible.';
            } else {
                $_SESSION['carrito'][$nombre] = [
                    'precio' => $articulo['precio'],
                    'cantidad' => $cantidadNueva,
                    'imagen' => $articulo['imagen'],
                ];
                guardarDatosUsuario($conexion);
            }
        }
    }

    if ($mensaje !== '') {
        $_SESSION['notificacion'] = ['tipo' => 'roja', 'mensaje' => $mensaje];
    }

    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'inicio.php'));
    exit();
}
?>