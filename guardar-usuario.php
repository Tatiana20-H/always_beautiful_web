<?php
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];

// Validar que los datos hayan sido enviados
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: registro.php");
    exit();
}

// Obtener y limpiar datos
$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$correo = strtolower($correo);
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
$genero = $_POST['genero'] ?? '';
$rol = 'usuario';

// Validaciones
$errores = [];

if (empty($nombre)) {
    $errores[] = "El nombre es requerido";
}

if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "Correo inválido";
}

if (empty($password)) {
    $errores[] = "La contraseña es requerida";
}

if ($password !== $password_confirm) {
    $errores[] = "Las contraseñas no coinciden";
}

if (!$fecha_nacimiento || !DateTime::createFromFormat('Y-m-d', $fecha_nacimiento)) {
    $errores[] = "La fecha de nacimiento es requerida";
}

if (!in_array($genero, ['hombre', 'mujer'], true)) {
    $errores[] = "Selecciona una opción de género";
}

if (strlen($password) < 8 || strlen($password) > 16) {
    $errores[] = "La contraseña debe tener entre 8 y 16 caracteres";
}

if (!preg_match('/[A-Z]/', $password)
    || !preg_match('/[a-z]/', $password)
    || !preg_match('/[0-9]/', $password)
    || !preg_match('/[^A-Za-z0-9\s]/', $password)) {
    $errores[] = "La contraseña debe incluir mayúscula, minúscula, número y símbolo";
}

// Si hay errores, mostrarlos
if (!empty($errores)) {
    $_SESSION['errores'] = $errores;
    $_SESSION['notificacion'] = ['tipo' => 'amarilla', 'mensaje' => implode(' ', $errores), 'modo' => 'register'];
    header("Location: index.php");
    exit();
}

// Verificar si el correo ya existe
$sql_verificar = "SELECT id, correo_verificado FROM usuarios WHERE correo = ?";
$stmt = $conexion->prepare($sql_verificar);
$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $usuarioExistente = $resultado->fetch_assoc();
    $stmt->close();
    if (!(int) $usuarioExistente['correo_verificado']) {
        $_SESSION['correo_verificacion'] = $correo;
        $_SESSION['aviso_verificacion'] = 'Esta cuenta aún necesita verificar su correo. Puedes solicitar un código nuevo.';
        header('Location: confirmacion_registro.php');
        exit();
    }
    $_SESSION['errores'] = ["El correo ya está registrado"];
    $_SESSION['notificacion'] = ['tipo' => 'roja', 'mensaje' => 'El correo ya está registrado.', 'modo' => 'register'];
    header("Location: index.php");
    exit();
}
$stmt->close();

// Encriptar contraseña
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insertar nuevo usuario
$sql_insert = "INSERT INTO usuarios (nombre, correo, contrasena, rol, fecha_nacimiento, genero, correo_verificado)
               VALUES (?, ?, ?, ?, ?, ?, 0)";
$stmt = $conexion->prepare($sql_insert);
$stmt->bind_param("ssssss", $nombre, $correo, $password_hash, $rol, $fecha_nacimiento, $genero);

if ($stmt->execute()) {
    $usuario_id = $conexion->insert_id;

    $_SESSION['correo_verificacion'] = $correo;
    $codigoVerificacion = (string) random_int(100000, 999999);
    $stmtCodigo = $conexion->prepare('UPDATE usuarios SET codigo_verificacion = ?, verificacion_expira = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?');
    $stmtCodigo->bind_param('si', $codigoVerificacion, $usuario_id);
    $codigoGuardado = $stmtCodigo->execute();
    $stmtCodigo->close();

    if ($codigoGuardado && enviarNotificacion($correo, 'Verifica tu correo - Always Beautiful', "Hola $nombre, tu código de verificación es: $codigoVerificacion. Vence en 15 minutos.")) {
        $_SESSION['aviso_verificacion'] = 'Te enviamos un código de 6 dígitos a tu correo.';
    } else {
        $stmtCancelar = $conexion->prepare('DELETE FROM usuarios WHERE id = ? AND correo_verificado = 0');
        $stmtCancelar->bind_param('i', $usuario_id);
        $stmtCancelar->execute();
        $stmtCancelar->close();
        unset($_SESSION['correo_verificacion']);
        $_SESSION['aviso_verificacion'] = 'No se pudo enviar el código. Revisa la configuración del correo y solicita otro.';
    }
    header('Location: confirmacion_registro.php');
    exit();
} else {
    $_SESSION['errores'] = ["Error al registrar: " . $conexion->error];
    $_SESSION['notificacion'] = ['tipo' => 'roja', 'mensaje' => 'No fue posible crear la cuenta.', 'modo' => 'register'];
    header("Location: index.php");
    exit();
}

$stmt->close();
?>
