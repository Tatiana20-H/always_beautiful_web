<?php
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];

if (strtolower((string) ($_SESSION['rol'] ?? '')) === 'admin') {
	header('Location: Administrador/index.php');
	exit();
}

if (isset($_SESSION['usuario_id'])) {
	$_SESSION['carrito'] = [];
	guardarDatosUsuario($conexion);
}

header("Location: inicio.php");
?>