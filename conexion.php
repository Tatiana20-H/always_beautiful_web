<?php
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = (int) (getenv('DB_PORT') ?: 3306);
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: '';
$dbName = getenv('DB_NAME') ?: 'alwaysbeautifuldb';

mysqli_report(MYSQLI_REPORT_OFF);

$conexion = mysqli_connect($dbHost, $dbUser, $dbPass, null, $dbPort);
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
    $adminEmail = strtolower(trim((string) (getenv('ADMIN_EMAIL') ?: 'tachiandrade79@gmail.com')));
    $adminPassword = (string) getenv('ADMIN_PASSWORD');
    $rolUsuario = 'usuario';
    if ($adminPassword === '' || !preg_match('/^(?=.*\p{L})(?=.*\d)(?=.*[^\p{L}\d\s]).{8,16}$/u', $adminPassword) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $stmt = mysqli_prepare($conexion, "UPDATE usuarios SET rol = ? WHERE rol = 'admin' AND correo <> ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ss', $rolUsuario, $adminEmail);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $stmt = mysqli_prepare($conexion, 'SELECT id, contrasena FROM usuarios WHERE correo = ? LIMIT 1');
    if (!$stmt) {
        return;
    }
    mysqli_stmt_bind_param($stmt, 's', $adminEmail);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $admin = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
    if (!$admin) {
        $nombre = 'Administrador';
        $rolAdmin = 'admin';
        $verificado = 1;
        $stmt = mysqli_prepare($conexion, 'INSERT INTO usuarios (nombre, correo, contrasena, rol, correo_verificado) VALUES (?, ?, ?, ?, ?)');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssssi', $nombre, $adminEmail, $hash, $rolAdmin, $verificado);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        return;
    }

    if (!password_verify($adminPassword, (string) $admin['contrasena']) || password_needs_rehash((string) $admin['contrasena'], PASSWORD_DEFAULT)) {
        $stmt = mysqli_prepare($conexion, "UPDATE usuarios SET contrasena = ?, rol = 'admin', correo_verificado = 1 WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $hash, $admin['id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    } else {
        $stmt = mysqli_prepare($conexion, "UPDATE usuarios SET rol = 'admin', correo_verificado = 1 WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $admin['id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

// Crear tabla de mensajes de contacto
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS mensajes_contacto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(254) NOT NULL,
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

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS canjes_puntos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    producto_id INT NOT NULL,
    puntos_usados INT UNSIGNED NOT NULL DEFAULT 1,
    estado ENUM('solicitado', 'entregado', 'cancelado') NOT NULL DEFAULT 'solicitado',
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_canjes_usuario (usuario_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

ensureAdminUser($conexion);
?>