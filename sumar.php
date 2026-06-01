<?php
session_start();

$producto = $_GET['producto'];

$_SESSION['carrito'][$producto]['cantidad']++;

header("Location: ver_carrito.php");
?>