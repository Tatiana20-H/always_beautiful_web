<?php
include("Seguridad.php");
include("../conexion.php");
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
        
        // Enviar email al usuario
        $email_asunto = "Re: " . htmlspecialchars($mensaje_original['asunto']) . " - Always Beautiful";
        $email_cuerpo = "Hola " . htmlspecialchars($mensaje_original['nombre']) . ",\n\n";
        $email_cuerpo .= "El administrador ha respondido a tu mensaje:\n\n";
        $email_cuerpo .= "--- RESPUESTA DEL ADMINISTRADOR ---\n";
        $email_cuerpo .= $respuesta . "\n";
        $email_cuerpo .= "--- FIN DE RESPUESTA ---\n\n";
        $email_cuerpo .= "Puedes ver tu respuesta completa en tu perfil en nuestro sitio.\n\n";
        $email_cuerpo .= "Saludos,\nEquipo Always Beautiful";
        
        $email_headers = "From: AdminAlways@gmail.com\r\n";
        $email_headers .= "Reply-To: AdminAlways@gmail.com\r\n";
        $email_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        @mail($mensaje_original['correo'], $email_asunto, $email_cuerpo, $email_headers);
        
        $_SESSION['exito'] = "Respuesta enviada con éxito al usuario.";
    } else {
        $_SESSION['error'] = "Error al enviar la respuesta.";
    }
    
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['error'] = "Error en la base de datos.";
}

header('Location: mensajes.php');
?>
