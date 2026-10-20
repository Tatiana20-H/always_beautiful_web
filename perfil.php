<?php
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];

if (!isset($_SESSION['usuario_id'])) {
    header("Location: inicio.php");
    exit();
}

$tokenCsrf = $_SESSION['token_csrf_perfil'] ?? bin2hex(random_bytes(32));
$_SESSION['token_csrf_perfil'] = $tokenCsrf;
$mensajePerfil = '';
$errorPerfil = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_password'])) {
    $tokenEnviado = $_POST['token_csrf'] ?? '';
    $passwordActual = (string) ($_POST['password_actual'] ?? '');
    $passwordNueva = (string) ($_POST['password_nueva'] ?? '');
    $passwordConfirmacion = (string) ($_POST['password_confirmacion'] ?? '');
    $usuarioId = (int) $_SESSION['usuario_id'];

    if (!is_string($tokenEnviado) || !hash_equals($tokenCsrf, $tokenEnviado)) {
        $mensajePerfil = 'La sesión del formulario expiró. Recarga la página e inténtalo de nuevo.';
        $errorPerfil = true;
    } elseif (strlen($passwordNueva) < 6 || strlen($passwordNueva) > 72) {
        $mensajePerfil = 'La nueva contraseña debe tener entre 6 y 72 caracteres.';
        $errorPerfil = true;
    } elseif (!hash_equals($passwordNueva, $passwordConfirmacion)) {
        $mensajePerfil = 'La nueva contraseña y su confirmación no coinciden.';
        $errorPerfil = true;
    } else {
        $stmtPassword = mysqli_prepare($conexion, 'SELECT contrasena FROM usuarios WHERE id = ? LIMIT 1');
        mysqli_stmt_bind_param($stmtPassword, 'i', $usuarioId);
        mysqli_stmt_execute($stmtPassword);
        $cuenta = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtPassword));
        mysqli_stmt_close($stmtPassword);
        if (!$cuenta || !password_verify($passwordActual, $cuenta['contrasena'])) {
            $mensajePerfil = 'La contraseña actual es incorrecta.';
            $errorPerfil = true;
        } else {
            $hashPassword = password_hash($passwordNueva, PASSWORD_BCRYPT);
            $stmtPassword = mysqli_prepare($conexion, 'UPDATE usuarios SET contrasena = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmtPassword, 'si', $hashPassword, $usuarioId);
            $actualizadoPassword = mysqli_stmt_execute($stmtPassword);
            mysqli_stmt_close($stmtPassword);
            $mensajePerfil = $actualizadoPassword ? 'Tu contraseña se cambió correctamente.' : 'No se pudo cambiar la contraseña. Inténtalo de nuevo.';
            $errorPerfil = !$actualizadoPassword;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $tokenEnviado = $_POST['token_csrf'] ?? '';
    if (!is_string($tokenEnviado) || !hash_equals($tokenCsrf, $tokenEnviado)) {
        $mensajePerfil = 'La sesión del formulario expiró. Recarga la página e inténtalo de nuevo.';
        $errorPerfil = true;
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $fechaNacimiento = trim($_POST['fecha_nacimiento'] ?? '');
        $longitudNombre = function_exists('mb_strlen') ? mb_strlen($nombre, 'UTF-8') : strlen($nombre);
        $errores = [];

        if ($nombre === '' || $longitudNombre > 100) {
            $errores[] = 'El nombre es obligatorio y no puede superar 100 caracteres.';
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 100) {
            $errores[] = 'Escribe un correo válido de máximo 100 caracteres.';
        }
        if (strtolower((string) ($_SESSION['rol'] ?? '')) === 'admin' && strcasecmp($correo, 'tachiandrade79@gmail.com') !== 0) {
            $errores[] = 'El correo de la cuenta administradora es fijo y no se puede cambiar.';
        }
        if ($fechaNacimiento !== '') {
            $fechaValidada = DateTime::createFromFormat('!Y-m-d', $fechaNacimiento);
            if (!$fechaValidada || $fechaValidada->format('Y-m-d') !== $fechaNacimiento || $fechaNacimiento > date('Y-m-d')) {
                $errores[] = 'La fecha de nacimiento no es válida.';
            }
        }

        if (!$errores) {
            $usuarioId = (int) $_SESSION['usuario_id'];
            $stmtCorreo = mysqli_prepare($conexion, 'SELECT id FROM usuarios WHERE correo = ? AND id <> ? LIMIT 1');
            if (!$stmtCorreo) {
                $errores[] = 'No se pudieron validar los datos. Inténtalo de nuevo.';
            } else {
                mysqli_stmt_bind_param($stmtCorreo, 'si', $correo, $usuarioId);
                mysqli_stmt_execute($stmtCorreo);
                $resultadoCorreo = mysqli_stmt_get_result($stmtCorreo);
                if ($resultadoCorreo && mysqli_num_rows($resultadoCorreo) > 0) {
                    $errores[] = 'Ese correo ya está asociado a otra cuenta.';
                }
                mysqli_stmt_close($stmtCorreo);
            }

            if (!$errores) {
                $fechaParaGuardar = $fechaNacimiento !== '' ? $fechaNacimiento : null;
                $stmtActualizar = mysqli_prepare($conexion, 'UPDATE usuarios SET nombre = ?, correo = ?, fecha_nacimiento = ? WHERE id = ?');
                if ($stmtActualizar) {
                    mysqli_stmt_bind_param($stmtActualizar, 'sssi', $nombre, $correo, $fechaParaGuardar, $usuarioId);
                    $actualizado = mysqli_stmt_execute($stmtActualizar);
                    mysqli_stmt_close($stmtActualizar);
                    if ($actualizado) {
                        $_SESSION['nombre'] = $nombre;
                        $_SESSION['correo'] = $correo;
                        $_SESSION['fecha_nacimiento'] = $fechaParaGuardar;
                        $mensajePerfil = 'Tus datos se actualizaron correctamente.';
                    } else {
                        $errores[] = 'No se pudieron guardar los cambios. Inténtalo de nuevo.';
                    }
                } else {
                    $errores[] = 'No se pudieron guardar los cambios. Inténtalo de nuevo.';
                }
            }
        }

        if ($errores) {
            $mensajePerfil = implode(' ', $errores);
            $errorPerfil = true;
        }
    }
}

$mensajeFoto = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil'])) {
    $tokenEnviado = $_POST['token_csrf'] ?? '';
    $archivo = $_FILES['foto_perfil'];
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $extensionesPorTipo = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $tipo = $archivo['tmp_name'] ? mime_content_type($archivo['tmp_name']) : '';

    if (!is_string($tokenEnviado) || !hash_equals($tokenCsrf, $tokenEnviado)) {
        $mensajeFoto = 'La sesión del formulario expiró. Recarga la página e inténtalo de nuevo.';
    } elseif ($archivo['error'] !== UPLOAD_ERR_OK || !in_array($tipo, $tiposPermitidos, true)) {
        $mensajeFoto = 'Selecciona una imagen JPG, PNG, GIF o WEBP válida.';
    } elseif ($archivo['size'] > 4 * 1024 * 1024) {
        $mensajeFoto = 'La imagen no puede superar 4 MB.';
    } else {
        $directorio = __DIR__ . '/IMG/perfiles';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }
        $extension = $extensionesPorTipo[$tipo];
        $nombreArchivo = 'usuario_' . (int) $_SESSION['usuario_id'] . '_' . time() . '.' . $extension;
        $rutaRelativa = 'IMG/perfiles/' . $nombreArchivo;
        if (move_uploaded_file($archivo['tmp_name'], $directorio . '/' . $nombreArchivo)) {
            $_SESSION['foto_perfil'] = $rutaRelativa;
            guardarDatosUsuario($conexion);
            $mensajeFoto = 'Foto de perfil actualizada.';
        } else {
            $mensajeFoto = 'No se pudo guardar la imagen.';
        }
    }
}

