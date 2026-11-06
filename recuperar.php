<?php
session_start();
require_once __DIR__ . '/conexion.php';
$conexion = $GLOBALS['conexion'];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['correo_recuperacion']);
}
$mensaje = '';
$errorEnvioCorreo = false;
$mostrarCodigo = !empty($_SESSION['recuperacion_correo']);
$correoSesion = $_SESSION['recuperacion_correo'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = strtolower(trim($_POST['correo'] ?? ''));
    $codigo = trim($_POST['codigo'] ?? '');
    $nuevaPassword = $_POST['nueva_password'] ?? '';
    $reenviarCodigo = isset($_POST['reenviar_codigo']);

    if ($reenviarCodigo) {
        $correo = $correoSesion;
    }

    if ($codigo !== '' && !$reenviarCodigo) {
        $correo = $correoSesion;
        if ($correo === '' || !preg_match('/^\d{6}$/', $codigo) || !preg_match('/^(?=.*\p{L})(?=.*\d)(?=.*[^\p{L}\d\s]).{8,16}$/u', $nuevaPassword)) {
            $mensaje = 'Código o contraseña no válidos. La contraseña debe tener entre 8 y 16 caracteres e incluir letras, números y símbolos.';
            $mostrarCodigo = true;
        } else {
            $stmt = $conexion->prepare('SELECT id, codigo_recuperacion, intentos_recuperacion FROM usuarios WHERE correo = ? AND codigo_expira > NOW() AND intentos_recuperacion < 5');
            $stmt->bind_param('s', $correo);
            $stmt->execute();
            $usuario = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($usuario && password_verify($codigo, (string) $usuario['codigo_recuperacion'])) {
                $hash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
                $stmt = $conexion->prepare('UPDATE usuarios SET contrasena = ?, correo_verificado = 1, codigo_recuperacion = NULL, codigo_expira = NULL, intentos_recuperacion = 0 WHERE id = ?');
                $stmt->bind_param('si', $hash, $usuario['id']);
                $actualizado = $stmt->execute();
                $stmt->close();
                if ($actualizado) {
                    unset($_SESSION['recuperacion_correo']);
                    $mensaje = 'Contraseña actualizada. Ya puedes iniciar sesión.';
                    $mostrarCodigo = false;
                } else {
                    error_log('No fue posible actualizar la contraseña durante la recuperación.');
                    $mensaje = 'No fue posible actualizar la contraseña. Inténtalo nuevamente.';
                    $mostrarCodigo = true;
                }
            } else {
                if ($usuario) {
                    $stmt = $conexion->prepare('UPDATE usuarios SET intentos_recuperacion = intentos_recuperacion + 1 WHERE id = ?');
                    $stmt->bind_param('i', $usuario['id']);
                    $stmt->execute();
                    $stmt->close();
                }
                $mensaje = 'Código incorrecto, vencido o se alcanzó el límite de intentos. Solicita otro código.';
                $mostrarCodigo = true;
            }
        }
    } elseif (($reenviarCodigo && $correo !== '') || filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        if (!$reenviarCodigo) {
            $_SESSION['recuperacion_correo'] = $correo;
            $correoSesion = $correo;
        }
        $mostrarCodigo = true;
        $stmt = $conexion->prepare('SELECT id FROM usuarios WHERE correo = ?');
        $stmt->bind_param('s', $correo);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $mensaje = 'Si el correo está registrado, recibirás un código de recuperación.';

        if ($usuario) {
            $codigo = (string) random_int(100000, 999999);
            $hashCodigo = password_hash($codigo, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare('UPDATE usuarios SET codigo_recuperacion = ?, codigo_expira = DATE_ADD(NOW(), INTERVAL 10 MINUTE), intentos_recuperacion = 0 WHERE id = ?');
            $stmt->bind_param('si', $hashCodigo, $usuario['id']);
            $guardado = $stmt->execute();
            $stmt->close();
            if ($guardado && !enviarNotificacion($correo, 'Código de recuperación - Always Beautiful', "Tu código de recuperación es: {$codigo}\nVence en 10 minutos. Si no solicitaste este cambio, ignora el mensaje.")) {
                $errorEnvioCorreo = true;
                $stmt = $conexion->prepare('UPDATE usuarios SET codigo_recuperacion = NULL, codigo_expira = NULL WHERE id = ?');
                $stmt->bind_param('i', $usuario['id']);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Recuperar contraseña</title><link rel="stylesheet" href="styles.css"></head>
<body>
<div class="form-wrapper recuperacion">
    <h2>Recuperar contraseña</h2>
    <?php if ($mensaje): ?><p class="mensaje-form" role="status"><?= htmlspecialchars($mensaje) ?></p><?php endif; ?>
    <?php if ($errorEnvioCorreo): ?><p class="mensaje-form" role="alert">No se pudo enviar el código. <?= htmlspecialchars(mensajeErrorNotificacion(), ENT_QUOTES, 'UTF-8') ?> Corrige la configuración e inténtalo nuevamente.</p><?php endif; ?>
    <form method="POST">
        <?php if (!$mostrarCodigo): ?>
            <input type="email" name="correo" placeholder="Correo registrado" required autocomplete="email">
        <?php else: ?>
            <p>Escribe el código de seis dígitos enviado a <?= htmlspecialchars($correoSesion) ?>.</p>
            <input type="text" name="codigo" placeholder="Código de verificación" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
            <div class="campo-password"><input type="password" name="nueva_password" placeholder="Nueva contraseña (8-16 caracteres)" minlength="8" maxlength="16" required><button type="button" class="ver-password" aria-label="Mostrar contraseña">&#128065;</button></div>
        <?php endif; ?>
        <button type="submit"><?= $mostrarCodigo ? 'Cambiar contraseña' : 'Enviar código' ?></button>
    </form>
    <?php if ($mostrarCodigo): ?>
        <form method="POST">
            <button type="submit" name="reenviar_codigo" value="1">Reenviar código</button>
        </form>
    <?php endif; ?>
    <p><a href="index.php">Volver al inicio</a></p>
</div>
<script>
document.querySelectorAll('.ver-password').forEach(function (boton) { boton.addEventListener('click', function () { const campo = this.parentElement.querySelector('input'); campo.type = campo.type === 'password' ? 'text' : 'password'; }); });
</script>
</body>
</html>