<?php
session_start();

$rolSesion = strtolower(trim((string) ($_SESSION['rol'] ?? '')));
$usuarioIdSesion = (int) ($_SESSION['usuario_id'] ?? 0);

if ($usuarioIdSesion <= 0 || $rolSesion !== 'admin') {
    header("Location: ../index.php");
    exit();
}
?>