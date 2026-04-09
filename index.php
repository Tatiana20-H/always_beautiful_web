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
        <form action="inicio.php" method="POST">
            <h2>Iniciar Sesión</h2>

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