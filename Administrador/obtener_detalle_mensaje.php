<?php
include_once("Seguridad.php");
include_once("../conexion.php");
$conexion = $GLOBALS['conexion'];

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
    exit;
}

$mensaje_id = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($mensaje_id === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

$stmtMensaje = $conexion->prepare('SELECT * FROM mensajes_contacto WHERE id = ?');
$stmtMensaje->bind_param('i', $mensaje_id);
$stmtMensaje->execute();
$resultado = $stmtMensaje->get_result();

if ($resultado && mysqli_num_rows($resultado) > 0) {
    $mensaje = mysqli_fetch_assoc($resultado);
    $stmtMensaje->close();
    
    $stmtRespuestas = $conexion->prepare('SELECT admin_id, respuesta, fecha_creacion FROM respuestas_mensajes WHERE mensaje_id = ? ORDER BY id ASC');
    $stmtRespuestas->bind_param('i', $mensaje_id);
    $stmtRespuestas->execute();
    $resultado_respuestas = $stmtRespuestas->get_result();
    
    $respuestas = [];
    while ($respuesta = mysqli_fetch_assoc($resultado_respuestas)) {
        $respuestas[] = [
            'texto' => $respuesta['respuesta'],
            'fecha' => date('d/m/Y H:i', strtotime($respuesta['fecha_creacion']))
        ];
    }
    $stmtRespuestas->close();
    
    echo json_encode([
        'success' => true,
        'nombre' => $mensaje['nombre'],
        'correo' => $mensaje['correo'],
        'asunto' => $mensaje['asunto'],
        'mensaje' => $mensaje['mensaje'],
        'foto_mensaje' => !empty($mensaje['foto_mensaje']) ? $mensaje['foto_mensaje'] : null,
        'fecha' => date('d/m/Y H:i', strtotime($mensaje['fecha_creacion'])),
        'respuestas' => $respuestas
    ]);
} else {
    $stmtMensaje->close();
    echo json_encode(['success' => false, 'error' => 'Mensaje no encontrado']);
}
?>
