<?php
$columnaVistoRespuesta = mysqli_query($conexion, "SHOW COLUMNS FROM respuestas_mensajes LIKE 'visto_usuario'");
if ($columnaVistoRespuesta && mysqli_num_rows($columnaVistoRespuesta) === 0) {
    mysqli_query($conexion, "ALTER TABLE respuestas_mensajes ADD COLUMN visto_usuario TINYINT(1) NOT NULL DEFAULT 0");
}

$columnaUsuarioRespuesta = mysqli_query($conexion, "SHOW COLUMNS FROM respuestas_mensajes LIKE 'usuario_id'");
if ($columnaUsuarioRespuesta && mysqli_num_rows($columnaUsuarioRespuesta) === 0) {
    mysqli_query($conexion, "ALTER TABLE respuestas_mensajes ADD COLUMN usuario_id INT NULL AFTER admin_id");
}

$columnaRemitenteRespuesta = mysqli_query($conexion, "SHOW COLUMNS FROM respuestas_mensajes LIKE 'remitente'");
if ($columnaRemitenteRespuesta && mysqli_num_rows($columnaRemitenteRespuesta) === 0) {
    mysqli_query($conexion, "ALTER TABLE respuestas_mensajes ADD COLUMN remitente ENUM('admin', 'usuario') NOT NULL DEFAULT 'admin'");
}

$resultadoAdminRespuesta = mysqli_query($conexion, "SHOW COLUMNS FROM respuestas_mensajes LIKE 'admin_id'");
$definicionAdminRespuesta = $resultadoAdminRespuesta ? mysqli_fetch_assoc($resultadoAdminRespuesta) : null;
if ($definicionAdminRespuesta && strtoupper((string) $definicionAdminRespuesta['Null']) === 'NO') {
    mysqli_query($conexion, "ALTER TABLE respuestas_mensajes MODIFY admin_id INT NULL");
}
