<?php
session_start();
include("conexion.php");
<?php
$host = "localhost";
$usuario = "root";
$contrasena = "";
$bd = "AlwaysBeautifulDB";
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

if (strlen($password) < 6) {
    $errores[] = "La contraseña debe tener al menos 6 caracteres";
}

// Si hay errores, mostrarlos
if (!empty($errores)) {
    $_SESSION['errores'] = $errores;
    header("Location: registro.php");
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
    $stmt->close();
    header("Location: registro.php");
    exit();
}
$stmt->close();

// Encriptar contraseña
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Insertar nuevo usuario
$sql_insert = "INSERT INTO usuarios (nombre, correo, contrasena, rol) 
               VALUES (?, ?, ?, 'usuario')";
$stmt = $conexion->prepare($sql_insert);
$stmt->bind_param("sss", $nombre, $correo, $password_hash);

if ($stmt->execute()) {
    $usuario_id = $conexion->insert_id;
    
    // Registrar en historial de actividades
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $sql_historial = "INSERT INTO historial_actividades (usuario_id, accion, descripcion, ip_address) 
                      VALUES (?, 'REGISTRO', 'Usuario registrado exitosamente', ?)";
    $stmt_historial = $conexion->prepare($sql_historial);
    $stmt_historial->bind_param("is", $usuario_id, $ip_address);
    $stmt_historial->execute();
    $stmt_historial->close();
    
    // Guardar sesión del usuario
    $_SESSION['usuario_id'] = $usuario_id;
    $_SESSION['nombre'] = $nombre;
    $_SESSION['correo'] = $correo;
    
    header("Location: confirmacion_registro.php");
    exit();
} else {
    $_SESSION['errores'] = ["Error al registrar: " . $conexion->error];
    header("Location: registro.php");
    exit();
}

$stmt->close();
?>
