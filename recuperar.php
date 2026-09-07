<?php
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];
$mensaje = '';
$mostrarCodigo = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $codigo = trim($_POST['codigo'] ?? '');
    $nuevaPassword = $_POST['nueva_password'] ?? '';

    if ($codigo !== '') {
        $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE correo = ? AND codigo_recuperacion = ? AND codigo_expira > NOW()");
        $stmt->bind_param('ss', $correo, $codigo);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($usuario && strlen($nuevaPassword) >= 6) {
            $hash = password_hash($nuevaPassword, PASSWORD_BCRYPT);
            $stmt = $conexion->prepare("UPDATE usuarios SET contrasena = ?, codigo_recuperacion = NULL, codigo_expira = NULL WHERE id = ?");
            $stmt->bind_param('si', $hash, $usuario['id']);
            $stmt->execute();
            $stmt->close();
            $mensaje = 'Contraseña actualizada. Ya puedes iniciar sesión.';
        } else {
            $mensaje = 'Código incorrecto, vencido o contraseña demasiado corta.';
            $mostrarCodigo = true;
        }
    } elseif (filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE correo = ?");
        $stmt->bind_param('s', $correo);
        $stmt->execute();
        $existe = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($existe) {
            $codigo = (string) random_int(1000, 9999);
            $stmt = $conexion->prepare("UPDATE usuarios SET codigo_recuperacion = ?, codigo_expira = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?");
            $stmt->bind_param('si', $codigo, $existe['id']);
            $stmt->execute();
            $stmt->close();
            enviarNotificacion($correo, 'Código de recuperación - Always Beautiful', "Tu código de recuperación es: $codigo. Vence en 15 minutos.");
            $mensaje = 'Te enviamos un código de 4 dígitos a tu correo.';
            $mostrarCodigo = true;
        } else {
            $mensaje = 'Ese correo no está registrado.';
        }
    } else {
        $mensaje = 'Escribe un correo válido.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Recuperar contraseña</title><link rel="stylesheet" href="styles.css"></head>
<body>
<div class="form-wrapper recuperacion">
    <h2>Recuperar contraseña</h2>
    <?php if ($mensaje): ?><p class="mensaje-form"><?= htmlspecialchars($mensaje) ?></p><?php endif; ?>
    <form method="POST">
        <input type="email" name="correo" placeholder="Correo registrado" required value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
        <?php if ($mostrarCodigo): ?>
            <input type="text" name="codigo" placeholder="Código de 4 dígitos" inputmode="numeric" maxlength="4" required>
            <div class="campo-password"><input type="password" name="nueva_password" placeholder="Nueva contraseña" required><button type="button" class="ver-password" aria-label="Mostrar contraseña">&#128065;</button></div>
        <?php endif; ?>
        <button type="submit"><?= $mostrarCodigo ? 'Cambiar contraseña' : 'Enviar código' ?></button>
    </form>
    <p><a href="index.php">Volver al inicio</a></p>
</div>
<script>
document.querySelectorAll('.ver-password').forEach(function (boton) { boton.addEventListener('click', function () { const campo = this.parentElement.querySelector('input'); campo.type = campo.type === 'password' ? 'text' : 'password'; }); });
</script>
</body>
</html>
