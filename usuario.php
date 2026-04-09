<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'usuario') {
    header("Location: index.php");
    exit();
}
?>

<h1>Bienvenido Usuario</h1>
<a href="logout.php">Cerrar sesión</a>