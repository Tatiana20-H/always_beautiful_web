<?php
include("Seguridad.php");
include("../conexion.php");
$conexion = $GLOBALS['conexion'];

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
    exit;
}

$mensaje_id = intval($_GET['id']);

// Obtener mensaje
$sql = "SELECT * FROM mensajes_contacto WHERE id = $mensaje_id";
$resultado = mysqli_query($conexion, $sql);

if ($resultado && mysqli_num_rows($resultado) > 0) {
    $mensaje = mysqli_fetch_assoc($resultado);
    
    // Obtener respuestas
    $sql_respuestas = "SELECT admin_id, respuesta, fecha_creacion FROM respuestas_mensajes WHERE mensaje_id = $mensaje_id ORDER BY fecha_creacion ASC";
    $resultado_respuestas = mysqli_query($conexion, $sql_respuestas);
    
    $respuestas = [];
    while ($respuesta = mysqli_fetch_assoc($resultado_respuestas)) {
        $respuestas[] = [
            'texto' => $respuesta['respuesta'],
            'fecha' => date('d/m/Y H:i', strtotime($respuesta['fecha_creacion']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'nombre' => htmlspecialchars($mensaje['nombre']),
        'correo' => htmlspecialchars($mensaje['correo']),
        'asunto' => htmlspecialchars($mensaje['asunto']),
        'mensaje' => htmlspecialchars($mensaje['mensaje']),
        'foto_mensaje' => !empty($mensaje['foto_mensaje']) ? htmlspecialchars($mensaje['foto_mensaje']) : null,
        'fecha' => date('d/m/Y H:i', strtotime($mensaje['fecha_creacion'])),
        'respuestas' => $respuestas
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Mensaje no encontrado']);
}
?>
