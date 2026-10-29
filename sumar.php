<?php
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];
require_once __DIR__ . '/precios_carrito.php';

if (strtolower((string) ($_SESSION['rol'] ?? '')) === 'admin') {
	header('Location: Administrador/index.php');
	exit();
}

$producto = is_string($_GET['producto'] ?? null) ? trim($_GET['producto']) : '';

if (isset($_SESSION['usuario_id'], $_SESSION['carrito'][$producto])) {
	$articulo = resolverArticuloCarrito($conexion, $producto);
	$cantidad = normalizarCantidadCarrito($_SESSION['carrito'][$producto]['cantidad'] ?? null);
	if ($articulo['valido'] && $cantidad !== null && validarCantidadDisponible($articulo, $cantidad + 1)) {
		$_SESSION['carrito'][$producto] = [
			'precio' => $articulo['precio'],
			'cantidad' => $cantidad + 1,
			'imagen' => $articulo['imagen'],
		];
		guardarDatosUsuario($conexion);
	} else {
		$_SESSION['notificacion'] = [
			'tipo' => 'roja',
			'mensaje' => $articulo['valido'] ? 'La cantidad supera el inventario disponible.' : $articulo['error'],
		];
	}
}

header("Location: ver_carrito.php");
?>