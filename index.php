<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="login-container">

    <div class="left">
      
    </div>

    <div class="right">
        <?php
        if (isset($_SESSION['errores_login'])) {
            echo '<div style="background: #ff6b6b; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">';
            foreach ($_SESSION['errores_login'] as $error) {
                echo '<p>❌ ' . htmlspecialchars($error) . '</p>';
            }
            echo '</div>';
            unset($_SESSION['errores_login']);
        }
        ?>
        
        <form action="login.php" method="POST">
            <h2>Iniciar Sesión</h2>

            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="email" name="correo" placeholder="Correo" required>
            <input type="password" name="password" placeholder="Contraseña" required>

            <select name="rol" required>
                <option value="">Tipo de usuario</option>
                <option value="admin">Administrador</option>
                <option value="usuario">Usuario</option>
            </select>

            <button type="submit">Ingresar</button>

            <p>¿No tienes cuenta? <a href="registro.php">Registrarse</a></p>
        </form>
    </div>

</div>

</body>
</html>