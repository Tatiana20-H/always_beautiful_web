<?php
include("conexion.php");

$nombre = $_POST['nombre'];
$correo = $_POST['correo'];
$password = $_POST['password'];

$sql = "INSERT INTO Usuario (nombre, correo, password, rol)
        VALUES ('$nombre', '$correo', '$password', 'usuario')";

if ($conexion->query($sql)) {
    header("Location: inicio.php"); // 🔥 directo a la tienda
    exit();
} else {
    echo "Error al registrar";
}
?>