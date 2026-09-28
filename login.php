<?php
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];

// Validar que los datos hayan sido enviados
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// Obtener y limpiar datos
$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';
$rolSolicitado = strtolower(trim((string) ($_POST['rol'] ?? '')));

// Validaciones básicas
$errores = [];

if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "Correo inválido";
}

if (empty($password)) {
    $errores[] = "La contraseña es requerida";
}

if (!in_array($rolSolicitado, ['usuario', 'admin'], true)) {
    $errores[] = "Selecciona un tipo de cuenta válido";
}

function redirectWithLoginErrors(array $errors, array $oldData = []) {
    $_SESSION['errores_login'] = $errors;
    $_SESSION['old_login'] = $oldData;
    $_SESSION['notificacion'] = [
        'tipo' => str_contains(strtolower(implode(' ', $errors)), 'no encontrado') ? 'amarilla' : 'roja',
        'mensaje' => implode(' ', $errors),
        'modo' => 'login'
    ];
    header("Location: index.php");
    exit();
}

if (!empty($errores)) {
    redirectWithLoginErrors($errores, [
        'nombre' => $nombre,
        'correo' => $correo,
    ]);
}

// Consultar usuario con prepared statement
$sql = "SELECT id, nombre, correo, contrasena, rol, genero, foto_perfil FROM usuarios WHERE correo = ? LIMIT 1";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    redirectWithLoginErrors(["Error en la consulta: " . $conexion->error], [
        'nombre' => $nombre,
        'correo' => $correo,
    ]);
}

$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc();
    $rolUsuario = strtolower(trim((string) ($usuario['rol'] ?? 'usuario')));
    
    // Verificar contraseña encriptada
    if (password_verify($password, $usuario['contrasena'])) {

        if (strcasecmp(trim((string) $usuario['nombre']), $nombre) !== 0 || $rolSolicitado !== $rolUsuario) {
            redirectWithLoginErrors(["Datos inválidos. Verifica tus datos y el tipo de cuenta seleccionado."], [
                'nombre' => $nombre,
                'correo' => $correo,
            ]);
        }
        
        // Login exitoso - guardar sesión
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['correo'] = $usuario['correo'];
        $rol = $rolUsuario;
        $_SESSION['rol'] = $rol;
        $_SESSION['genero'] = $usuario['genero'] ?? null;
        $_SESSION['foto_perfil'] = $usuario['foto_perfil'] ?? null;
        cargarDatosUsuario($conexion, $usuario['id']);
        $_SESSION['notificacion'] = ['tipo' => 'verde', 'mensaje' => 'Usuario autenticado correctamente.', 'modo' => 'login'];
            enviarNotificacion($usuario['correo'], 'Inicio de sesión en Always Beautiful', "Hola {$usuario['nombre']}, se inició sesión en tu cuenta de Always Beautiful.");
        
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
        if ($rol === 'admin') {
            header("Location: Administrador/index.php");
        } else {
            header("Location: inicio.php");
        }
        exit();
        
    } else {
        redirectWithLoginErrors(["Contraseña incorrecta"], [
            'nombre' => $nombre,
            'correo' => $correo,
        ]);
    }
    
} else {
    redirectWithLoginErrors(["Usuario no encontrado con esas credenciales"], [
        'nombre' => $nombre,
        'correo' => $correo,
    ]);
}

$stmt->close();
$conexion->close();
?>
