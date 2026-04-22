<?php
session_start();
include("conexion.php");

$correo = $_POST['correo'];
$password = $_POST['password'];

$sql = "SELECT * FROM Usuario WHERE correo='$correo'";
$resultado = $conexion->query($sql);

if ($resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc();

    // VALIDAR CONTRASEÑA
    if ($password == $usuario['password']) {

        $_SESSION['usuario'] = $usuario['nombre'];
        $_SESSION['rol'] = $usuario['rol'];


        if ($usuario['rol'] == 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: usuario.php");
        }

    } else {
        echo "Contraseña incorrecta";
    }

} else {
    echo "Usuario no existe";
}
?>
