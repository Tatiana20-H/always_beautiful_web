<?php
session_start();

$correo = $_POST['correo'];
$password = $_POST['password'];

// ADMIN
if($correo == "admin@gmail.com" && $password == "123"){
    
    $_SESSION['usuario'] = $correo;
    $_SESSION['rol'] = "admin";

    header("Location: admin/admin.php");
    exit();
}

// USUARIO NORMAL
else {
    $_SESSION['usuario'] = $correo;
    $_SESSION['rol'] = "cliente";

    header("Location: usuario/inicio.php");
    exit();
}
?>