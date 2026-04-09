<?php
session_start();

if(!isset($_SESSION['carrito'])){
    $_SESSION['carrito'] = [];
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $imagen = isset($_POST['imagen']) ? $_POST['imagen'] : "IMG/default.jpg";

    // SI YA EXISTE
    if(isset($_SESSION['carrito'][$nombre])){
        $_SESSION['carrito'][$nombre]['cantidad']++;
    } else {
        $_SESSION['carrito'][$nombre] = [
            'precio' => $precio,
            'cantidad' => 1,
            'imagen' => $imagen
        ];
    }

    // 🔥 REGRESAR A LA MISMA PÁGINA
    if(isset($_SERVER['HTTP_REFERER'])){
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        header("Location: inicio.php");
    }

    exit();
}
?>