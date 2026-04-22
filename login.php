<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login - Always Beautiful</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="login-container">

    <div class="left">
      
    </div>

    <div class="right">
        <?php
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            include("conexion.php");
            
            $correo = trim($_POST['correo'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($correo) || empty($password)) {
                $error = "Correo y contraseña son requeridos";
            } else {
                $sql = "SELECT id, nombre, correo, contrasena, rol FROM usuarios WHERE correo = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("s", $correo);
                $stmt->execute();
                $resultado = $stmt->get_result();
                
                if ($resultado->num_rows > 0) {
                    $usuario = $resultado->fetch_assoc();
                    
                    if (password_verify($password, $usuario['contrasena'])) {
                        $_SESSION['usuario_id'] = $usuario['id'];
                        $_SESSION['nombre'] = $usuario['nombre'];
                        $_SESSION['correo'] = $usuario['correo'];
                        $_SESSION['rol'] = $usuario['rol'];
                        
                        $ip_address = $_SERVER['REMOTE_ADDR'];
                        $sql_historial = "INSERT INTO historial_actividades (usuario_id, accion, descripcion, ip_address) VALUES (?, 'LOGIN', 'Sesión iniciada', ?)";
                        $stmt_historial = $conexion->prepare($sql_historial);
                        $stmt_historial->bind_param("is", $usuario['id'], $ip_address);
                        $stmt_historial->execute();
                        $stmt_historial->close();
                        
                        header("Location: inicio.php");
                        exit();
                    } else {
                        $error = "❌ Contraseña incorrecta";
                    }
                } else {
                    $error = "❌ El correo no está registrado";
                }
                
                $stmt->close();
            }
        }
        
        if ($error) {
            echo '<div style="background: #ff6b6b; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">';
            echo '<p>' . $error . '</p>';
            echo '</div>';
        }
        ?>
        
        <form method="POST" action="">
            <h2>Iniciar Sesión</h2>

            <input type="email" name="correo" placeholder="Correo" required>
            <input type="password" name="password" placeholder="Contraseña" required>

            <button type="submit">Ingresar</button>

            <p>¿No tienes cuenta? <a href="registro.php">Registrarse</a></p>
        </form>
    </div>

</div>

</body>
</html>
