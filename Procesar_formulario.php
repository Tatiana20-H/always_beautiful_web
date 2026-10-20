<?php
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['usuario_id'])) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: formulario.php');
        exit();
    }
}

$errorFormulario = static function (string $mensaje) {
    $_SESSION['notificacion_contacto'] = ['tipo' => 'error', 'mensaje' => $mensaje];
    $_SESSION['datos_contacto_pendientes'] = [
        'nombre' => trim((string) ($_POST['nombre'] ?? '')),
        'correo' => trim((string) ($_POST['correo'] ?? '')),
        'asunto' => trim((string) ($_POST['asunto'] ?? '')),
        'mensaje' => trim((string) ($_POST['mensaje'] ?? '')),
    ];
    header('Location: formulario.php');
    exit();
};

$tokenEnviado = $_POST['token_csrf'] ?? '';
if (!is_string($tokenEnviado) || empty($_SESSION['token_csrf_contacto']) || !hash_equals($_SESSION['token_csrf_contacto'], $tokenEnviado)) {
    $errorFormulario('La sesión del formulario expiró. Recarga la página e inténtalo de nuevo.');
}

$usuarioId = !empty($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
$nombre = trim((string) ($usuarioId ? ($_SESSION['nombre'] ?? '') : ($_POST['nombre'] ?? '')));
$correo = trim((string) ($usuarioId ? ($_SESSION['correo'] ?? '') : ($_POST['correo'] ?? '')));
$asunto = trim($_POST['asunto'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');
$fotoMensaje = null;

if ($nombre === '' || strlen($nombre) > 100 || !filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 100) {
    $errorFormulario('Escribe un nombre y un correo electrónico válidos.');
}
if ($asunto === '' || strlen($asunto) > 200 || $mensaje === '') {
    $errorFormulario('Escribe un asunto de máximo 200 caracteres y un mensaje.');
}

if (isset($_FILES['foto_mensaje']) && $_FILES['foto_mensaje']['error'] !== UPLOAD_ERR_NO_FILE) {
    $archivo = $_FILES['foto_mensaje'];
    $tiposPermitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $tipo = !empty($archivo['tmp_name']) && is_uploaded_file($archivo['tmp_name']) ? mime_content_type($archivo['tmp_name']) : '';

    if ($archivo['error'] !== UPLOAD_ERR_OK || !isset($tiposPermitidos[$tipo]) || $archivo['size'] > 5 * 1024 * 1024) {
        $errorFormulario('La foto debe ser JPG, PNG o WEBP y pesar menos de 5 MB.');
    }

    $nombreArchivo = bin2hex(random_bytes(12)) . '.' . $tiposPermitidos[$tipo];
    $fotoMensaje = 'IMG/mensajes/' . $nombreArchivo;
    $rutaCompleta = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $fotoMensaje);
    $directorioFotos = dirname($rutaCompleta);
    if (!is_dir($directorioFotos) && !@mkdir($directorioFotos, 0755, true) && !is_dir($directorioFotos)) {
        $errorFormulario('No fue posible preparar el almacenamiento de la foto.');
    }

    if (!@move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        $errorFormulario('No fue posible guardar la foto adjunta.');
    }
}

$sql = "INSERT INTO mensajes_contacto (usuario_id, nombre, correo, asunto, mensaje, foto_mensaje, estado) VALUES (?, ?, ?, ?, ?, ?, 'no_leido')";
$stmt = mysqli_prepare($conexion, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'isssss', $usuarioId, $nombre, $correo, $asunto, $mensaje, $fotoMensaje);
    $enviado = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
} else {
    $enviado = false;
}

if (!$enviado) {
    if ($fotoMensaje && isset($rutaCompleta) && is_file($rutaCompleta)) {
        unlink($rutaCompleta);
    }
    $errorFormulario('No fue posible enviar el mensaje. Inténtalo nuevamente.');
}

unset($_SESSION['datos_contacto_pendientes']);
$_SESSION['notificacion_contacto'] = ['tipo' => 'exito', 'mensaje' => 'Tu mensaje fue enviado. El equipo de Always Beautiful ya puede verlo en la bandeja de administración.'];
header('Location: formulario.php');
exit();