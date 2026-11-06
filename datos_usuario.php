<?php
function asegurarCamposUsuario($conexion) {
    $campos = [
        'fecha_nacimiento' => "ALTER TABLE usuarios ADD COLUMN fecha_nacimiento DATE NULL",
        'codigo_recuperacion' => "ALTER TABLE usuarios ADD COLUMN codigo_recuperacion VARCHAR(255) NULL",
        'codigo_expira' => "ALTER TABLE usuarios ADD COLUMN codigo_expira DATETIME NULL",
        'intentos_recuperacion' => "ALTER TABLE usuarios ADD COLUMN intentos_recuperacion TINYINT UNSIGNED NOT NULL DEFAULT 0",
        'foto_perfil' => "ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL",
        'genero' => "ALTER TABLE usuarios ADD COLUMN genero ENUM('hombre', 'mujer') NULL",
        'carrito_json' => "ALTER TABLE usuarios ADD COLUMN carrito_json LONGTEXT NULL",
        'historial_json' => "ALTER TABLE usuarios ADD COLUMN historial_json LONGTEXT NULL",
        'correo_verificado' => "ALTER TABLE usuarios ADD COLUMN correo_verificado TINYINT(1) NOT NULL DEFAULT 0",
        'correo_padre' => "ALTER TABLE usuarios ADD COLUMN correo_padre VARCHAR(254) NULL",
        'padre_verificado' => "ALTER TABLE usuarios ADD COLUMN padre_verificado TINYINT(1) NOT NULL DEFAULT 0",
        'puntos' => "ALTER TABLE usuarios ADD COLUMN puntos INT UNSIGNED NOT NULL DEFAULT 0",
        'puntos_resto' => "ALTER TABLE usuarios ADD COLUMN puntos_resto INT UNSIGNED NOT NULL DEFAULT 0"
    ];

    foreach ($campos as $campo => $sql) {
        $resultado = mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE '$campo'");
        if ($resultado && mysqli_num_rows($resultado) === 0) {
            mysqli_query($conexion, $sql);
        }
    }

    mysqli_query($conexion, "ALTER TABLE usuarios MODIFY codigo_recuperacion VARCHAR(255) NULL");
    mysqli_query($conexion, "ALTER TABLE usuarios MODIFY correo_verificado TINYINT(1) NOT NULL DEFAULT 0");
    mysqli_query($conexion, "ALTER TABLE mensajes_contacto MODIFY correo VARCHAR(254) NOT NULL");
    mysqli_query($conexion, "ALTER TABLE usuarios MODIFY correo VARCHAR(254) NOT NULL");

    mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS registros_pendientes (
        correo VARCHAR(254) NOT NULL PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        contrasena VARCHAR(255) NOT NULL,
        fecha_nacimiento DATE NOT NULL,
        genero ENUM('hombre', 'mujer') NOT NULL,
        correo_padre VARCHAR(254) NULL,
        paso ENUM('correo', 'padre') NOT NULL DEFAULT 'correo',
        codigo_hash VARCHAR(255) NOT NULL,
        codigo_expira DATETIME NOT NULL,
        intentos TINYINT UNSIGNED NOT NULL DEFAULT 0,
        creado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function enviarNotificacion($destinatario, $asunto, $mensaje) {
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        error_log('PHPMailer no está instalado; reconstruye la imagen Docker.');
        return false;
    }
    require_once $autoload;

    $configuracionLocal = [];
    $archivoConfiguracion = __DIR__ . '/.env';
    if (is_readable($archivoConfiguracion)) {
        $valoresLocales = parse_ini_file($archivoConfiguracion, false, INI_SCANNER_RAW);
        if (is_array($valoresLocales)) {
            $configuracionLocal = $valoresLocales;
        }
    }
    $obtenerConfiguracion = static function ($nombre, $porDefecto = '') use ($configuracionLocal) {
        $valorEntorno = getenv($nombre);
        if ($valorEntorno !== false && $valorEntorno !== '') {
            return $valorEntorno;
        }
        return $configuracionLocal[$nombre] ?? $porDefecto;
    };

    $host = trim((string) $obtenerConfiguracion('SMTP_HOST', 'smtp.gmail.com'));
    $usuario = trim((string) $obtenerConfiguracion('SMTP_USER', $obtenerConfiguracion('ADMIN_EMAIL', 'tachiandrade79@gmail.com')));
    $contrasena = preg_replace('/\s+/', '', (string) $obtenerConfiguracion('SMTP_PASSWORD'));
    $remitente = trim((string) $obtenerConfiguracion('SMTP_FROM', $usuario));
    $puerto = (int) $obtenerConfiguracion('SMTP_PORT', 587);
    $cifrado = strtolower(trim((string) $obtenerConfiguracion('SMTP_ENCRYPTION', 'tls')));

    if ($usuario === '' || $contrasena === '' || !filter_var($destinatario, FILTER_VALIDATE_EMAIL) || !filter_var($remitente, FILTER_VALIDATE_EMAIL)) {
        error_log('Correo SMTP no configurado: define SMTP_USER/SMTP_FROM y una contraseña de aplicación en SMTP_PASSWORD.');
        return false;
    }

    try {
        $correo = new PHPMailer\PHPMailer\PHPMailer(true);
        $correo->isSMTP();
        $correo->Host = $host;
        $correo->SMTPAuth = true;
        $correo->Timeout = 15;
        $correo->Username = $usuario;
        $correo->Password = $contrasena;
        $correo->Port = $puerto;
        $correo->SMTPSecure = $cifrado === 'ssl'
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $correo->CharSet = 'UTF-8';
        $correo->setFrom($remitente, 'Always Beautiful');
        $correo->addAddress($destinatario);
        $correo->Subject = $asunto;
        $correo->Body = $mensaje;
        $correo->send();
        return true;
    } catch (Throwable $error) {
        error_log('No se pudo enviar correo SMTP: ' . $error->getMessage());
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
