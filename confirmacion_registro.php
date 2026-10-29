<?php
session_start();
include __DIR__ . '/conexion.php';
$conexion = $GLOBALS['conexion'];
$correo = (string) ($_SESSION['correo_verificacion'] ?? '');
$mensaje = (string) ($_SESSION['aviso_verificacion'] ?? '');
unset($_SESSION['aviso_verificacion']);
$error = false;

if ($correo === '') {
    header('Location: registro.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = (string) ($_POST['accion'] ?? 'verificar');
    if ($accion === 'reenviar') {
        $codigo = (string) random_int(100000, 999999);
        $stmt = mysqli_prepare($conexion, 'UPDATE usuarios SET codigo_verificacion = ?, verificacion_expira = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE LOWER(correo) = LOWER(?) AND correo_verificado = 0');
        mysqli_stmt_bind_param($stmt, 'ss', $codigo, $correo);
        $guardado = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0;
        mysqli_stmt_close($stmt);
        if ($guardado && enviarNotificacion($correo, 'Verifica tu correo - Always Beautiful', "Tu nuevo código de verificación es: $codigo. Vence en 15 minutos.")) {
            $mensaje = 'Te enviamos un código de 6 dígitos a tu correo.';
        } else {
            mysqli_query($conexion, "UPDATE usuarios SET codigo_verificacion = NULL, verificacion_expira = NULL WHERE LOWER(correo) = LOWER('" . mysqli_real_escape_string($conexion, $correo) . "')");
            $mensaje = 'No se pudo enviar el código. Revisa la configuración del correo.';
            $error = true;
        }
    } else {
        $codigo = trim((string) ($_POST['codigo'] ?? ''));
        if (!preg_match('/^\d{6}$/', $codigo)) {
            $mensaje = 'Escribe el código de 6 dígitos que enviamos a tu correo.';
            $error = true;
        } else {
            $stmt = mysqli_prepare($conexion, 'SELECT id, nombre, correo, rol, genero, foto_perfil FROM usuarios WHERE LOWER(correo) = LOWER(?) AND codigo_verificacion = ? AND verificacion_expira > NOW() AND correo_verificado = 0 LIMIT 1');
            mysqli_stmt_bind_param($stmt, 'ss', $correo, $codigo);
            mysqli_stmt_execute($stmt);
            $usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
            if (!$usuario) {
                $mensaje = 'El código es incorrecto o venció. Solicita uno nuevo.';
                $error = true;
            } else {
                $usuarioId = (int) $usuario['id'];
                $stmt = mysqli_prepare($conexion, 'UPDATE usuarios SET correo_verificado = 1, codigo_verificacion = NULL, verificacion_expira = NULL WHERE id = ?');
                mysqli_stmt_bind_param($stmt, 'i', $usuarioId);
                $verificado = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                if ($verificado) {
                    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
                    $historial = mysqli_prepare($conexion, "INSERT INTO historial_actividades (usuario_id, accion, descripcion, ip_address) VALUES (?, 'REGISTRO', 'Correo verificado y cuenta activada', ?)");
                    if ($historial) {
                        mysqli_stmt_bind_param($historial, 'is', $usuarioId, $ip);
                        mysqli_stmt_execute($historial);
                        mysqli_stmt_close($historial);
                    }

                    session_regenerate_id(true);
                    $_SESSION['usuario_id'] = $usuarioId;
                    $_SESSION['nombre'] = $usuario['nombre'];
                    $_SESSION['correo'] = $usuario['correo'];
                    $_SESSION['rol'] = $usuario['rol'];
                    $_SESSION['genero'] = $usuario['genero'] ?? null;
                    $_SESSION['foto_perfil'] = $usuario['foto_perfil'] ?? null;
                    cargarDatosUsuario($conexion, $usuarioId);
                    unset($_SESSION['correo_verificacion']);
                    $_SESSION['notificacion'] = ['tipo' => 'verde', 'mensaje' => 'Correo verificado. Tu sesión se inició correctamente.', 'modo' => 'login'];
                    header('Location: inicio.php');
                    exit();
                }
                $mensaje = 'No se pudo activar la cuenta. Inténtalo de nuevo.';
                $error = true;
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
<title>Verificar correo | Always Beautiful</title>
<link rel="stylesheet" href="styles.css">
<style>
.verificacion-pagina { align-items: center; display: grid; min-height: 100vh; padding: 24px 16px; }
.verificacion-panel { background: rgba(255,255,255,.94); border: 1px solid #dec9ba; border-radius: 12px; box-shadow: 0 18px 50px rgba(58,39,34,.12); box-sizing: border-box; margin: auto; max-width: 430px; padding: 34px; width: 100%; }
.verificacion-panel h1 { color: #3a2722; font: 600 29px Georgia, 'Times New Roman', serif; margin: 0 0 10px; }
.verificacion-panel p { color: #68554c; font-size: 15px; line-height: 1.5; }
.verificacion-panel label { display: block; font-weight: 600; margin: 22px 0 7px; }
.verificacion-panel input { box-sizing: border-box; font-size: 20px; letter-spacing: 3px; text-align: center; width: 100%; }
.verificacion-panel button { background: #481b18; border-radius: 6px; min-height: 46px; }
.verificacion-panel .reenviar button { background: transparent; color: #743629; margin-top: 10px; }
.verificacion-aviso { background: #eaf4eb; border-radius: 6px; color: #245b33 !important; padding: 12px; }
.verificacion-aviso.error { background: #fff0ed; color: #8b2f24 !important; }
.verificacion-email { overflow-wrap: anywhere; }
@media (max-width: 480px) { .verificacion-panel { padding: 26px 22px; } }
</style>
</head>
<body>
<main class="verificacion-pagina">
<section class="verificacion-panel" aria-labelledby="titulo-verificacion">
    <p>Always Beautiful</p>
    <h1 id="titulo-verificacion">Confirma tu correo</h1>
    <p>Enviamos un código de 6 dígitos a <strong class="verificacion-email"><?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8') ?></strong>. El código vence en 15 minutos.</p>
    <?php if ($mensaje !== ''): ?><p class="verificacion-aviso <?= $error ? 'error' : '' ?>" role="status"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <form method="POST" autocomplete="off">
        <input type="hidden" name="accion" value="verificar">
        <label for="codigo-verificacion">Código de verificación</label>
        <input id="codigo-verificacion" name="codigo" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000" required>
        <button type="submit">Verificar correo</button>
    </form>
    <form method="POST" class="reenviar">
        <input type="hidden" name="accion" value="reenviar">
        <button type="submit">Enviar otro código</button>
    </form>
</section>
</main>
<?php if ($mensaje !== ''): ?>
<script>
setTimeout(() => document.querySelector('.verificacion-aviso')?.remove(), 2500);
</script>
<?php endif; ?>
</body>
</html>