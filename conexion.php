<?php
$dbHost = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "AlwaysBeautifulDB";

mysqli_report(MYSQLI_REPORT_OFF);

$conexion = mysqli_connect($dbHost, $dbUser, $dbPass);
if (!$conexion) {
    die("Error al conectar al servidor MySQL: " . mysqli_connect_error());
}

if (!@mysqli_select_db($conexion, $dbName)) {
    $errno = mysqli_errno($conexion);
    if ($errno === 1049) {
        if (!mysqli_query($conexion, "CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
            die("No se pudo crear la base de datos: " . mysqli_error($conexion));
        }
        if (!@mysqli_select_db($conexion, $dbName)) {
            die("No se pudo seleccionar la base de datos `$dbName`: " . mysqli_error($conexion));
        }

        $sqlFile = __DIR__ . DIRECTORY_SEPARATOR . 'Base de datos de alwaysbeautifulweb.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            if ($sql === false) {
                die("No se pudo leer el archivo SQL: $sqlFile");
            }

            $sql = preg_replace('/^USE\\s+`?'.preg_quote($dbName, '/').'`?;$/mi', '', $sql);

            if (!mysqli_multi_query($conexion, $sql)) {
                die("Error al importar la base de datos desde SQL: " . mysqli_error($conexion));
            }

            while (mysqli_more_results($conexion) && mysqli_next_result($conexion)) {
                // Consumir todos los resultados.
            }
        }
    } else {
        die("Error de conexión a la base de datos: " . mysqli_error($conexion));
    }
}

mysqli_set_charset($conexion, 'utf8mb4');

require_once __DIR__ . '/datos_usuario.php';
asegurarCamposUsuario($conexion);

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS reseñas_productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    nombre_autor VARCHAR(100) NULL,
    producto_nombre VARCHAR(150) NOT NULL,
    estrellas TINYINT NOT NULL,
    comentario TEXT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY reseña_usuario_producto (usuario_id, producto_nombre),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
mysqli_query($conexion, "ALTER TABLE reseñas_productos MODIFY usuario_id INT NULL");
mysqli_query($conexion, "ALTER TABLE reseñas_productos ADD COLUMN nombre_autor VARCHAR(100) NULL");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS votos_reseñas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reseña_id INT NOT NULL,
    usuario_id INT NOT NULL,
    voto ENUM('like', 'dislike') NOT NULL,
    UNIQUE KEY voto_usuario_reseña (reseña_id, usuario_id),
    FOREIGN KEY (reseña_id) REFERENCES reseñas_productos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS reportes_reseñas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reseña_id INT NOT NULL,
    usuario_id INT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY reporte_usuario_reseña (reseña_id, usuario_id),
    FOREIGN KEY (reseña_id) REFERENCES reseñas_productos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function ensureAdminUser($conexion) {
    $adminName = 'Admin';
    $adminEmail = 'AdminAlways@gmail.com';
    $adminPassword = '123456';
    $adminRole = 'admin';
    $adminPasswordHash = password_hash($adminPassword, PASSWORD_BCRYPT);

    $sql = "SELECT id, contrasena FROM usuarios WHERE correo = ? AND rol = ?";
    if ($stmt = mysqli_prepare($conexion, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $adminEmail, $adminRole);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) === 0) {
            mysqli_stmt_close($stmt);
            $sql_insert = "INSERT INTO usuarios (nombre, correo, contrasena, rol) VALUES (?, ?, ?, ?)";
            if ($insert = mysqli_prepare($conexion, $sql_insert)) {
                mysqli_stmt_bind_param($insert, "ssss", $adminName, $adminEmail, $adminPasswordHash, $adminRole);
                mysqli_stmt_execute($insert);
                mysqli_stmt_close($insert);
            }
        } else {
            mysqli_stmt_bind_result($stmt, $adminId, $storedHash);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if (!is_string($storedHash) || !password_verify($adminPassword, $storedHash) || password_needs_rehash($storedHash, PASSWORD_BCRYPT)) {
                $sql_update = "UPDATE usuarios SET contrasena = ? WHERE id = ?";
                if ($update = mysqli_prepare($conexion, $sql_update)) {
                    mysqli_stmt_bind_param($update, "si", $adminPasswordHash, $adminId);
                    mysqli_stmt_execute($update);
                    mysqli_stmt_close($update);
                }
            }
        }
    }
}

// Crear tabla de mensajes de contacto
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS mensajes_contacto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL,
    asunto VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    estado ENUM('no_leido', 'leido', 'respondido') DEFAULT 'no_leido',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha_creacion),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$columnaFoto = mysqli_query($conexion, "SHOW COLUMNS FROM mensajes_contacto LIKE 'foto_mensaje'");
if ($columnaFoto && mysqli_num_rows($columnaFoto) === 0) {
    mysqli_query($conexion, "ALTER TABLE mensajes_contacto ADD COLUMN foto_mensaje VARCHAR(255) NULL");
}

// Crear tabla de respuestas de mensajes
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS respuestas_mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mensaje_id INT NOT NULL,
    admin_id INT NOT NULL,
    respuesta TEXT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mensaje_id) REFERENCES mensajes_contacto(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

ensureAdminUser($conexion);
?>