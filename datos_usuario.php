<?php
function asegurarCamposUsuario($conexion) {
    $campos = [
        'fecha_nacimiento' => "ALTER TABLE usuarios ADD COLUMN fecha_nacimiento DATE NULL",
        'codigo_recuperacion' => "ALTER TABLE usuarios ADD COLUMN codigo_recuperacion VARCHAR(4) NULL",
        'codigo_expira' => "ALTER TABLE usuarios ADD COLUMN codigo_expira DATETIME NULL",
        'foto_perfil' => "ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL",
        'genero' => "ALTER TABLE usuarios ADD COLUMN genero ENUM('hombre', 'mujer') NULL",
        'carrito_json' => "ALTER TABLE usuarios ADD COLUMN carrito_json LONGTEXT NULL",
        'historial_json' => "ALTER TABLE usuarios ADD COLUMN historial_json LONGTEXT NULL"
    ];

    foreach ($campos as $campo => $sql) {
        $resultado = mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE '$campo'");
        if ($resultado && mysqli_num_rows($resultado) === 0) {
            mysqli_query($conexion, $sql);
        }
    }
}

function enviarNotificacion($destinatario, $asunto, $mensaje) {
    $remitente = getenv('ALWAYS_BEAUTIFUL_EMAIL') ?: 'no-reply@alwaysbeautiful.local';
    $cabeceras = "From: Always Beautiful <" . $remitente . ">\r\n";
    $cabeceras .= "Content-Type: text/plain; charset=UTF-8\r\n";
    return mail($destinatario, $asunto, $mensaje, $cabeceras);
}

function guardarDatosUsuario($conexion) {
    if (empty($_SESSION['usuario_id'])) {
        return;
    }

    $carrito = json_encode($_SESSION['carrito'] ?? [], JSON_UNESCAPED_UNICODE);
    $historial = json_encode($_SESSION['historial'] ?? [], JSON_UNESCAPED_UNICODE);
    $foto = $_SESSION['foto_perfil'] ?? null;
    $usuarioId = (int) $_SESSION['usuario_id'];
    $stmt = mysqli_prepare($conexion, "UPDATE usuarios SET carrito_json = ?, historial_json = ?, foto_perfil = ? WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssi", $carrito, $historial, $foto, $usuarioId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function cargarDatosUsuario($conexion, $usuarioId) {
    $stmt = mysqli_prepare($conexion, "SELECT fecha_nacimiento, genero, foto_perfil, carrito_json, historial_json FROM usuarios WHERE id = ?");
    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param($stmt, "i", $usuarioId);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $datos = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    $_SESSION['fecha_nacimiento'] = $datos['fecha_nacimiento'] ?? null;
    $_SESSION['genero'] = $datos['genero'] ?? null;
    $_SESSION['foto_perfil'] = $datos['foto_perfil'] ?? null;
    $_SESSION['carrito'] = !empty($datos['carrito_json']) ? (json_decode($datos['carrito_json'], true) ?: []) : [];
    $_SESSION['historial'] = !empty($datos['historial_json']) ? (json_decode($datos['historial_json'], true) ?: []) : [];
}
