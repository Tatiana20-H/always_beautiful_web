<?php
session_start();
include("conexion.php");

// Validar que los datos hayan sido enviados
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// Obtener y limpiar datos
$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';
$rol = trim($_POST['rol'] ?? '');

// Validaciones básicas
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

if (empty($rol) || !in_array($rol, ['admin', 'usuario'])) {
    $errores[] = "Tipo de usuario inválido";
}

// Si hay errores, redirigir con mensajes
if (!empty($errores)) {
    $_SESSION['errores_login'] = $errores;
    header("Location: index.php");
    exit();
}

// Consultar usuario con prepared statement
$sql = "SELECT id, nombre, correo, contrasena, rol FROM usuarios WHERE correo = ? AND rol = ?";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    $_SESSION['errores_login'] = ["Error en la consulta: " . $conexion->error];
    header("Location: index.php");
    exit();
}

$stmt->bind_param("ss", $correo, $rol);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc();
    
    // Verificar contraseña encriptada
    if (password_verify($password, $usuario['contrasena'])) {
        
        // Login exitoso - guardar sesión
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['correo'] = $usuario['correo'];
        $_SESSION['rol'] = $usuario['rol'];
        
        // Registrar en historial de actividades (opcional - no bloquea si falla)
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $sql_historial = "INSERT INTO historial_actividades (usuario_id, accion, descripcion, ip_address) 
                          VALUES (?, 'LOGIN', 'Usuario inició sesión', ?)";
        $stmt_historial = $conexion->prepare($sql_historial);
        if ($stmt_historial) {
            $stmt_historial->bind_param("is", $usuario['id'], $ip_address);
            $stmt_historial->execute();
            $stmt_historial->close();
        }
        
        // Redirigir según rol
        if ($usuario['rol'] == 'admin') {
            header("Location: Administrador/index.php");
        } else {
            header("Location: inicio.php");
        }
        exit();
        
    } else {
        $_SESSION['errores_login'] = ["Contraseña incorrecta"];
        header("Location: index.php");
        exit();
    }
    
} else {
    $_SESSION['errores_login'] = ["Usuario no encontrado con esas credenciales"];
    header("Location: index.php");
    exit();
}

$stmt->close();
$conexion->close();
?>
