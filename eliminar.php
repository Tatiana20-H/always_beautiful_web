<?php
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];

$producto = $_GET['producto'] ?? '';
if (isset($_SESSION['usuario_id'])) {
	unset($_SESSION['carrito'][$producto]);
	guardarDatosUsuario($conexion);
}

header("Location: ver_carrito.php");
?>