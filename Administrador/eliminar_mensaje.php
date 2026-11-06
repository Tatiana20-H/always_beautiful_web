<?php
include_once("Seguridad.php");
include_once("../conexion.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['csrf_mensajes']) || !hash_equals($_SESSION['csrf_mensajes'], (string) ($_POST['csrf'] ?? ''))) {
    http_response_code(403);
    exit('Solicitud no válida.');
}

$mensaje_id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($mensaje_id === false) {
    $_SESSION['error'] = 'ID de mensaje no válido.';
    header('Location: mensajes.php');
    exit;
}

$conexion->begin_transaction();
$stmt = $conexion->prepare('DELETE FROM mensajes_contacto WHERE id = ?');
$stmt->bind_param('i', $mensaje_id);
if ($stmt->execute() && $stmt->affected_rows === 1) {
    $conexion->commit();
    $_SESSION['exito'] = 'Mensaje eliminado correctamente.';
} else {
    $conexion->rollback();
    $_SESSION['error'] = 'No se encontró el mensaje o no fue posible eliminarlo.';
}
$stmt->close();

header('Location: mensajes.php');
?>
