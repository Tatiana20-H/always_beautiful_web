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

                <input type="date" name="fecha_nacimiento" required>

                <select name="genero" required>
                    <option value="">Selecciona tu género</option>
                    <option value="mujer">Mujer</option>
                    <option value="hombre">Hombre</option>
                </select>

                <div class="campo-password"><input type="password" name="password" placeholder="Contraseña" minlength="6" maxlength="72" required><button type="button" class="ver-password" aria-label="Mostrar contraseña">&#128065;</button></div>

                <div class="campo-password"><input type="password" name="password_confirm" placeholder="Confirmar contraseña" minlength="6" maxlength="72" required><button type="button" class="ver-password" aria-label="Mostrar confirmación">&#128065;</button></div>

                <button type="submit">Crear cuenta</button>

                <p>¿Ya tienes cuenta? <a href="index.php">Iniciar sesión aquí</a></p>

            </form>
        </div>

    </div>

</div>

<script>
document.querySelectorAll('.ver-password').forEach(function (boton) {
    boton.addEventListener('click', function () {
        const campo = this.parentElement.querySelector('input');
        campo.type = campo.type === 'password' ? 'text' : 'password';
    });
});
</script>

</body>
</html>