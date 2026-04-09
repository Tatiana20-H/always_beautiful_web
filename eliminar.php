<?php
session_start();

$producto = $_GET['producto'];
unset($_SESSION['carrito'][$producto]);

header("Location: ver_carrito.php");
?>