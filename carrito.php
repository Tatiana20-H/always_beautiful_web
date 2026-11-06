<?php
session_start();
require_once __DIR__ . '/conexion.php';
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

    $nombre = trim($_POST['nombre'] ?? '');
    $stmt = $conexion->prepare('SELECT nombre, precio, stock, imagen FROM productos WHERE nombre = ? AND activo = 1 LIMIT 1');
    $stmt->bind_param('s', $nombre);
    $stmt->execute();
    $productoCatalogo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$productoCatalogo) {
        $_SESSION['notificacion'] = ['tipo' => 'roja', 'mensaje' => 'Este producto ya no está disponible.', 'modo' => 'login'];
        header('Location: inicio.php');
        exit();
    }

    $precio = (int) $productoCatalogo['precio'];
    $imagen = $productoCatalogo['imagen'] ?: 'IMG/default.jpg';
    $cantidadActual = (int) ($_SESSION['carrito'][$nombre]['cantidad'] ?? 0);
    if ($cantidadActual >= 99 || $cantidadActual >= (int) $productoCatalogo['stock']) {
        $_SESSION['notificacion'] = ['tipo' => 'amarilla', 'mensaje' => 'No hay suficiente stock para agregar otra unidad.', 'modo' => 'login'];
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'inicio.php'));
        exit();
    }

    if(isset($_SESSION['carrito'][$nombre])){
        $_SESSION['carrito'][$nombre]['cantidad']++;
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