<?php
session_start();

$producto = $_GET['producto'];

if($_SESSION['carrito'][$producto]['cantidad'] > 1){
    $_SESSION['carrito'][$producto]['cantidad']--;
} else {
    unset($_SESSION['carrito'][$producto]);
}

header("Location: ver_carrito.php");
?>