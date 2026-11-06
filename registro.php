<?php
session_start();
require_once __DIR__ . '/conexion.php';
$conexion = $GLOBALS['conexion'];
$mensajeVerificacion = '';
$pasoVerificacion = $_SESSION['registro_paso'] ?? '';
$correoPendiente = $_SESSION['registro_correo'] ?? '';
$avisoRegistro = $_SESSION['registro_mensaje'] ?? '';
$esVerificacion = isset($_GET['verificar']) && $correoPendiente !== '';
unset($_SESSION['registro_mensaje']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reenviar_codigo']) && $correoPendiente !== '') {
    $stmt = $conexion->prepare("SELECT codigo_hash FROM registros_pendientes WHERE correo = ? AND paso = 'correo'");
    $stmt->bind_param('s', $correoPendiente);
    $stmt->execute();
    $pendienteReenvio = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $codigoNuevo = (string) random_int(100000, 999999);
    $hashNuevo = password_hash($codigoNuevo, PASSWORD_DEFAULT);
    if ($pendienteReenvio) {
        $stmt = $conexion->prepare("UPDATE registros_pendientes SET codigo_hash = ?, codigo_expira = DATE_ADD(NOW(), INTERVAL 10 MINUTE), intentos = 0 WHERE correo = ? AND paso = 'correo'");
        $stmt->bind_param('ss', $hashNuevo, $correoPendiente);
        $actualizado = $stmt->execute();
        $stmt->close();
        if ($actualizado && enviarNotificacion($correoPendiente, 'Verifica tu correo - Always Beautiful', "Tu nuevo código de verificación es: {$codigoNuevo}\nVence en 10 minutos.")) {
            $avisoRegistro = 'Enviamos un nuevo código a tu correo.';
        } else {
            $avisoRegistro = 'No se pudo enviar el correo. Configura SMTP_PASSWORD con una contraseña de aplicación de Google y vuelve a intentarlo.';
        }
    } else {
        $avisoRegistro = 'No encontramos un registro pendiente para ese correo. Vuelve a completar el formulario.';
        unset($_SESSION['registro_correo'], $_SESSION['registro_paso']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo_verificacion'])) {
    $codigoIngresado = trim($_POST['codigo_verificacion']);
    $stmt = $conexion->prepare("SELECT * FROM registros_pendientes WHERE correo = ? AND paso = 'correo' AND codigo_expira > NOW() AND intentos < 5");
    $stmt->bind_param('s', $correoPendiente);
    $stmt->execute();
    $pendiente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$pendiente || !password_verify($codigoIngresado, $pendiente['codigo_hash'])) {
        if ($pendiente) {
            $stmt = $conexion->prepare('UPDATE registros_pendientes SET intentos = intentos + 1 WHERE correo = ?');
            $stmt->bind_param('s', $correoPendiente);
            $stmt->execute();
            $stmt->close();
        }
        $mensajeVerificacion = 'El código no es válido, venció o superó el límite de intentos.';
    } else {
        $correo = $pendiente['correo'];
        $nombre = $pendiente['nombre'];
        $hash = $pendiente['contrasena'];
        $fechaNacimiento = $pendiente['fecha_nacimiento'];
        $genero = $pendiente['genero'];
        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, correo, contrasena, rol, fecha_nacimiento, genero, correo_verificado, correo_padre, padre_verificado) VALUES (?, ?, ?, 'usuario', ?, ?, 1, NULL, 0)");
        $stmt->bind_param('sssss', $nombre, $correo, $hash, $fechaNacimiento, $genero);
        $creado = $stmt->execute();
        $usuarioId = $conexion->insert_id;
        $stmt->close();
        if ($creado) {
            $stmt = $conexion->prepare('DELETE FROM registros_pendientes WHERE correo = ?');
            $stmt->bind_param('s', $correo);
            $stmt->execute();
            $stmt->close();
            unset($_SESSION['registro_correo'], $_SESSION['registro_paso']);
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = (int) $usuarioId;
            $_SESSION['nombre'] = $nombre;
            $_SESSION['correo'] = $correo;
            $_SESSION['rol'] = 'usuario';
            $_SESSION['genero'] = $genero;
            $_SESSION['foto_perfil'] = null;
            cargarDatosUsuario($conexion, (int) $usuarioId);
            $_SESSION['notificacion'] = ['tipo' => 'verde', 'mensaje' => 'Correo verificado. Bienvenido a Always Beautiful.', 'modo' => 'login'];
            header('Location: inicio.php');
            exit();
        }
        $mensajeVerificacion = 'No fue posible crear la cuenta. Puede que el correo ya esté registrado.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registro - Always Beautiful</title>
<link rel="stylesheet" href="styles.css">
<style>
body.pagina-registro { background: #f6efe9; color: #261d1a; }
.pagina-registro .login-container { min-height: 100vh; height: auto; }
.pagina-registro .left { background-color: #d8b8a8; background-image: linear-gradient(180deg, rgba(38,29,26,.03), rgba(38,29,26,.12)), url("IMG/logo.png"); }
.pagina-registro .left::before { background: transparent; }
.pagina-registro .right { align-items: center; background: #f6efe9; box-sizing: border-box; min-height: 100vh; padding: 36px; }
.pagina-registro .form-wrapper { background: #fbf7f3; border: 1px solid #e5d3c3; border-radius: 12px; box-shadow: 0 16px 42px rgba(58,39,34,.12); max-width: 430px; padding: 34px; text-align: left; width: 100%; }
.pagina-registro .form-wrapper h2 { color: #481b18; font-size: 25px; margin: 0 0 10px; text-align: center; }
.registro-intro { color: #75665d; line-height: 1.55; margin: 0 auto 22px; max-width: 340px; text-align: center; }
.registro-correo { color: #743629; font-weight: 700; overflow-wrap: anywhere; }
.pagina-registro .form-wrapper form { text-align: left; }
.pagina-registro .form-wrapper input, .pagina-registro .form-wrapper select { background: #fff; border: 1px solid #cdbfb3; border-radius: 6px; box-sizing: border-box; color: #403834; margin: 8px 0; min-height: 46px; padding: 12px; width: 100%; }
.pagina-registro .campo-password input { padding-right: 48px; }
.pagina-registro .campo-password .ver-password { color: #5b3c35; right: 12px; top: 21px; }
.pagina-registro .form-wrapper button { background: #481b18; border-radius: 6px; color: #fff; font-weight: 700; margin-top: 10px; min-height: 46px; transition: background .18s ease, transform .18s ease; }
.pagina-registro .form-wrapper button:hover { background: #743629; transform: translateY(-1px); }
.pagina-registro .reenviar-form button { background: transparent; border: 1px solid #a96d4b; color: #743629; margin-top: 8px; }
.pagina-registro .reenviar-form button:hover { background: #f1e3da; }
.registro-mensaje { background: #f1e3da; border-left: 4px solid #a96d4b; border-radius: 6px; color: #5b3c35; line-height: 1.5; margin: 0 0 16px; padding: 12px 14px; }
.registro-error { background: #f7e6e2; border-left-color: #a33b32; color: #702b25; }
.pagina-registro .form-wrapper p { color: #75665d; line-height: 1.5; }
.pagina-registro .form-wrapper a { color: #743629; font-weight: 700; }
.pagina-verificacion { background: linear-gradient(140deg, #f8f2ee 0%, #efe3da 55%, #f6efe9 100%); }
.pagina-verificacion .login-container { align-items: center; justify-content: center; min-height: 100vh; }
.pagina-verificacion .left { display: none; }
.pagina-verificacion .right { background: transparent; min-height: 100vh; padding: 32px 20px; width: 100%; }
.pagina-verificacion .form-wrapper { border-color: #dfc7b8; max-width: 470px; overflow: hidden; padding: 38px 40px 34px; position: relative; }
.pagina-verificacion .form-wrapper::before { background: linear-gradient(90deg, #481b18, #a96d4b); content: ""; height: 5px; left: 0; position: absolute; right: 0; top: 0; }
.verificacion-marca { color: #481b18; font-family: Georgia, "Times New Roman", serif; font-size: 15px; font-weight: 700; margin-bottom: 25px; text-align: center; }
.verificacion-marca span { color: #a96d4b; }
.verificacion-pasos { align-items: center; color: #75665d; display: flex; font-size: 12px; justify-content: center; margin: 0 auto 23px; max-width: 310px; }
.verificacion-pasos span { align-items: center; display: inline-flex; gap: 7px; white-space: nowrap; }
.verificacion-pasos span:last-child { color: #743629; font-weight: 700; }
.verificacion-pasos b { align-items: center; background: #e5d3c3; border-radius: 50%; color: #5b3c35; display: inline-flex; height: 24px; justify-content: center; width: 24px; }
.verificacion-pasos span:last-child b { background: #743629; color: #fff; }
.verificacion-pasos i { background: #d8b8a8; height: 1px; margin: 0 12px; width: 40px; }
.pagina-verificacion .form-wrapper h2 { font-size: 27px; margin-bottom: 8px; }
.pagina-verificacion .registro-intro { margin-bottom: 24px; }
.pagina-verificacion .form-wrapper label { color: #5b3c35; display: block; font-size: 13px; font-weight: 700; margin: 0 0 6px; }
.pagina-verificacion .form-wrapper input[name="codigo_verificacion"] { font-family: "Courier New", monospace; font-size: 25px; font-weight: 700; height: 58px; letter-spacing: 0; margin: 0 0 8px; text-align: center; }
.pagina-verificacion .form-wrapper input[name="codigo_verificacion"]::placeholder { color: #b9957d; font-size: 22px; opacity: .8; }
.pagina-verificacion .registro-mensaje { font-size: 13px; margin-bottom: 18px; }
.pagina-verificacion .form-wrapper > .reenviar-form { margin-top: 2px; }
.verificacion-ayuda { color: #75665d; font-size: 12px; margin: 15px 0 0; text-align: center; }
@media (max-width: 720px) {
    .pagina-registro .login-container { display: block; min-height: 100vh; }
    .pagina-registro .left { display: none; }
    .pagina-registro .right { min-height: 100vh; padding: 22px 16px; width: 100%; }
    .pagina-registro .form-wrapper { padding: 26px 20px; }
    .pagina-verificacion .right { padding: 18px 14px; }
    .pagina-verificacion .form-wrapper { padding: 32px 22px 24px; }
    .verificacion-pasos i { margin: 0 7px; width: 24px; }
}
</style>
</head>
<body class="pagina-registro <?= $esVerificacion ? 'pagina-verificacion' : '' ?>">

<div class="login-container">

    <div class="left"></div>

    <div class="right">

        <div class="form-wrapper">
            <?php if ($esVerificacion): ?><div class="verificacion-marca">Always <span>Beautiful</span></div><?php endif; ?>
            <?php
            if ($avisoRegistro !== '') {
                echo '<div class="registro-mensaje" role="status">' . htmlspecialchars($avisoRegistro) . '</div>';
            }
            if ($mensajeVerificacion !== '') {
                echo '<div class="registro-mensaje registro-error" role="alert">' . htmlspecialchars($mensajeVerificacion) . '</div>';
            }
            if (isset($_SESSION['errores'])) {
                echo '<div class="registro-mensaje registro-error" role="alert">';
                foreach ($_SESSION['errores'] as $error) {
                    echo '<p>' . htmlspecialchars($error) . '</p>';
                }
                echo '</div>';
                unset($_SESSION['errores']);
            }
            ?>
            <h2><?= isset($_GET['verificar']) && $correoPendiente !== '' ? 'Verifica tu correo' : 'Crea tu cuenta' ?></h2>
            <?php if (isset($_GET['verificar']) && $correoPendiente !== ''): ?>
            <div class="verificacion-pasos" aria-label="Paso 2 de 2">
                <span><b>1</b> Cuenta</span><i aria-hidden="true"></i><span><b>2</b> Correo</span>
            </div>
            <p class="registro-intro">Enviamos un código de seis dígitos a<br><span class="registro-correo"><?= htmlspecialchars($correoPendiente) ?></span></p>
            <form method="POST">
                <label for="codigo-verificacion">Código de verificación</label>
                <input id="codigo-verificacion" type="text" name="codigo_verificacion" placeholder="000000" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" required autofocus>
                <button type="submit">Verificar y continuar</button>
            </form>
            <form class="reenviar-form" method="POST"><button type="submit" name="reenviar_codigo" value="1">Reenviar código</button></form>
            <p class="verificacion-ayuda">El código vence en 10 minutos. Revisa también la carpeta de spam.</p>
            <?php else: ?>
            <p class="registro-intro">Regístrate con tu correo y verifica la cuenta para continuar.</p>
            <form action="guardar-usuario.php" method="POST">

                <input type="text" name="nombre" placeholder="Nombre completo" required>

                <input type="email" name="correo" placeholder="Correo" required>

                <input type="date" name="fecha_nacimiento" required>
                <select name="genero" required>
                    <option value="">Selecciona tu género</option>
                    <option value="mujer">Mujer</option>
                    <option value="hombre">Hombre</option>
                </select>

                <div class="campo-password"><input type="password" name="password" placeholder="Contraseña (8-16: letras, número y símbolo)" minlength="8" maxlength="16" required><button type="button" class="ver-password" aria-label="Mostrar contraseña">&#128065;</button></div>

                <div class="campo-password"><input type="password" name="password_confirm" placeholder="Confirmar contraseña" minlength="8" maxlength="16" required><button type="button" class="ver-password" aria-label="Mostrar confirmación">&#128065;</button></div>

                <button type="submit">Crear cuenta</button>

                <p>¿Ya tienes cuenta? <a href="index.php">Iniciar sesión aquí</a></p>

            </form>
            <?php endif; ?>
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