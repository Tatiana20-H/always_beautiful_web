<?php
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];

if (isset($_SESSION['usuario_id'])) {
	$_SESSION['carrito'] = [];
	guardarDatosUsuario($conexion);
}

header("Location: inicio.php");
?>