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

$tablaPedidosEntrega = mysqli_query($conexion, "SHOW TABLES LIKE 'pedidos'");
if ($tablaPedidosEntrega && mysqli_num_rows($tablaPedidosEntrega) > 0) {
    $columnasPedido = [
        'datos_entrega' => 'ALTER TABLE pedidos ADD COLUMN datos_entrega TEXT NULL AFTER metodo_pago',
        'observacion_inventario' => 'ALTER TABLE pedidos ADD COLUMN observacion_inventario TEXT NULL AFTER datos_entrega',
        'correo_usuario_enviado' => 'ALTER TABLE pedidos ADD COLUMN correo_usuario_enviado TINYINT(1) NOT NULL DEFAULT 0 AFTER observacion_inventario',
        'correo_admin_enviado' => 'ALTER TABLE pedidos ADD COLUMN correo_admin_enviado TINYINT(1) NOT NULL DEFAULT 0 AFTER correo_usuario_enviado',
        'fecha_pago' => 'ALTER TABLE pedidos ADD COLUMN fecha_pago TIMESTAMP NULL DEFAULT NULL AFTER correo_admin_enviado',
        'descuento' => 'ALTER TABLE pedidos ADD COLUMN descuento INT UNSIGNED NOT NULL DEFAULT 0 AFTER total',
        'puntos_usados' => 'ALTER TABLE pedidos ADD COLUMN puntos_usados INT UNSIGNED NOT NULL DEFAULT 0 AFTER descuento',
        'beneficio_solicitud_id' => 'ALTER TABLE pedidos ADD COLUMN beneficio_solicitud_id INT NULL AFTER puntos_usados',
    ];
    foreach ($columnasPedido as $nombreColumna => $sqlColumna) {
        $columnaExiste = mysqli_query($conexion, "SHOW COLUMNS FROM pedidos LIKE '" . mysqli_real_escape_string($conexion, $nombreColumna) . "'");
        if ($columnaExiste && mysqli_num_rows($columnaExiste) === 0 && !mysqli_query($conexion, $sqlColumna)) {
            error_log('No se pudo agregar la columna ' . $nombreColumna . ' a pedidos: ' . mysqli_error($conexion));
        }
    }
}

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS notificaciones_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo VARCHAR(30) NOT NULL DEFAULT 'promocion',
    oferta_tipo VARCHAR(30) NULL,
    oferta_id INT NULL,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT NOT NULL,
    precio DECIMAL(10,2) NULL,
    imagen VARCHAR(255) NULL,
    leida TINYINT(1) NOT NULL DEFAULT 0,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_notificacion_oferta (usuario_id, tipo, oferta_tipo, oferta_id),
    KEY idx_notificacion_usuario_fecha (usuario_id, fecha_creacion),
    CONSTRAINT fk_notificacion_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS puntos_movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    pedido_id INT NULL,
    tipo ENUM('compra', 'canje', 'obsequio') NOT NULL,
    puntos INT NOT NULL,
    referencia VARCHAR(120) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_movimiento_puntos_referencia (referencia),
    KEY idx_movimiento_puntos_usuario (usuario_id, fecha_creacion),
    CONSTRAINT fk_puntos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_puntos_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL
) ENGINE=InnoDB");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS solicitudes_beneficios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('descuento', 'obsequio') NOT NULL,
    estado ENUM('solicitado', 'reservado', 'completado', 'cancelado') NOT NULL DEFAULT 'solicitado',
    puntos INT UNSIGNED NOT NULL,
    pedido_id INT NULL,
    fecha_solicitud TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_solicitud_beneficio_usuario (usuario_id, tipo, estado),
    KEY idx_solicitud_beneficio_pedido (pedido_id),
    CONSTRAINT fk_solicitud_beneficio_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_solicitud_beneficio_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL
) ENGINE=InnoDB");

require_once __DIR__ . '/beneficios.php';

$tablaProductoDestacado = mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS producto_destacado (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    producto_id INT NULL,
    descripcion TEXT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 0,
    fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_producto_destacado_producto (producto_id),
    CONSTRAINT fk_producto_destacado_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON DELETE SET NULL
) ENGINE=InnoDB");
if (!$tablaProductoDestacado) {
    error_log('No se pudo asegurar la tabla producto_destacado: ' . mysqli_error($conexion));
} else {
    $registroProductoDestacado = mysqli_query($conexion, "INSERT IGNORE INTO producto_destacado (id, producto_id, descripcion, activo) VALUES (1, NULL, '', 0)");
    if (!$registroProductoDestacado) {
        error_log('No se pudo inicializar producto_destacado: ' . mysqli_error($conexion));
    }
}

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS promociones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    categoria VARCHAR(50) NOT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    descuento DECIMAL(5,2) NOT NULL DEFAULT 0,
    imagen VARCHAR(255) NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS combos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    descuento DECIMAL(5,2) NOT NULL DEFAULT 0,
    imagen VARCHAR(255) NULL,
    productos TEXT,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

