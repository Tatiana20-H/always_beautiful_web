<?php
include("Seguridad.php");
include("../conexion.php");

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "❌ ID de mensaje no proporcionado.";
    header('Location: mensajes.php');
    exit;
}

$mensaje_id = intval($_GET['id']);

// Eliminar respuestas primero
mysqli_query($conexion, "DELETE FROM respuestas_mensajes WHERE mensaje_id = $mensaje_id");

// Eliminar mensaje
$sql = "DELETE FROM mensajes_contacto WHERE id = $mensaje_id";

if (mysqli_query($conexion, $sql)) {
    $_SESSION['exito'] = "✅ Mensaje eliminado correctamente.";
} else {
    $_SESSION['error'] = "❌ Error al eliminar el mensaje.";
}

header('Location: mensajes.php');
?>
