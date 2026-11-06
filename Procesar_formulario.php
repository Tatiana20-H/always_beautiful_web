<?php
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'usuario') {
    header('Location: formulario.php');
    exit();
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

if ($asunto === '' || strlen($asunto) > 200 || $mensaje === '' || strlen($mensaje) > 5000) {
    $_SESSION['notificacion_contacto'] = 'Escribe un asunto y un mensaje antes de enviar.';
    header('Location: formulario.php');
    exit();
}

if (isset($_FILES['foto_mensaje']) && $_FILES['foto_mensaje']['error'] !== UPLOAD_ERR_NO_FILE) {
    $archivo = $_FILES['foto_mensaje'];
    $tiposPermitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $tipo = ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
        && is_uploaded_file($archivo['tmp_name'] ?? '')
        ? mime_content_type($archivo['tmp_name'])
        : '';

    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !isset($tiposPermitidos[$tipo]) || ($archivo['size'] ?? 0) > 5 * 1024 * 1024) {
        $_SESSION['notificacion_contacto'] = 'La foto debe ser JPG, PNG o WEBP y pesar menos de 5 MB.';
        header('Location: formulario.php');
        exit();
    }

    $nombreArchivo = bin2hex(random_bytes(12)) . '.' . $tiposPermitidos[$tipo];
    $fotoMensaje = 'IMG/mensajes/' . $nombreArchivo;
    $rutaCompleta = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $fotoMensaje);
    $directorioMensajes = dirname($rutaCompleta);

    if (!is_dir($directorioMensajes) && !mkdir($directorioMensajes, 0755, true) && !is_dir($directorioMensajes)) {
        $_SESSION['notificacion_contacto'] = 'No fue posible preparar el almacenamiento de adjuntos.';
        header('Location: formulario.php');
        exit();
    }
    if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        $_SESSION['notificacion_contacto'] = 'No fue posible guardar la foto adjunta.';
        header('Location: formulario.php');
        exit();
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
    if ($fotoMensaje !== null) {
        @unlink(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $fotoMensaje));
    }
    $_SESSION['notificacion_contacto'] = 'No fue posible enviar el mensaje. Inténtalo nuevamente.';
    header('Location: formulario.php');
    exit();
}

$administradorCorreo = trim((string) (getenv('ADMIN_EMAIL') ?: 'tachiandrade79@gmail.com'));
if (filter_var($administradorCorreo, FILTER_VALIDATE_EMAIL)) {
    $aviso = "Recibiste un mensaje nuevo en el panel administrativo.\n\n" .
        "De: {$nombre} <{$correo}>\n" .
        "Asunto: {$asunto}\n\n" .
        "Mensaje:\n{$mensaje}\n" .
        ($fotoMensaje ? "\nImagen adjunta: {$fotoMensaje}\n" : '') .
        "\nEntra al panel de Always Beautiful para responder.";
    enviarNotificacion($administradorCorreo, 'Nuevo mensaje de contacto - Always Beautiful', $aviso);
}

enviarNotificacion($correo, 'Recibimos tu mensaje - Always Beautiful', "Hola {$nombre},\n\nRecibimos tu mensaje con asunto: {$asunto}. El equipo te responderá pronto.");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="2;url=inicio.php">
    <title>Mensaje enviado</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="mensaje-enviado">
        <h2>Mensaje enviado</h2>
        <p>Gracias por escribirnos. Te responderemos pronto.</p>
    </div>
</body>
</html>