foreach (['promociones', 'combos'] as $tablaOfertas) {
    $columnaDescuento = mysqli_query($conexion, "SHOW COLUMNS FROM `$tablaOfertas` LIKE 'descuento'");
    if ($columnaDescuento && mysqli_num_rows($columnaDescuento) === 0) {
        mysqli_query($conexion, "ALTER TABLE `$tablaOfertas` ADD COLUMN descuento DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER precio");
    }

    $columnaAdministradorOferta = mysqli_query($conexion, "SHOW COLUMNS FROM `$tablaOfertas` LIKE 'administrador_id'");
    if ($columnaAdministradorOferta && mysqli_num_rows($columnaAdministradorOferta) === 0) {
        mysqli_query($conexion, "ALTER TABLE `$tablaOfertas` ADD COLUMN administrador_id INT NULL");
    }
}

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
    $claveInicialAdmin = getenv('ADMIN_INITIAL_PASSWORD');
    $claveInicialValida = is_string($claveInicialAdmin) && strlen($claveInicialAdmin) >= 12 && strlen($claveInicialAdmin) <= 72;
    mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS configuracion_admin (id TINYINT NOT NULL PRIMARY KEY, inicializado TINYINT(1) NOT NULL DEFAULT 0, clave_inicial_configurada TINYINT(1) NOT NULL DEFAULT 0) ENGINE=InnoDB");
    mysqli_query($conexion, "INSERT IGNORE INTO configuracion_admin (id, inicializado) VALUES (1, 0)");
    $columnaClaveConfigurada = mysqli_query($conexion, "SHOW COLUMNS FROM configuracion_admin LIKE 'clave_inicial_configurada'");
    if ($columnaClaveConfigurada && mysqli_num_rows($columnaClaveConfigurada) === 0) {
        mysqli_query($conexion, "ALTER TABLE configuracion_admin ADD COLUMN clave_inicial_configurada TINYINT(1) NOT NULL DEFAULT 0");
    }
    $estado = mysqli_query($conexion, "SELECT inicializado, clave_inicial_configurada FROM configuracion_admin WHERE id = 1");
    $configuracionAdmin = $estado ? mysqli_fetch_assoc($estado) : null;
    $inicializarCredenciales = $configuracionAdmin && ((int) $configuracionAdmin['inicializado'] === 0 || (int) $configuracionAdmin['clave_inicial_configurada'] !== 1);
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
        if ($inicializarCredenciales && $claveInicialValida) {
            $hashAdmin = password_hash($claveInicialAdmin, PASSWORD_DEFAULT);
            $actualizar = mysqli_prepare($conexion, "UPDATE usuarios SET nombre = ?, correo = ?, contrasena = ?, rol = 'admin' WHERE id = ?");
            if ($actualizar) {
                mysqli_stmt_bind_param($actualizar, 'sssi', $nombreAdmin, $correoAdmin, $hashAdmin, $admin['id']);
                $actualizado = mysqli_stmt_execute($actualizar);
                mysqli_stmt_close($actualizar);
                if ($actualizado) {
                    mysqli_query($conexion, "UPDATE configuracion_admin SET inicializado = 1, clave_inicial_configurada = 1 WHERE id = 1");
                }
            }
        } elseif ($inicializarCredenciales && (int) $configuracionAdmin['clave_inicial_configurada'] === 0) {
            $hashInutilizable = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
            $bloquearClavePredeterminada = mysqli_prepare($conexion, 'UPDATE usuarios SET contrasena = ? WHERE id = ?');
            if ($bloquearClavePredeterminada) {
                mysqli_stmt_bind_param($bloquearClavePredeterminada, 'si', $hashInutilizable, $admin['id']);
                $bloqueado = mysqli_stmt_execute($bloquearClavePredeterminada);
                mysqli_stmt_close($bloquearClavePredeterminada);
                if ($bloqueado) {
                    mysqli_query($conexion, "UPDATE configuracion_admin SET inicializado = 1, clave_inicial_configurada = 2 WHERE id = 1");
                }
            }
        } elseif ($admin['rol'] !== 'admin' || $admin['nombre'] !== $nombreAdmin || $admin['correo'] !== $correoAdmin) {
            $actualizar = mysqli_prepare($conexion, "UPDATE usuarios SET nombre = ?, correo = ?, rol = 'admin' WHERE id = ?");
            if ($actualizar) {
                mysqli_stmt_bind_param($actualizar, 'ssi', $nombreAdmin, $correoAdmin, $admin['id']);
                mysqli_stmt_execute($actualizar);
                mysqli_stmt_close($actualizar);
            }
        }
        if ($inicializarCredenciales && !$claveInicialValida) {
            error_log('Administrador no inicializado: configura ADMIN_INITIAL_PASSWORD con entre 12 y 72 caracteres.');
        }
        return;
    }

    if (!$claveInicialValida) {
        error_log('Administrador no creado: configura ADMIN_INITIAL_PASSWORD con entre 12 y 72 caracteres.');
        return;
    }

    $hashAdmin = password_hash($claveInicialAdmin, PASSWORD_DEFAULT);
    $insertar = mysqli_prepare($conexion, "INSERT INTO usuarios (nombre, correo, contrasena, rol) VALUES (?, ?, ?, 'admin')");
    if ($insertar) {
        mysqli_stmt_bind_param($insertar, 'sss', $nombreAdmin, $correoAdmin, $hashAdmin);
        $insertado = mysqli_stmt_execute($insertar);
        mysqli_stmt_close($insertar);
        if ($insertado) mysqli_query($conexion, "UPDATE configuracion_admin SET inicializado = 1, clave_inicial_configurada = 1 WHERE id = 1");
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
?>