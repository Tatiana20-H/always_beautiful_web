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
        $old_login = $_SESSION['old_login'] ?? ['nombre' => '', 'correo' => '', 'rol' => ''];
        ?>
        <div class="form-wrapper">
            <?php
            if (isset($_SESSION['errores_login'])) {
                echo '<div class="error-box">';
                foreach ($_SESSION['errores_login'] as $error) {
                    echo '<p>❌ ' . htmlspecialchars($error) . '</p>';
                }
                echo '</div>';
                unset($_SESSION['errores_login']);
            }
            ?>
            <h2>Iniciar Sesión</h2>
            <form action="login.php" method="POST">

            <input type="text" name="nombre" placeholder="Nombre" required value="<?php echo htmlspecialchars($old_login['nombre']); ?>">
            <input type="email" name="correo" placeholder="Correo" required value="<?php echo htmlspecialchars($old_login['correo']); ?>">
            <input type="password" name="password" placeholder="Contraseña" required>

            <select name="rol" required>
                <option value="">Tipo de usuario</option>
                <option value="admin" <?php echo $old_login['rol'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                <option value="usuario" <?php echo $old_login['rol'] === 'usuario' ? 'selected' : ''; ?>>Usuario</option>
            </select>

            <button type="submit">Ingresar</button>

            <p>¿No tienes cuenta? <a href="registro.php">Registrarse</a></p>
        </form>
        </div>
    </div>

</div>

</body>
</html>