<?php
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];

$producto = $_GET['producto'] ?? '';

if (isset($_SESSION['usuario_id'], $_SESSION['carrito'][$producto])) {
	$_SESSION['carrito'][$producto]['cantidad']++;
	guardarDatosUsuario($conexion);
}

header("Location: ver_carrito.php");
?>