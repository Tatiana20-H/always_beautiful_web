<?php
session_start();

$rolSesion = strtolower(trim((string) ($_SESSION['rol'] ?? '')));
$usuarioIdSesion = (int) ($_SESSION['usuario_id'] ?? 0);
$correoAdmin = strtolower(trim((string) (getenv('ADMIN_EMAIL') ?: 'tachiandrade79@gmail.com')));
require_once __DIR__ . '/../conexion.php';
$conexion = $GLOBALS['conexion'];
$stmt = $conexion->prepare('SELECT correo, rol FROM usuarios WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $usuarioIdSesion);
$stmt->execute();
$usuarioActual = $stmt->get_result()->fetch_assoc();
$stmt->close();
$adminPasswordConfigured = (string) getenv('ADMIN_PASSWORD') !== '';

if ($usuarioIdSesion <= 0 || $rolSesion !== 'admin' || !$adminPasswordConfigured || !$usuarioActual || $usuarioActual['rol'] !== 'admin' || strtolower($usuarioActual['correo']) !== $correoAdmin) {
    unset($_SESSION['usuario_id'], $_SESSION['nombre'], $_SESSION['correo'], $_SESSION['rol']);
    header("Location: ../index.php");
    exit();
}
?>