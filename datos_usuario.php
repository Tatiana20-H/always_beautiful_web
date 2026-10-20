<?php
function asegurarCamposUsuario($conexion) {
    $campos = [
        'fecha_nacimiento' => "ALTER TABLE usuarios ADD COLUMN fecha_nacimiento DATE NULL",
        'codigo_recuperacion' => "ALTER TABLE usuarios ADD COLUMN codigo_recuperacion VARCHAR(6) NULL",
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

    $codigo = mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE 'codigo_recuperacion'");
    if ($codigo && ($columna = mysqli_fetch_assoc($codigo)) && stripos($columna['Type'], 'varchar(4)') === 0) {
        mysqli_query($conexion, 'ALTER TABLE usuarios MODIFY codigo_recuperacion VARCHAR(6) NULL');
    }
}

function enviarNotificacion($destinatario, $asunto, $mensaje) {
    $autoload = getenv('MAILER_AUTOLOAD') ?: '/opt/always-beautiful-vendor/autoload.php';
    $usuarioCorreo = getenv('MAIL_USERNAME') ?: getenv('ALWAYS_BEAUTIFUL_EMAIL');
    $claveCorreo = getenv('MAIL_PASSWORD');
    if (!is_file($autoload) || !$usuarioCorreo || !$claveCorreo) {
        error_log('Correo no enviado: configura MAIL_USERNAME y MAIL_PASSWORD para SMTP.');
        return false;
    }

    require_once $autoload;
    $correo = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $correo->isSMTP();
        $correo->Host = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
        $correo->SMTPAuth = true;
        $correo->Username = $usuarioCorreo;
        $correo->Password = $claveCorreo;
        $correo->Port = (int) (getenv('MAIL_PORT') ?: 587);
        $correo->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $correo->CharSet = 'UTF-8';
        $correo->setFrom($usuarioCorreo, 'Always Beautiful');
        $correo->addAddress($destinatario);
        $correo->Subject = $asunto;
        $correo->Body = $mensaje;
        $correo->isHTML(false);
        return $correo->send();
    } catch (Throwable $error) {
        error_log('Error enviando correo SMTP: ' . $error->getMessage());
        return false;
    }
}

function guardarDatosUsuario($conexion) {
    if (empty($_SESSION['usuario_id'])) {
        return;
    }

    $carrito = json_encode($_SESSION['carrito'] ?? [], JSON_UNESCAPED_UNICODE);
    $historial = json_encode($_SESSION['historial'] ?? [], JSON_UNESCAPED_UNICODE);
    $foto = $_SESSION['foto_perfil'] ?? null;
    $usuarioId = (int) $_SESSION['usuario_id'];
    mysqli_begin_transaction($conexion);

    $stmt = mysqli_prepare($conexion, "UPDATE usuarios SET carrito_json = ?, historial_json = ?, foto_perfil = ? WHERE id = ?");
    if (!$stmt) {
        mysqli_rollback($conexion);
        return;
    }
    mysqli_stmt_bind_param($stmt, "sssi", $carrito, $historial, $foto, $usuarioId);
    $actualizado = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if (!$actualizado) {
        mysqli_rollback($conexion);
        return;
    }

    $eliminarCarrito = mysqli_prepare($conexion, "DELETE FROM carrito WHERE usuario_id = ?");
    if (!$eliminarCarrito) {
        mysqli_rollback($conexion);
        return;
    }
    mysqli_stmt_bind_param($eliminarCarrito, "i", $usuarioId);
    $carritoEliminado = mysqli_stmt_execute($eliminarCarrito);
    mysqli_stmt_close($eliminarCarrito);
    if (!$carritoEliminado) {
        mysqli_rollback($conexion);
        return;
    }

    $buscarProducto = mysqli_prepare($conexion, "SELECT id FROM productos WHERE nombre = ? LIMIT 1");
    $insertarLinea = mysqli_prepare($conexion, "INSERT INTO carrito (usuario_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    if (!$buscarProducto || !$insertarLinea) {
        if ($buscarProducto) mysqli_stmt_close($buscarProducto);
        if ($insertarLinea) mysqli_stmt_close($insertarLinea);
        mysqli_rollback($conexion);
        return;
    }

    foreach ($_SESSION['carrito'] ?? [] as $nombreProducto => $producto) {
        mysqli_stmt_bind_param($buscarProducto, "s", $nombreProducto);
        if (!mysqli_stmt_execute($buscarProducto)) {
            mysqli_stmt_close($buscarProducto);
            mysqli_stmt_close($insertarLinea);
            mysqli_rollback($conexion);
            return;
        }
        $resultadoProducto = mysqli_stmt_get_result($buscarProducto);
        $filaProducto = $resultadoProducto ? mysqli_fetch_assoc($resultadoProducto) : null;
        if (!$filaProducto) {
            continue;
        }

        $productoId = (int) $filaProducto['id'];
        $cantidad = max(1, (int) ($producto['cantidad'] ?? 1));
        $precio = max(0, (int) ($producto['precio'] ?? 0));
        mysqli_stmt_bind_param($insertarLinea, "iiii", $usuarioId, $productoId, $cantidad, $precio);
        if (!mysqli_stmt_execute($insertarLinea)) {
            mysqli_stmt_close($buscarProducto);
            mysqli_stmt_close($insertarLinea);
            mysqli_rollback($conexion);
            return;
        }
    }

    mysqli_stmt_close($buscarProducto);
    mysqli_stmt_close($insertarLinea);
    mysqli_commit($conexion);
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
    if (empty($_SESSION['carrito'])) {
        $stmtCarrito = mysqli_prepare($conexion, "SELECT p.nombre, p.imagen, c.cantidad, c.precio_unitario FROM carrito c JOIN productos p ON p.id = c.producto_id WHERE c.usuario_id = ? ORDER BY c.fecha_agregado");
        if ($stmtCarrito) {
            mysqli_stmt_bind_param($stmtCarrito, "i", $usuarioId);
            mysqli_stmt_execute($stmtCarrito);
            $resultadoCarrito = mysqli_stmt_get_result($stmtCarrito);
            while ($linea = mysqli_fetch_assoc($resultadoCarrito)) {
                $_SESSION['carrito'][$linea['nombre']] = [
                    'precio' => (int) $linea['precio_unitario'],
                    'cantidad' => (int) $linea['cantidad'],
                    'imagen' => $linea['imagen'] ?? ''
                ];
            }
            mysqli_stmt_close($stmtCarrito);
        }
    }
    $_SESSION['historial'] = !empty($datos['historial_json']) ? (json_decode($datos['historial_json'], true) ?: []) : [];
}
