<?php
date_default_timezone_set('America/Bogota');
// Lee las variables del entorno de Docker o usa los valores por defecto del compose.yaml
$dbHost = getenv('DB_HOST') ?: 'db';
$dbPort = (int) (getenv('DB_PORT') ?: 3306);
$dbUser = getenv('DB_USER') ?: 'root';

// Modificación clave: Si la variable existe pero está vacía, se asigna un string vacío ''
$dbPass = getenv('DB_PASSWORD');
if ($dbPass === false) {
    $dbPass = '';
}

$dbName = getenv('DB_NAME') ?: 'alwaysbeautifuldb';

if (!function_exists('mysqli_connect')) {
    die('La extensión mysqli no está instalada en PHP. Ejecuta: sudo docker compose up -d --build');
}

mysqli_report(MYSQLI_REPORT_OFF);

$conexion = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
if (!$conexion) {
    die("No se pudo conectar a MySQL. Verifica DB_HOST, DB_USER, DB_PASSWORD y DB_PORT en la configuración del servidor.");
}

if (!@mysqli_select_db($conexion, $dbName)) {
    $errno = mysqli_errno($conexion);
    if ($errno === 1049) {
        if (!mysqli_query($conexion, "CREATE DATABASE IF NOT EXISTS `$dbName`")) {
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
mysqli_query($conexion, "SET time_zone = '-05:00'");

$tablaUsuarios = mysqli_query($conexion, "SHOW TABLES LIKE 'usuarios'");
if (!$tablaUsuarios || mysqli_num_rows($tablaUsuarios) === 0) {
    $sqlFile = __DIR__ . DIRECTORY_SEPARATOR . 'Base de datos de alwaysbeautifulweb.sql';
    if (!file_exists($sqlFile)) {
        die("No se encontró el archivo de estructura de la base de datos.");
    }

    $sql = file_get_contents($sqlFile);
    $sql = preg_replace('/^CREATE DATABASE IF NOT EXISTS.*?;\s*/is', '', $sql, 1);
    $sql = preg_replace('/^USE\s+`?'.preg_quote($dbName, '/').'`?;\s*/mi', '', $sql);
    if ($sql === false || !mysqli_multi_query($conexion, $sql)) {
        die("Error al importar la estructura de la base de datos: " . mysqli_error($conexion));
    }

    while (mysqli_more_results($conexion)) {
        mysqli_next_result($conexion);
    }
}

require_once __DIR__ . '/datos_usuario.php';
asegurarCamposUsuario($conexion);

function asegurarCamposProducto($conexion) {
    $campos = [
        ['nombre' => 'descripcion', 'sql' => "ALTER TABLE productos ADD COLUMN descripcion TEXT NULL AFTER categoria"],
        ['nombre' => 'categoria', 'sql' => "ALTER TABLE productos ADD COLUMN categoria VARCHAR(50) NOT NULL DEFAULT 'Piel' AFTER stock"],
        ['nombre' => 'imagen', 'sql' => "ALTER TABLE productos ADD COLUMN imagen VARCHAR(255) NULL AFTER categoria"],
        ['nombre' => 'activo', 'sql' => "ALTER TABLE productos ADD COLUMN activo BOOLEAN NOT NULL DEFAULT TRUE AFTER imagen"],
    ];

    foreach ($campos as $campo) {
        $resultado = mysqli_query($conexion, "SHOW COLUMNS FROM productos LIKE '" . mysqli_real_escape_string($conexion, $campo['nombre']) . "'");
        if (!$resultado || mysqli_num_rows($resultado) === 0) {
            mysqli_query($conexion, $campo['sql']);
        }
    }
}

asegurarCamposProducto($conexion);

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS promociones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    categoria VARCHAR(50) NOT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    imagen VARCHAR(255) NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS combos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    imagen VARCHAR(255) NULL,
    productos TEXT,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

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
) ENGINE=InnoDB");
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
) ENGINE=InnoDB");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS reportes_reseñas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reseña_id INT NOT NULL,
    usuario_id INT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY reporte_usuario_reseña (reseña_id, usuario_id),
    FOREIGN KEY (reseña_id) REFERENCES reseñas_productos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB");

function ensureAdminUser($conexion) {
    $correoAdmin = 'tachiandrade79@gmail.com';
    $nombreAdmin = 'Tachi Andrade';
    mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS configuracion_admin (id TINYINT NOT NULL PRIMARY KEY, inicializado TINYINT(1) NOT NULL DEFAULT 0) ENGINE=InnoDB");
    mysqli_query($conexion, "INSERT IGNORE INTO configuracion_admin (id, inicializado) VALUES (1, 0)");
    $estado = mysqli_query($conexion, "SELECT inicializado FROM configuracion_admin WHERE id = 1");
    $inicializarCredenciales = $estado && (int) mysqli_fetch_assoc($estado)['inicializado'] === 0;
    mysqli_query($conexion, "UPDATE usuarios SET rol = 'usuario' WHERE rol = 'admin' AND LOWER(correo) <> 'tachiandrade79@gmail.com'");
    $stmt = mysqli_prepare($conexion, 'SELECT id, nombre, correo, contrasena, rol FROM usuarios WHERE LOWER(correo) = ? LIMIT 1');
    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param($stmt, 's', $correoAdmin);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $admin = $resultado ? mysqli_fetch_assoc($resultado) : null;
    mysqli_stmt_close($stmt);

    if ($admin) {
        if ($inicializarCredenciales) {
            $hashAdmin = password_hash('123456', PASSWORD_BCRYPT);
            $actualizar = mysqli_prepare($conexion, "UPDATE usuarios SET nombre = ?, correo = ?, contrasena = ?, rol = 'admin' WHERE id = ?");
            if ($actualizar) {
                mysqli_stmt_bind_param($actualizar, 'sssi', $nombreAdmin, $correoAdmin, $hashAdmin, $admin['id']);
                mysqli_stmt_execute($actualizar);
                mysqli_stmt_close($actualizar);
            }
        } elseif ($admin['rol'] !== 'admin' || $admin['nombre'] !== $nombreAdmin || $admin['correo'] !== $correoAdmin) {
            $actualizar = mysqli_prepare($conexion, "UPDATE usuarios SET nombre = ?, correo = ?, rol = 'admin' WHERE id = ?");
            if ($actualizar) {
                mysqli_stmt_bind_param($actualizar, 'ssi', $nombreAdmin, $correoAdmin, $admin['id']);
                mysqli_stmt_execute($actualizar);
                mysqli_stmt_close($actualizar);
            }
        }
        if ($inicializarCredenciales) mysqli_query($conexion, "UPDATE configuracion_admin SET inicializado = 1 WHERE id = 1");
        return;
    }

    $hashAdmin = password_hash('123456', PASSWORD_BCRYPT);
    $insertar = mysqli_prepare($conexion, "INSERT INTO usuarios (nombre, correo, contrasena, rol) VALUES (?, ?, ?, 'admin')");
    if ($insertar) {
        mysqli_stmt_bind_param($insertar, 'sss', $nombreAdmin, $correoAdmin, $hashAdmin);
        $insertado = mysqli_stmt_execute($insertar);
        mysqli_stmt_close($insertar);
        if ($insertado) mysqli_query($conexion, "UPDATE configuracion_admin SET inicializado = 1 WHERE id = 1");
    }
}

function ensureDefaultUser($conexion) {
    $userName = 'Tatiana';
    $userEmail = 'htatiana089@gmail.com';
    $userPassword = '123456';
    $userRole = 'usuario';
    $passwordHash = password_hash($userPassword, PASSWORD_BCRYPT);

    $sql = "SELECT id, nombre, contrasena FROM usuarios WHERE correo = ? LIMIT 1";
    if ($stmt = mysqli_prepare($conexion, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $userEmail);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        if ($resultado && $resultado->num_rows > 0) {
            $usuario = $resultado->fetch_assoc();
            if ($usuario['nombre'] !== $userName || !password_verify($userPassword, $usuario['contrasena'])) {
                $actualizar = mysqli_prepare($conexion, "UPDATE usuarios SET nombre = ?, contrasena = ?, rol = ? WHERE id = ?");
                if ($actualizar) {
                    mysqli_stmt_bind_param($actualizar, "sssi", $userName, $passwordHash, $userRole, $usuario['id']);
                    mysqli_stmt_execute($actualizar);
                    mysqli_stmt_close($actualizar);
                }
            }
            mysqli_stmt_close($stmt);
            return;
        }
        mysqli_stmt_close($stmt);
    }

    $insertar = mysqli_prepare($conexion, "INSERT INTO usuarios (nombre, correo, contrasena, rol) VALUES (?, ?, ?, ?)");
    if ($insertar) {
        mysqli_stmt_bind_param($insertar, "ssss", $userName, $userEmail, $passwordHash, $userRole);
        mysqli_stmt_execute($insertar);
        mysqli_stmt_close($insertar);
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
) ENGINE=InnoDB");
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
) ENGINE=InnoDB");
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS galeria_inicio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS sesiones_inicio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('slider', 'collage') NOT NULL DEFAULT 'slider',
    estilo_slider TINYINT NOT NULL DEFAULT 1,
    imagenes TEXT NULL,
    orden INT NOT NULL DEFAULT 0,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    administrador_id INT NULL,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS sesiones_imagenes (
    sesion_id INT NOT NULL,
    galeria_id INT NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    PRIMARY KEY (sesion_id, galeria_id),
    UNIQUE KEY uq_sesion_imagen_orden (sesion_id, orden),
    FOREIGN KEY (sesion_id) REFERENCES sesiones_inicio(id) ON DELETE CASCADE,
    FOREIGN KEY (galeria_id) REFERENCES galeria_inicio(id) ON DELETE CASCADE
) ENGINE=InnoDB");

function asegurarRelacionAdministrador($conexion, $tabla) {
    $tablasPermitidas = ['productos', 'promociones', 'combos', 'galeria_inicio', 'sesiones_inicio'];
    if (!in_array($tabla, $tablasPermitidas, true)) {
        return;
    }

    $columnas = mysqli_query($conexion, "SHOW COLUMNS FROM `$tabla` LIKE 'administrador_id'");
    if ($columnas && mysqli_num_rows($columnas) === 0) {
        mysqli_query($conexion, "ALTER TABLE `$tabla` ADD COLUMN administrador_id INT NULL");
    }

    $relaciones = mysqli_query($conexion, "SELECT 1 FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$tabla' AND COLUMN_NAME = 'administrador_id' AND REFERENCED_TABLE_NAME = 'usuarios' LIMIT 1");
    if ($relaciones && mysqli_num_rows($relaciones) === 0) {
        mysqli_query($conexion, "ALTER TABLE `$tabla` ADD CONSTRAINT `fk_{$tabla}_administrador` FOREIGN KEY (administrador_id) REFERENCES usuarios(id) ON DELETE SET NULL");
    }
}

foreach (['productos', 'promociones', 'combos', 'galeria_inicio', 'sesiones_inicio'] as $tablaAdministrada) {
    asegurarRelacionAdministrador($conexion, $tablaAdministrada);
}

$sesionesAntiguas = mysqli_query($conexion, "SELECT id, imagenes FROM sesiones_inicio WHERE imagenes IS NOT NULL AND imagenes <> ''");
if ($sesionesAntiguas) {
    $buscarImagen = mysqli_prepare($conexion, "SELECT id FROM galeria_inicio WHERE ruta = ? LIMIT 1");
    $insertarRelacionImagen = mysqli_prepare($conexion, "INSERT IGNORE INTO sesiones_imagenes (sesion_id, galeria_id, orden) VALUES (?, ?, ?)");
    while ($sesionAntigua = mysqli_fetch_assoc($sesionesAntiguas)) {
        $imagenesAntiguas = json_decode($sesionAntigua['imagenes'], true);
        if (!is_array($imagenesAntiguas) || !$buscarImagen || !$insertarRelacionImagen) {
            continue;
        }
        foreach (array_values(array_unique($imagenesAntiguas)) as $ordenImagen => $rutaImagen) {
            if (!is_string($rutaImagen)) {
                continue;
            }
            mysqli_stmt_bind_param($buscarImagen, 's', $rutaImagen);
            mysqli_stmt_execute($buscarImagen);
            $resultadoImagen = mysqli_stmt_get_result($buscarImagen);
            if ($resultadoImagen && ($imagen = mysqli_fetch_assoc($resultadoImagen))) {
                mysqli_stmt_bind_param($insertarRelacionImagen, 'iii', $sesionAntigua['id'], $imagen['id'], $ordenImagen);
                mysqli_stmt_execute($insertarRelacionImagen);
            }
        }
    }
    if ($buscarImagen) {
        mysqli_stmt_close($buscarImagen);
    }
    if ($insertarRelacionImagen) {
        mysqli_stmt_close($insertarRelacionImagen);
    }
}

$sesionesInicio = [
    ['Slider principal', 'slider', 1, 1],
    ['Favoritos de la temporada', 'slider', 2, 2],
    ['Ofertas para ti', 'slider', 3, 3],
];
foreach ($sesionesInicio as $sesionInicio) {
    $stmtSesion = mysqli_prepare($conexion, "INSERT INTO sesiones_inicio (nombre, tipo, estilo_slider, orden) SELECT ?, ?, ?, ? FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sesiones_inicio WHERE nombre = ?)");
    if ($stmtSesion) {
        mysqli_stmt_bind_param($stmtSesion, "sssis", $sesionInicio[0], $sesionInicio[1], $sesionInicio[2], $sesionInicio[3], $sesionInicio[0]);
        mysqli_stmt_execute($stmtSesion);
        mysqli_stmt_close($stmtSesion);
    }
}
$ordenesSesionesBase = [1, 2, 3];
foreach ($ordenesSesionesBase as $ordenSesionBase) {
    $sesionesDuplicadas = mysqli_query($conexion, "SELECT id FROM sesiones_inicio WHERE orden = " . (int) $ordenSesionBase . " AND activo = 1 ORDER BY id");
    $conservarSesion = true;
    if ($sesionesDuplicadas) {
        while ($sesionDuplicada = mysqli_fetch_assoc($sesionesDuplicadas)) {
            if ($conservarSesion) {
                $conservarSesion = false;
                continue;
            }
            mysqli_query($conexion, "UPDATE sesiones_inicio SET activo = 0 WHERE id = " . (int) $sesionDuplicada['id']);
        }
    }
}

ensureAdminUser($conexion);
ensureDefaultUser($conexion);
?>