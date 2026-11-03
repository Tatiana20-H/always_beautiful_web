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

if (strlen($password) < 6 || strlen($password) > 72) {
    $errores[] = "La contraseña debe tener entre 6 y 72 caracteres";
}

// Si hay errores, mostrarlos
if (!empty($errores)) {
    $_SESSION['errores'] = $errores;
    $_SESSION['notificacion'] = ['tipo' => 'amarilla', 'mensaje' => implode(' ', $errores), 'modo' => 'register'];
    header("Location: index.php");
    exit();
}

// Verificar si el correo ya existe
$sql_verificar = "SELECT id FROM usuarios WHERE correo = ?";
$stmt = $conexion->prepare($sql_verificar);
$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $_SESSION['errores'] = ["El correo ya está registrado"];
    $_SESSION['notificacion'] = ['tipo' => 'roja', 'mensaje' => 'El correo ya está registrado.', 'modo' => 'register'];
    $stmt->close();
    header("Location: index.php");
    exit();
}
$stmt->close();

// Encriptar contraseña
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Insertar nuevo usuario
$sql_insert = "INSERT INTO usuarios (nombre, correo, contrasena, rol, fecha_nacimiento, genero)
               VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conexion->prepare($sql_insert);
$stmt->bind_param("ssssss", $nombre, $correo, $password_hash, $rol, $fecha_nacimiento, $genero);

if ($stmt->execute()) {
    $usuario_id = $conexion->insert_id;
    
    // Registrar en historial de actividades (opcional - no bloquea si falla)
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $sql_historial = "INSERT INTO historial_actividades (usuario_id, accion, descripcion, ip_address) 
                      VALUES (?, 'REGISTRO', 'Usuario registrado exitosamente', ?)";
    $stmt_historial = $conexion->prepare($sql_historial);
    if ($stmt_historial) {
        $stmt_historial->bind_param("is", $usuario_id, $ip_address);
        $stmt_historial->execute();
        $stmt_historial->close();
    }
    
    // Guardar sesión del usuario
    $_SESSION['usuario_id'] = $usuario_id;
    $_SESSION['nombre'] = $nombre;
    $_SESSION['correo'] = $correo;
    $_SESSION['rol'] = $rol;
    $_SESSION['fecha_nacimiento'] = $fecha_nacimiento;
    $_SESSION['genero'] = $genero;
    $_SESSION['carrito'] = [];
    $_SESSION['historial'] = [];
    $_SESSION['foto_perfil'] = null;
    $_SESSION['notificacion'] = ['tipo' => 'verde', 'mensaje' => 'Registro exitoso. Tu cuenta fue creada correctamente.', 'modo' => 'register'];
        enviarNotificacion($correo, 'Registro exitoso en Always Beautiful', "Hola $nombre, tu cuenta se ha registrado correctamente en Always Beautiful.");
    
    header("Location: index.php");
    exit();
} else {
    $_SESSION['errores'] = ["Error al registrar: " . $conexion->error];
    $_SESSION['notificacion'] = ['tipo' => 'roja', 'mensaje' => 'No fue posible crear la cuenta.', 'modo' => 'register'];
    header("Location: index.php");
    exit();
}

$stmt->close();
?>
