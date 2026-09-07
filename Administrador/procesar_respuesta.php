<?php
include("Seguridad.php");
include("../conexion.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mensajes.php');
    exit;
}

$mensaje_id = intval($_POST['mensaje_id'] ?? 0);
$respuesta = mysqli_real_escape_string($conexion, $_POST['respuesta'] ?? '');
$admin_id = intval($_SESSION['id'] ?? 0);

if (empty($mensaje_id) || empty($respuesta) || empty($admin_id)) {
    $_SESSION['error'] = "❌ Datos incompletos.";
    header('Location: mensajes.php');
    exit;
}

// Obtener datos del mensaje original
$sql = "SELECT correo, nombre, asunto FROM mensajes_contacto WHERE id = $mensaje_id";
$resultado = mysqli_query($conexion, $sql);

if (!$resultado || mysqli_num_rows($resultado) === 0) {
    $_SESSION['error'] = "❌ Mensaje no encontrado.";
    header('Location: mensajes.php');
    exit;
}

$mensaje_original = mysqli_fetch_assoc($resultado);

// Insertar respuesta en la base de datos
$sql_insert = "INSERT INTO respuestas_mensajes (mensaje_id, admin_id, respuesta) VALUES (?, ?, ?)";
if ($stmt = mysqli_prepare($conexion, $sql_insert)) {
    mysqli_stmt_bind_param($stmt, "iss", $mensaje_id, $admin_id, $respuesta);
    
    if (mysqli_stmt_execute($stmt)) {
        // Actualizar estado a respondido
        mysqli_query($conexion, "UPDATE mensajes_contacto SET estado='respondido' WHERE id=$mensaje_id");
        
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
        
        $_SESSION['exito'] = "✅ Respuesta enviada con éxito al usuario.";
    } else {
        $_SESSION['error'] = "❌ Error al enviar la respuesta.";
    }
    
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['error'] = "❌ Error en la base de datos.";
}

header('Location: mensajes.php');
?>
