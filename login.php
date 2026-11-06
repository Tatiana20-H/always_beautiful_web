<?php
session_start();
require_once __DIR__ . '/conexion.php';
$conexion = $GLOBALS['conexion'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$correo = strtolower(trim($_POST['correo'] ?? ''));
$password = $_POST['password'] ?? '';
$rolSolicitado = $_POST['rol'] ?? 'usuario';
$errores = [];

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'Escribe un correo válido.';
}
if ($password === '') {
    $errores[] = 'La contraseña es obligatoria.';
}
if (!in_array($rolSolicitado, ['usuario', 'admin'], true)) {
    $errores[] = 'Credenciales incorrectas.';
}

function redirectWithLoginErrors(array $errors, array $oldData = []): void {
    $_SESSION['old_login'] = $oldData;
    $_SESSION['notificacion'] = [
        'tipo' => 'roja',
        'mensaje' => implode(' ', $errors),
        'modo' => 'login'
    ];
    header('Location: index.php');
    exit();
}

if ($errores) {
    redirectWithLoginErrors($errores, ['correo' => $correo]);
}

$stmt = $conexion->prepare('SELECT id, nombre, correo, contrasena, rol, genero, foto_perfil, correo_verificado FROM usuarios WHERE correo = ? AND activo = 1 LIMIT 1');
if (!$stmt) {
    redirectWithLoginErrors(['No fue posible iniciar sesión. Inténtalo más tarde.'], ['correo' => $correo]);
}
$stmt->bind_param('s', $correo);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

$correoAdmin = strtolower(trim((string) (getenv('ADMIN_EMAIL') ?: 'tachiandrade79@gmail.com')));
$rolReal = strtolower(trim((string) ($usuario['rol'] ?? '')));
$adminPasswordConfigured = (string) getenv('ADMIN_PASSWORD') !== '';
$rolPermitido = $rolSolicitado === $rolReal && ($rolReal !== 'admin' || ($correo === $correoAdmin && $adminPasswordConfigured));
$autenticado = $usuario && password_verify($password, (string) $usuario['contrasena']);

if (!$autenticado || !$rolPermitido) {
    redirectWithLoginErrors(['Correo, contraseña o tipo de cuenta incorrectos.'], ['correo' => $correo]);
}
if (empty($usuario['correo_verificado'])) {
    redirectWithLoginErrors(['Debes verificar tu correo antes de iniciar sesión.'], ['correo' => $correo]);
}
session_regenerate_id(true);
$_SESSION['usuario_id'] = (int) $usuario['id'];
$_SESSION['nombre'] = $usuario['nombre'];
$_SESSION['correo'] = $usuario['correo'];
$_SESSION['rol'] = $rolReal;
$_SESSION['genero'] = $usuario['genero'] ?? null;
$_SESSION['foto_perfil'] = $usuario['foto_perfil'] ?? null;
cargarDatosUsuario($conexion, (int) $usuario['id']);
$_SESSION['notificacion'] = ['tipo' => 'verde', 'mensaje' => 'Inicio de sesión exitoso.', 'modo' => 'login'];
enviarNotificacion($usuario['correo'], 'Inicio de sesión en Always Beautiful', "Hola {$usuario['nombre']}, se inició sesión en tu cuenta de Always Beautiful.");

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$historial = $conexion->prepare("INSERT INTO historial_actividades (usuario_id, accion, descripcion, ip_address) VALUES (?, 'LOGIN', 'Usuario inició sesión', ?)");
if ($historial) {
    $historial->bind_param('is', $usuario['id'], $ip);
    $historial->execute();
    $historial->close();
}

header($rolReal === 'admin' ? 'Location: Administrador/index.php' : 'Location: inicio.php');
exit();