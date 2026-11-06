<?php
session_start();
require_once __DIR__ . '/conexion.php';
$conexion = $GLOBALS['conexion'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registro.php');
    exit();
}

$nombre = trim($_POST['nombre'] ?? '');
$correo = strtolower(trim($_POST['correo'] ?? ''));
$fechaNacimiento = trim($_POST['fecha_nacimiento'] ?? '');
$genero = $_POST['genero'] ?? '';
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';
$fecha = DateTime::createFromFormat('!Y-m-d', $fechaNacimiento);
$errores = [];

if ($nombre === '' || !preg_match('/^.{1,100}$/us', $nombre)) {
    $errores[] = 'Escribe un nombre válido (máximo 100 caracteres).';
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 254) {
    $errores[] = 'Escribe un correo válido.';
}
if (!$fecha || $fecha->format('Y-m-d') !== $fechaNacimiento || $fecha >= new DateTime('today')) {
    $errores[] = 'La fecha de nacimiento no es válida.';
}
if (!in_array($genero, ['hombre', 'mujer'], true)) {
    $errores[] = 'Selecciona una opción de género.';
}
if (!preg_match('/^(?=.*\p{L})(?=.*\d)(?=.*[^\p{L}\d\s]).{8,16}$/u', $password)) {
    $errores[] = 'La contraseña debe tener entre 8 y 16 caracteres e incluir letras, números y símbolos.';
}
if ($password !== $passwordConfirm) {
    $errores[] = 'Las contraseñas no coinciden.';
}

if ($errores) {
    $_SESSION['errores'] = $errores;
    $_SESSION['notificacion'] = ['tipo' => 'amarilla', 'mensaje' => implode(' ', $errores), 'modo' => 'register'];
    header('Location: index.php');
    exit();
}

$correoAdmin = strtolower(trim((string) (getenv('ADMIN_EMAIL') ?: 'tachiandrade79@gmail.com')));
if ($correo === $correoAdmin) {
    $_SESSION['errores'] = ['Ese correo está reservado para la cuenta administrativa.'];
    $_SESSION['notificacion'] = ['tipo' => 'roja', 'mensaje' => 'No se pudo crear la cuenta.', 'modo' => 'register'];
    header('Location: index.php');
    exit();
}

$stmt = $conexion->prepare('SELECT id FROM usuarios WHERE correo = ?');
$stmt->bind_param('s', $correo);
$stmt->execute();
$existe = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($existe) {
    $_SESSION['errores'] = ['Ese correo ya tiene una cuenta.'];
    $_SESSION['notificacion'] = ['tipo' => 'roja', 'mensaje' => 'Ese correo ya tiene una cuenta.', 'modo' => 'register'];
    header('Location: index.php');
    exit();
}

$codigo = (string) random_int(100000, 999999);
$codigoHash = password_hash($codigo, PASSWORD_DEFAULT);
$paso = 'correo';
$contrasenaHash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conexion->prepare("INSERT INTO registros_pendientes (correo, nombre, contrasena, fecha_nacimiento, genero, correo_padre, paso, codigo_hash, codigo_expira, intentos) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), contrasena = VALUES(contrasena), fecha_nacimiento = VALUES(fecha_nacimiento), genero = VALUES(genero), correo_padre = NULL, paso = VALUES(paso), codigo_hash = VALUES(codigo_hash), codigo_expira = VALUES(codigo_expira), intentos = 0");
$stmt->bind_param('sssssss', $correo, $nombre, $contrasenaHash, $fechaNacimiento, $genero, $paso, $codigoHash);
$guardado = $stmt->execute();
$errorGuardado = $stmt->error;
$stmt->close();

$_SESSION['registro_correo'] = $correo;
$_SESSION['registro_paso'] = 'correo';
if (!$guardado) {
    error_log('No fue posible guardar registro pendiente: ' . $errorGuardado);
    $_SESSION['registro_mensaje'] = 'No fue posible guardar el registro. Inténtalo nuevamente.';
    header('Location: registro.php');
    exit();
}

if (!enviarNotificacion($correo, 'Verifica tu correo - Always Beautiful', "Tu código de verificación es: {$codigo}\nVence en 10 minutos. Si no solicitaste esta cuenta, ignora este mensaje.")) {
    $_SESSION['registro_mensaje'] = 'Guardamos tus datos, pero ' . mensajeErrorNotificacion() . ' Cuando lo corrijas, selecciona “Reenviar código”.';
} else {
    $_SESSION['registro_mensaje'] = 'Enviamos un código de seis dígitos a tu correo. Vence en 10 minutos.';
}
header('Location: registro.php?verificar=1');
exit();