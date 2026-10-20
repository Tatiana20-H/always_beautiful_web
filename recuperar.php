<?php
session_start();
include __DIR__ . '/conexion.php';
$conexion = $GLOBALS['conexion'];
$mensaje = '';
$error = false;
$correoFormulario = trim((string) ($_POST['correo'] ?? $_SESSION['correo_recuperacion'] ?? ''));
$mostrarCodigo = !empty($_SESSION['correo_recuperacion']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = (string) ($_POST['accion'] ?? 'enviar_codigo');

    if ($accion === 'enviar_codigo') {
        $correoFormulario = strtolower(trim((string) ($_POST['correo'] ?? '')));
        if (!filter_var($correoFormulario, FILTER_VALIDATE_EMAIL)) {
            $mensaje = 'Escribe un correo válido.';
            $error = true;
        } else {
            $stmt = mysqli_prepare($conexion, 'SELECT id FROM usuarios WHERE LOWER(correo) = LOWER(?) LIMIT 1');
            mysqli_stmt_bind_param($stmt, 's', $correoFormulario);
            mysqli_stmt_execute($stmt);
            $usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if (!$usuario) {
                $mensaje = 'No encontramos una cuenta con ese correo.';
                $error = true;
            } else {
                $codigo = (string) random_int(100000, 999999);
                $usuarioId = (int) $usuario['id'];
                $stmt = mysqli_prepare($conexion, 'UPDATE usuarios SET codigo_recuperacion = ?, codigo_expira = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?');
                mysqli_stmt_bind_param($stmt, 'si', $codigo, $usuarioId);
                $guardado = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                if ($guardado && enviarNotificacion($correoFormulario, 'Código de recuperación - Always Beautiful', "Tu código de recuperación es: $codigo. Vence en 15 minutos.")) {
                    $_SESSION['correo_recuperacion'] = $correoFormulario;
                    $mostrarCodigo = true;
                    $mensaje = 'Enviamos un código de 6 dígitos a tu correo. Revisa también la carpeta de spam.';
                } else {
                    mysqli_query($conexion, "UPDATE usuarios SET codigo_recuperacion = NULL, codigo_expira = NULL WHERE id = $usuarioId");
                    $mensaje = 'No fue posible enviar el código. El correo SMTP no está configurado o el servicio rechazó el envío.';
                    $error = true;
                }
            }
        }
    } elseif ($accion === 'cambiar_password') {
        $correoFormulario = strtolower(trim((string) ($_SESSION['correo_recuperacion'] ?? '')));
        $codigo = trim((string) ($_POST['codigo'] ?? ''));
        $nuevaPassword = (string) ($_POST['nueva_password'] ?? '');
        $confirmarPassword = (string) ($_POST['confirmar_password'] ?? '');
        if ($correoFormulario === '' || !preg_match('/^\\d{6}$/', $codigo)) {
            $mensaje = 'Escribe el código de seis dígitos enviado a tu correo.';
            $error = true;
            $mostrarCodigo = true;
        } elseif (strlen($nuevaPassword) < 6 || strlen($nuevaPassword) > 72) {
            $mensaje = 'La contraseña debe tener entre 6 y 72 caracteres.';
            $error = true;
            $mostrarCodigo = true;
        } elseif (!hash_equals($nuevaPassword, $confirmarPassword)) {
            $mensaje = 'Las contraseñas no coinciden.';
            $error = true;
            $mostrarCodigo = true;
        } else {
            $stmt = mysqli_prepare($conexion, 'SELECT id FROM usuarios WHERE LOWER(correo) = LOWER(?) AND codigo_recuperacion = ? AND codigo_expira > NOW() LIMIT 1');
            mysqli_stmt_bind_param($stmt, 'ss', $correoFormulario, $codigo);
            mysqli_stmt_execute($stmt);
            $usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if (!$usuario) {
                $mensaje = 'El código es incorrecto o venció. Solicita uno nuevo.';
                $error = true;
                $mostrarCodigo = false;
                unset($_SESSION['correo_recuperacion']);
            } else {
                $hash = password_hash($nuevaPassword, PASSWORD_BCRYPT);
                $usuarioId = (int) $usuario['id'];
                $stmt = mysqli_prepare($conexion, 'UPDATE usuarios SET contrasena = ?, codigo_recuperacion = NULL, codigo_expira = NULL WHERE id = ?');
                mysqli_stmt_bind_param($stmt, 'si', $hash, $usuarioId);
                $actualizado = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                if ($actualizado) {
                    unset($_SESSION['correo_recuperacion']);
                    $correoFormulario = '';
                    $mensaje = 'Contraseña actualizada. Ya puedes iniciar sesión con tu correo y la nueva contraseña.';
                } else {
                    $mensaje = 'No fue posible actualizar la contraseña. Inténtalo nuevamente.';
                    $error = true;
                    $mostrarCodigo = true;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recuperar contraseña | Always Beautiful</title>
<link rel="stylesheet" href="styles.css">
<style>
.recuperacion-pagina { align-items: center; display: grid; min-height: 100vh; padding: 32px 20px; box-sizing: border-box; }
.recuperacion-panel { animation: recuperar-entrada .45s ease-out both; background: rgba(255,255,255,.9); border: 1px solid #dec9ba; border-radius: 12px; box-shadow: 0 18px 50px rgba(58,39,34,.12); box-sizing: border-box; margin: 0 auto; max-width: 440px; padding: 34px; text-align: left; width: 100%; }
.recuperacion-marca { color: #8d5e43; font-size: 12px; font-weight: 700; letter-spacing: 0; margin: 0 0 10px; text-transform: uppercase; }
.recuperacion-panel h1 { color: #3a2722; font-family: Georgia, 'Times New Roman', serif; font-size: 30px; font-weight: 600; margin: 0 0 10px; }
.recuperacion-descripcion { color: #68554c; font-size: 15px; line-height: 1.55; margin: 0 0 22px; }
.recuperacion-panel form { text-align: left; }
.recuperacion-panel label { color: #44352e; display: block; font-size: 14px; font-weight: 600; margin: 0 0 7px; }
.recuperacion-panel input { background: #fff; border: 1px solid #d9c6b8; border-radius: 6px; box-sizing: border-box; color: #30231f; font: inherit; margin: 0 0 16px; min-height: 46px; padding: 11px 13px; width: 100%; }
.recuperacion-panel input:focus { border-color: #a96d4b; outline: 3px solid rgba(169,109,75,.16); }
.recuperacion-panel button { background: #481b18; border: 0; border-radius: 6px; color: #fffaf5; cursor: pointer; font: inherit; font-weight: 700; margin: 4px 0 0; min-height: 46px; padding: 11px 16px; transition: background .18s ease, transform .18s ease; width: 100%; }
.recuperacion-panel button:hover { background: #743629; transform: translateY(-1px); }
.recuperacion-secundario button { background: transparent; color: #743629; margin-top: 10px; }
.recuperacion-secundario button:hover { background: #f5ebe4; }
.recuperacion-mensaje { border-radius: 6px; font-size: 14px; line-height: 1.5; margin: 0 0 18px; padding: 12px 14px; }
.recuperacion-mensaje.error { background: #fff0ed; color: #8b2f24; }
.recuperacion-mensaje.exito { background: #eaf4eb; color: #245b33; }
.recuperacion-cuenta { background: #f7f0ea; border-radius: 6px; color: #5e493e; font-size: 14px; margin: 0 0 16px; overflow-wrap: anywhere; padding: 11px 13px; }
.recuperacion-volver { color: #805b48; display: inline-block; font-size: 14px; margin-top: 20px; text-decoration: none; }
.recuperacion-volver:hover { color: #481b18; text-decoration: underline; }
@keyframes recuperar-entrada { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@media (max-width: 520px) { .recuperacion-pagina { padding: 20px 14px; } .recuperacion-panel { padding: 26px 22px; } .recuperacion-panel h1 { font-size: 27px; } }
@media (prefers-reduced-motion: reduce) { .recuperacion-panel { animation: none; } .recuperacion-panel button { transition: none; } }
</style>
</head>
<body>
<main class="recuperacion-pagina">
<section class="recuperacion-panel" aria-labelledby="titulo-recuperacion">
    <p class="recuperacion-marca">Always Beautiful</p>
    <h1 id="titulo-recuperacion">Recuperar contraseña</h1>
    <p class="recuperacion-descripcion"><?= $mostrarCodigo ? 'Escribe el código enviado a tu correo y define una contraseña nueva.' : 'Te enviaremos un código temporal al correo asociado a tu cuenta.' ?></p>
    <?php if ($mensaje): ?><p class="recuperacion-mensaje <?= $error ? 'error' : 'exito' ?>" role="status"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if (!$mostrarCodigo): ?>
        <form method="POST" autocomplete="on">
            <input type="hidden" name="accion" value="enviar_codigo">
            <label for="correo-recuperacion">Correo electrónico</label>
            <input id="correo-recuperacion" type="email" name="correo" placeholder="nombre@ejemplo.com" autocomplete="email" required value="<?= htmlspecialchars($correoFormulario, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Enviar código</button>
        </form>
    <?php else: ?>
        <form method="POST" autocomplete="off">
            <input type="hidden" name="accion" value="cambiar_password">
            <p class="recuperacion-cuenta">Código para <strong><?= htmlspecialchars($correoFormulario, ENT_QUOTES, 'UTF-8') ?></strong></p>
            <label for="codigo-recuperacion">Código de 6 dígitos</label>
            <input id="codigo-recuperacion" type="text" name="codigo" placeholder="000000" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
            <label for="nueva-password">Nueva contraseña</label>
            <input id="nueva-password" type="password" name="nueva_password" placeholder="Mínimo 6 caracteres" minlength="6" maxlength="72" autocomplete="new-password" required>
            <label for="confirmar-password">Confirmar contraseña</label>
            <input id="confirmar-password" type="password" name="confirmar_password" placeholder="Repite la contraseña" minlength="6" maxlength="72" autocomplete="new-password" required>
            <button type="submit">Cambiar contraseña</button>
        </form>
        <form method="POST" class="recuperacion-secundario">
            <input type="hidden" name="accion" value="enviar_codigo">
            <input type="hidden" name="correo" value="<?= htmlspecialchars($correoFormulario, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Enviar otro código</button>
        </form>
    <?php endif; ?>
    <a class="recuperacion-volver" href="index.php">Volver al inicio</a>
</section>
</main>
</body>
</html>
