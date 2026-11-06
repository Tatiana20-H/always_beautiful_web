<?php
include_once("Seguridad.php");
include_once("../conexion.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mensajes.php');
    exit;
}

if (empty($_SESSION['csrf_mensajes']) || !hash_equals($_SESSION['csrf_mensajes'], (string) ($_POST['csrf'] ?? ''))) {
    http_response_code(403);
    exit('Solicitud no válida. Recarga la bandeja e inténtalo de nuevo.');
}

$mensaje_id = intval($_POST['mensaje_id'] ?? 0);
$respuesta = trim($_POST['respuesta'] ?? '');
$admin_id = (int) ($_SESSION['usuario_id'] ?? 0);

if (empty($mensaje_id) || $respuesta === '' || strlen($respuesta) > 5000 || empty($admin_id)) {
    $_SESSION['error'] = 'Datos incompletos o respuesta demasiado larga.';
    header('Location: mensajes.php');
    exit;
}

// Obtener datos del mensaje original
$stmtMensaje = mysqli_prepare($conexion, 'SELECT correo, nombre, asunto FROM mensajes_contacto WHERE id = ?');
mysqli_stmt_bind_param($stmtMensaje, 'i', $mensaje_id);
mysqli_stmt_execute($stmtMensaje);
$resultado = mysqli_stmt_get_result($stmtMensaje);

if (!$resultado || mysqli_num_rows($resultado) === 0) {
    $_SESSION['error'] = 'Mensaje no encontrado.';
    header('Location: mensajes.php');
    exit;
}

$mensaje_original = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($stmtMensaje);

// Insertar respuesta en la base de datos
$conexion->begin_transaction();
$sql_insert = "INSERT INTO respuestas_mensajes (mensaje_id, admin_id, respuesta) VALUES (?, ?, ?)";
if ($stmt = mysqli_prepare($conexion, $sql_insert)) {
    mysqli_stmt_bind_param($stmt, "iis", $mensaje_id, $admin_id, $respuesta);
    
    if (mysqli_stmt_execute($stmt)) {
        // Actualizar estado a respondido
        $stmtEstado = mysqli_prepare($conexion, "UPDATE mensajes_contacto SET estado='respondido' WHERE id = ?");
        mysqli_stmt_bind_param($stmtEstado, 'i', $mensaje_id);
        $estadoActualizado = mysqli_stmt_execute($stmtEstado);
        mysqli_stmt_close($stmtEstado);
        if ($estadoActualizado) {
            $conexion->commit();
        } else {
            $conexion->rollback();
            $_SESSION['error'] = 'No se pudo actualizar el estado del mensaje.';
            header('Location: mensajes.php');
            exit;
        }
        // Enviar email al usuario
        $email_asunto = "Re: " . $mensaje_original['asunto'] . " - Always Beautiful";
        $email_cuerpo = "Hola " . $mensaje_original['nombre'] . ",\n\n";
        $email_cuerpo .= "El administrador ha respondido a tu mensaje:\n\n";
        $email_cuerpo .= "--- RESPUESTA DEL ADMINISTRADOR ---\n";
        $email_cuerpo .= $respuesta . "\n";
        $email_cuerpo .= "--- FIN DE RESPUESTA ---\n\n";
        $email_cuerpo .= "Puedes ver tu respuesta completa en tu perfil en nuestro sitio.\n\n";
        $email_cuerpo .= "Saludos,\nEquipo Always Beautiful";
        
        $_SESSION['exito'] = enviarNotificacion($mensaje_original['correo'], $email_asunto, $email_cuerpo)
            ? 'Respuesta guardada y enviada al correo del usuario.'
            : 'Respuesta guardada en el panel; no se pudo enviar el correo. Verifica SMTP.';
    } else {
        $conexion->rollback();
        $_SESSION['error'] = 'Error al guardar la respuesta.';
    }
    
    mysqli_stmt_close($stmt);
} else {
    $conexion->rollback();
    $_SESSION['error'] = 'Error en la base de datos.';
}

header('Location: mensajes.php');
?>
