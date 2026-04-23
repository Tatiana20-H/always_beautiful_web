
<?php
$conexion = new mysqli("localhost", "root", "1076906755Tt*", "AlwaysBeautifulDB");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
?>