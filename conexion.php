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

            if (!password_verify($adminPassword, $storedHash) || password_needs_rehash($storedHash, PASSWORD_BCRYPT)) {
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

ensureAdminUser($conexion);
?>