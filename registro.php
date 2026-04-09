<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="login-container">

    <div class="left">
        
    </div>

    <div class="right">
        <form action="guardar_usuario.php" method="POST">
            <h2>Registrarse</h2>

            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="email" name="correo" placeholder="Correo" required>
            <input type="password" name="password" placeholder="Contraseña" required>

            <!-- Rol oculto -->
            <input type="hidden" name="rol" value="usuario">

            <button type="submit">Crear cuenta</button>

            <p>¿Ya tienes cuenta? <a href="index.php">Iniciar sesión</a></p>
        </form>
    </div>

</div>

</body>
</html>