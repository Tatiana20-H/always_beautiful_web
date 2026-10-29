<?php
include("Seguridad.php");
include("../conexion.php");
$conexion = $GLOBALS['conexion'];
require_once __DIR__ . '/../migracion_respuestas_leidas.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mensajes.php');
    exit;
}

$mensaje_id = intval($_POST['mensaje_id'] ?? 0);
$respuesta = trim((string) ($_POST['respuesta'] ?? ''));
$admin_id = intval($_SESSION['usuario_id'] ?? 0);

if (empty($mensaje_id) || empty($respuesta) || empty($admin_id)) {
    $_SESSION['error'] = "Datos incompletos.";
    header('Location: mensajes.php');
    exit;
}

// Obtener datos del mensaje original
$stmtMensaje = mysqli_prepare($conexion, 'SELECT correo, nombre, asunto FROM mensajes_contacto WHERE id = ? LIMIT 1');
if (!$stmtMensaje) {
    $_SESSION['error'] = "Mensaje no encontrado.";
    header('Location: mensajes.php');
    exit;
}
mysqli_stmt_bind_param($stmtMensaje, 'i', $mensaje_id);
mysqli_stmt_execute($stmtMensaje);
$resultado = mysqli_stmt_get_result($stmtMensaje);
$mensaje_original = $resultado ? mysqli_fetch_assoc($resultado) : null;
mysqli_stmt_close($stmtMensaje);

if (!$mensaje_original) {
    $_SESSION['error'] = "Mensaje no encontrado.";
    header('Location: mensajes.php');
    exit;
}

// Insertar respuesta en la base de datos
$sql_insert = "INSERT INTO respuestas_mensajes (mensaje_id, admin_id, respuesta) VALUES (?, ?, ?)";
if ($stmt = mysqli_prepare($conexion, $sql_insert)) {
    mysqli_stmt_bind_param($stmt, "iis", $mensaje_id, $admin_id, $respuesta);
    
    if (mysqli_stmt_execute($stmt)) {
        // Actualizar estado a respondido
        $stmtEstado = mysqli_prepare($conexion, "UPDATE mensajes_contacto SET estado='respondido' WHERE id = ?");
        if ($stmtEstado) {
            mysqli_stmt_bind_param($stmtEstado, 'i', $mensaje_id);
            mysqli_stmt_execute($stmtEstado);
            mysqli_stmt_close($stmtEstado);
        }
        
        $urlBandejaUsuario = urlAplicacion('/mis_respuestas.php');
        $emailCuerpo = "Hola " . $mensaje_original['nombre'] . ",\n\n";
        $emailCuerpo .= "El administrador respondió a tu mensaje sobre: " . $mensaje_original['asunto'] . "\n\n";
        $emailCuerpo .= "Respuesta:\n$respuesta\n\n";
        $emailCuerpo .= "Entra a Always Beautiful para continuar la conversación:\n$urlBandejaUsuario\n\n";
        $emailCuerpo .= "Saludos,\nEquipo Always Beautiful";
        $emailEnviado = enviarNotificacion($mensaje_original['correo'], 'El administrador respondió - Always Beautiful', $emailCuerpo);

        $_SESSION['exito'] = $emailEnviado
            ? "Respuesta guardada y aviso enviado al correo del usuario."
            : "Respuesta guardada en la página, pero no se pudo enviar el aviso por correo. Revisa la configuración SMTP.";
    } else {
        $_SESSION['error'] = "Error al enviar la respuesta.";
    }
    
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['error'] = "Error en la base de datos.";
}

header('Location: mensajes.php');
?>
