<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro - Always Beautiful</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="login-container">

    <div class="left">
        
    </div>

    <div class="right">
        <?php
        if (isset($_SESSION['errores'])) {
            echo '<div style="background: #ff6b6b; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">';
            foreach ($_SESSION['errores'] as $error) {
                echo '<p>❌ ' . htmlspecialchars($error) . '</p>';
            }
            echo '</div>';
            unset($_SESSION['errores']);
        }
        ?>
        
        <form action="guardar-usuario.php" method="POST">
            <h2>Registrarse</h2>

            <input type="text" name="nombre" placeholder="Nombre completo" required>
            <input type="email" name="correo" placeholder="Correo" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <input type="password" name="password_confirm" placeholder="Confirmar contraseña" required>
            
            <select name="rol" required>
                <option value="">Tipo de usuario</option>
                <option value="admin">Administrador</option>
                <option value="usuario">Usuario</option>
            </select>

            <button type="submit">Crear cuenta</button>

            <p>¿Ya tienes cuenta? <a href="login.php">Iniciar sesión aquí</a></p>
        </form>
    </div>

</div>

</body>
</html>
        echo '<div class="errores"><ul>';
        foreach ($_SESSION['errores'] as $error) {
            echo '<li>❌ ' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
        unset($_SESSION['errores']);
    }
    ?>
    
    <form action="guardar-usuario.php" method="POST">
        <h2>🌸 Registrarse</h2>

        <input type="text" name="nombre" placeholder="Tu nombre" required>
        <input type="email" name="correo" placeholder="Correo electrónico" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <input type="password" name="password_confirm" placeholder="Confirmar contraseña" required>

        <button type="submit">✨ Crear cuenta</button>

        <p>¿Ya tienes cuenta? <a href="login.php">Iniciar sesión aquí</a></p>
    </form>
</div>

</body>
</html>