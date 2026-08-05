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

    <div class="left"></div>

    <div class="right">

        <?php
        if (isset($_SESSION['errores'])) {
            echo '<div class="error-box">';

            foreach ($_SESSION['errores'] as $error) {
                echo '<p>❌ ' . htmlspecialchars($error) . '</p>';
            }

            echo '</div>';
            unset($_SESSION['errores']);
        }
        ?>

        <div class="form-wrapper">
            <h2>Registrarse</h2>
            <form action="guardar-usuario.php" method="POST">

                <input type="text" name="nombre" placeholder="Nombre completo" required>

                <input type="email" name="correo" placeholder="Correo" required>

                <input type="password" name="password" placeholder="Contraseña" required>

                <input type="password" name="password_confirm" placeholder="Confirmar contraseña" required>

                <button type="submit">Crear cuenta</button>

                <p>¿Ya tienes cuenta? <a href="index.php">Iniciar sesión aquí</a></p>

            </form>
        </div>

    </div>

</div>

</body>
</html>