$nombreUsuario = $_SESSION['nombre'] ?? 'Usuario';
$totalCompras = count($_SESSION['historial'] ?? []);
$productosCarrito = 0;
foreach ($_SESSION['carrito'] ?? [] as $producto) {
    $productosCarrito += (int) ($producto['cantidad'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mi Perfil - Always Beautiful</title>
<link rel="stylesheet" href="styles.css">

<style>
.perfil-container{
    max-width: 850px;
    width: calc(100% - 40px);
    margin: 40px auto;
    padding: 30px;
    background: var(--ab-ivory);
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(58,39,34,0.12);
    text-align: center;
}

.foto-perfil { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--ab-bronze); }
.foto-predeterminada { display: inline-flex; align-items: center; justify-content: center; font-size: 48px; background: var(--ab-blush); }
.foto-form { margin: 18px auto 28px; max-width: 420px; }
.foto-form input { box-sizing: border-box; }
.foto-form button { width: auto; background: var(--ab-brown); }
.perfil-edicion { max-width: 560px; margin: 28px auto; padding: 22px; background: #fff; border: 1px solid var(--ab-sand); border-radius: 8px; text-align: left; }
.perfil-edicion h3 { margin-top: 0; color: var(--ab-brown); }
.perfil-campo { display: grid; gap: 6px; margin: 14px 0; color: var(--ab-mocha); font-size: 14px; font-weight: 600; }
.perfil-campo input { box-sizing: border-box; width: 100%; min-height: 42px; padding: 9px 11px; border: 1px solid var(--ab-sand); border-radius: 5px; color: var(--ab-dark-brown); font: inherit; }
.perfil-campo input:focus { outline: 2px solid var(--ab-terracotta); outline-offset: 1px; }
.perfil-aviso { margin: 14px auto; max-width: 560px; padding: 11px 14px; border-radius: 5px; background: #e8f4eb; color: #245b33; }
.perfil-aviso.error { background: #fff0ed; color: #8b2f24; }
.resumen-cuenta { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 25px 0; }
.resumen-cuenta div { background: #fff; border: 1px solid var(--ab-blush); border-radius: 10px; padding: 14px; color: var(--ab-mocha); }
.historial-compra { border: 1px solid var(--ab-sand); background: rgba(255,255,255,0.72); color: var(--ab-dark-brown); padding: 15px; margin: 15px 0; border-radius: 10px; text-align: left; }
@media (max-width: 600px) { .resumen-cuenta { grid-template-columns: 1fr; } }

.perfil-container h2{
    color: var(--ab-brown);
}

.dato{
    margin: 15px 0;
    font-size: 18px;
    color: var(--ab-mocha);
}

.btn{
    background: var(--ab-brown);
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.btn:hover{ background: var(--ab-terracotta); }
</style>

</head>
<body>

<?php include("header.php"); ?>

<div class="perfil-container">

    <h2>Mi Perfil</h2>

    <?php if (!empty($_SESSION['foto_perfil'])): ?>
        <img src="<?= htmlspecialchars($_SESSION['foto_perfil']) ?>" alt="Foto de perfil" class="foto-perfil">
    <?php else: ?>
        <div class="foto-perfil foto-predeterminada"></div>
    <?php endif; ?>

    <form action="perfil.php" method="POST" enctype="multipart/form-data" class="foto-form">
        <input type="hidden" name="token_csrf" value="<?= htmlspecialchars($tokenCsrf, ENT_QUOTES, 'UTF-8') ?>">
        <input type="file" id="foto-perfil-input" name="foto_perfil" accept="image/jpeg,image/png,image/gif,image/webp" required onchange="this.form.submit()">
        <label for="foto-perfil-input" class="btn">Elegir foto</label>
    </form>
    <?php if ($mensajeFoto): ?><p class="perfil-aviso <?= strpos($mensajeFoto, 'actualizada') === false ? 'error' : '' ?>" role="status"><?= htmlspecialchars($mensajeFoto) ?></p><?php endif; ?>

    <div class="dato">
        <strong>Usuario:</strong> <?= htmlspecialchars($nombreUsuario); ?><br>
        <strong>Correo:</strong> <?= htmlspecialchars($_SESSION['correo'] ?? ''); ?><br>
        <?php if (!empty($_SESSION['fecha_nacimiento'])): ?>
            <strong>Fecha de nacimiento:</strong> <?= htmlspecialchars($_SESSION['fecha_nacimiento']); ?>
        <?php endif; ?>
    </div>

    <?php if ($mensajePerfil): ?><p class="perfil-aviso <?= $errorPerfil ? 'error' : '' ?>" role="status"><?= htmlspecialchars($mensajePerfil) ?></p><?php endif; ?>

    <form action="perfil.php" method="POST" class="perfil-edicion">
        <h3>Datos personales</h3>
        <input type="hidden" name="token_csrf" value="<?= htmlspecialchars($tokenCsrf, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="actualizar_perfil" value="1">
        <label class="perfil-campo" for="nombre-perfil">Nombre
            <input id="nombre-perfil" type="text" name="nombre" maxlength="100" value="<?= htmlspecialchars($_POST['actualizar_perfil'] ?? '' ? ($_POST['nombre'] ?? '') : $nombreUsuario, ENT_QUOTES, 'UTF-8') ?>" autocomplete="name" required>
        </label>
        <label class="perfil-campo" for="correo-perfil">Correo electrónico
            <input id="correo-perfil" type="email" name="correo" maxlength="100" value="<?= htmlspecialchars($_POST['actualizar_perfil'] ?? '' ? ($_POST['correo'] ?? '') : ($_SESSION['correo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="email" required>
        </label>
        <label class="perfil-campo" for="fecha-nacimiento-perfil">Fecha de nacimiento
            <input id="fecha-nacimiento-perfil" type="date" name="fecha_nacimiento" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['actualizar_perfil'] ?? '' ? ($_POST['fecha_nacimiento'] ?? '') : ($_SESSION['fecha_nacimiento'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="bday">
        </label>
        <button class="btn" type="submit">Guardar cambios</button>
    </form>

    <form action="perfil.php" method="POST" class="perfil-edicion" autocomplete="off">
        <h3>Cambiar contraseña</h3>
        <input type="hidden" name="token_csrf" value="<?= htmlspecialchars($tokenCsrf, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="cambiar_password" value="1">
        <label class="perfil-campo" for="password-actual">Contraseña actual
            <input id="password-actual" type="password" name="password_actual" autocomplete="current-password" required>
        </label>
        <label class="perfil-campo" for="password-nueva">Nueva contraseña
            <input id="password-nueva" type="password" name="password_nueva" minlength="6" maxlength="72" autocomplete="new-password" required>
        </label>
        <label class="perfil-campo" for="password-confirmacion">Confirmar nueva contraseña
            <input id="password-confirmacion" type="password" name="password_confirmacion" minlength="6" maxlength="72" autocomplete="new-password" required>
        </label>
        <button class="btn" type="submit">Actualizar contraseña</button>
    </form>

    <div class="resumen-cuenta">
        <div><strong>Compras realizadas</strong><br><?= $totalCompras ?></div>
        <div><strong>Productos en carrito</strong><br><?= $productosCarrito ?></div>
        <div><strong>Estado</strong><br>Cuenta activa</div>
    </div>

    <h3>Historial de compras</h3>

<?php if(!empty($_SESSION['historial'])): ?>

    <?php foreach($_SESSION['historial'] as $compra): ?>

        <div class="historial-compra">

            <strong>Fecha:</strong> <?= htmlspecialchars((string) ($compra['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?> <br>
            <strong>Hora:</strong> <?= htmlspecialchars((string) ($compra['hora'] ?? ''), ENT_QUOTES, 'UTF-8') ?> <br>
            <strong>Total de productos:</strong> <?= (int) ($compra['cantidad_total'] ?? 0) ?> <br><br>

            <strong>Productos:</strong><br>

            <?php foreach($compra['productos'] as $nombre => $producto): ?>
                • <?= htmlspecialchars((string) $nombre, ENT_QUOTES, 'UTF-8') ?> — Cantidad: <?= (int) ($producto['cantidad'] ?? 0) ?> <br>
            <?php endforeach; ?>

            <br>
            <strong>Total pagado:</strong> $<?= number_format((float) ($compra['total'] ?? 0), 0, ',', '.') ?>

        </div>

    <?php endforeach; ?>

<?php else: ?>

    <p>No has realizado compras aún</p>

<?php endif; ?>

    <br>

    <a href="inicio.php">
        <button class="btn">Volver al inicio</button>
    </a>

</div>

</body>
</html>