<?php
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['usuario_id'])) {
    header('Location: formulario.php');
    exit();
}

$nombre = trim($_SESSION['nombre'] ?? '');
$correo = trim($_SESSION['correo'] ?? '');
$asunto = trim($_POST['asunto'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');
$fotoMensaje = null;

if ($asunto === '' || $mensaje === '') {
    $_SESSION['notificacion_contacto'] = 'Escribe un asunto y un mensaje antes de enviar.';
    header('Location: formulario.php');
    exit();
}

if (!empty($_FILES['foto_mensaje']['name'])) {
    $archivo = $_FILES['foto_mensaje'];
    $tiposPermitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $tipo = mime_content_type($archivo['tmp_name']);

    if ($archivo['error'] !== UPLOAD_ERR_OK || !isset($tiposPermitidos[$tipo]) || $archivo['size'] > 5 * 1024 * 1024) {
        $_SESSION['notificacion_contacto'] = 'La foto debe ser JPG, PNG o WEBP y pesar menos de 5 MB.';
        header('Location: formulario.php');
        exit();
    }

    $nombreArchivo = bin2hex(random_bytes(12)) . '.' . $tiposPermitidos[$tipo];
    $fotoMensaje = 'IMG/mensajes/' . $nombreArchivo;
    $rutaCompleta = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $fotoMensaje);

    if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        $_SESSION['notificacion_contacto'] = 'No fue posible guardar la foto adjunta.';
        header('Location: formulario.php');
        exit();
    }
}

$sql = "INSERT INTO mensajes_contacto (usuario_id, nombre, correo, asunto, mensaje, foto_mensaje) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conexion, $sql);
$usuarioId = (int) $_SESSION['usuario_id'];

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'isssss', $usuarioId, $nombre, $correo, $asunto, $mensaje, $fotoMensaje);
    $enviado = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
} else {
    $enviado = false;
}

if (!$enviado) {
    $_SESSION['notificacion_contacto'] = 'No fue posible enviar el mensaje. Inténtalo nuevamente.';
    header('Location: formulario.php');
    exit();
}
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