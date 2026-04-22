<?php
session_start();
include("conexion.php");

$correo = $_POST['correo'];
$contrasena = $_POST['contrasena'];

# 🔹 BUSCAR EN ADMIN
$sql_admin = "SELECT * FROM Administrador 
WHERE correo='$correo' AND contraseña='$contrasena'";

$result_admin = $conexion->query($sql_admin);

if ($result_admin->num_rows > 0) {
    $_SESSION['admin'] = $correo;
    header("Location: admin/index.php");
    exit();
}

# 🔹 BUSCAR EN CLIENTE
$sql_cliente = "SELECT * FROM Cliente 
WHERE correo='$correo' AND contraseña='$contrasena'";

$result_cliente = $conexion->query($sql_cliente);

if ($result_cliente->num_rows > 0) {
    $_SESSION['cliente'] = $correo;
    header("Location: index.php"); // tu página principal
    exit();
}

echo "Correo o contraseña incorrectos";
?>