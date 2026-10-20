<?php 
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];

// Obtener datos del usuario si está logueado
$usuario_logueado = false;
$nombre_usuario = "";
$correo_usuario = "";
$notificacion_contacto = $_SESSION['notificacion_contacto'] ?? null;
$datos_pendientes = $_SESSION['datos_contacto_pendientes'] ?? [];
unset($_SESSION['notificacion_contacto']);
unset($_SESSION['datos_contacto_pendientes']);

if (!isset($_SESSION['token_csrf_contacto'])) {
    $_SESSION['token_csrf_contacto'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['usuario_id'])) {
    $usuario_logueado = true;
    $resultado = mysqli_query($conexion, "SELECT nombre, correo FROM usuarios WHERE id=" . intval($_SESSION['usuario_id']));
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $usuario = mysqli_fetch_assoc($resultado);
        $nombre_usuario = $usuario['nombre'];
        $correo_usuario = $usuario['correo'];
    }
}

// Incluir header completo solo si está logueado
if ($usuario_logueado) {
    include("header.php");
} else {
    // Header simplificado para visitantes
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Formulario de Contacto - Always Beautiful</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<header class="header">
    <div class="barra-principal">
        <a href="inicio.php" class="marca" aria-label="Always Beautiful, inicio">
            <img src="IMG/logo.png" alt="Always Beautiful" class="logo-header">
            <span class="nombre-marca">Always Beautiful</span>
        </a>

        <nav class="menu" aria-label="Navegación principal">
            <a href="inicio.php" class="item"><img src="IMG/Inicio-logo.png" alt="" class="menu-icon">Inicio</a>
            <div class="item dropdown">
                <button type="button" class="item-trigger" aria-expanded="false"><img src="IMG/productos-logo.png" alt="" class="menu-icon">Productos</button>
                <div class="submenu">
                    <a href="maquillaje.php">Maquillaje</a>
                    <a href="Accesorios.php">Accesorios</a>
                    <a href="cabello.php">Cuidado del cabello</a>
                    <a href="piel.php">Cuidado de la piel</a>
                </div>
            </div>
            <div class="item dropdown modulo-nosotros">
                <button type="button" class="item-trigger" aria-expanded="false"><img src="IMG/nosotros-logo.png" alt="" class="menu-icon">Nosotros</button>
                <div class="submenu">
                    <a href="quienes.php">Quiénes somos</a>
                    <a href="historia.php">Historia</a>
                    <a href="mision.php">Misión</a>
                    <a href="vision.php">Visión</a>
                    <a href="valores.php">Valores</a>
                </div>
            </div>
            <div class="item dropdown modulo-blog">
                <button type="button" class="item-trigger" aria-expanded="false"><img src="IMG/blog-logo.png" alt="" class="menu-icon">Blog</button>
                <div class="submenu"><a href="articulos.php">Artículos</a></div>
            </div>
            <div class="item dropdown">
                <button type="button" class="item-trigger" aria-expanded="false"><img src="IMG/contacto-logo.png" alt="" class="menu-icon">Contacto</button>
                <div class="submenu">
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <a href="formulario.php">Formulario</a>
                    <?php endif; ?>
                    <a href="ubicacion.php">Ubicación</a>
                </div>
            </div>
            <div class="item dropdown modulo-politicas">
                <button type="button" class="item-trigger" aria-expanded="false"><img src="IMG/politicas-logo.png" alt="" class="menu-icon">Políticas</button>
                <div class="submenu">
                    <a href="terminos.php">Términos</a>
                    <a href="privacidad.php">Privacidad</a>
                    <a href="preguntas.php">Preguntas frecuentes</a>
                </div>
            </div>
        </nav>

        <div class="acciones-header">
            <div class="acceso-cuenta">
                <button type="button" class="perfil-trigger visitante-trigger" data-auth-mode="login" aria-label="Registrarse o iniciar sesión">
                    <span class="avatar-generico" aria-hidden="true">👤</span>
                </button>
            </div>
        </div>
    </div>
</header>

<!-- Modal de autenticación solo para visitantes -->
<div class="modal-auth" id="autenticacion" aria-hidden="false" style="display: none;">
    <div class="modal-auth-contenido" role="dialog" aria-modal="true" aria-labelledby="titulo-auth">
        <button type="button" class="modal-cerrar" aria-label="Cerrar">&times;</button>
        <img src="IMG/logo.png" alt="Always Beautiful" class="modal-logo">
        <div class="auth-tabs">
            <button type="button" class="auth-tab" data-auth-tab="register">Crear cuenta</button>
            <button type="button" class="auth-tab activo" data-auth-tab="login">Iniciar sesión</button>
        </div>

        <div class="auth-panel oculto" data-auth-panel="register">
            <h2 id="titulo-auth">Regístrate para continuar</h2>
            <form action="guardar-usuario.php" method="POST" autocomplete="off">
                <input type="text" name="nombre" placeholder="Nombre completo" autocomplete="off" required>
                <input type="email" name="correo" placeholder="Correo" autocomplete="off" required>
                <input type="date" name="fecha_nacimiento" required>
                <div class="campo-password"><input type="password" name="password" placeholder="Contraseña" autocomplete="new-password" minlength="6" maxlength="72" required><button type="button" class="ver-password" aria-label="Mostrar contraseña"></button></div>
                <div class="campo-password"><input type="password" name="password_confirm" placeholder="Confirmar contraseña" autocomplete="new-password" minlength="6" maxlength="72" required><button type="button" class="ver-password" aria-label="Mostrar confirmación"></button></div>
                <button type="submit">Crear cuenta</button>
            </form>
        </div>

        <div class="auth-panel" data-auth-panel="login">
            <h2>Inicia sesión para continuar</h2>
            <form action="login.php" method="POST" autocomplete="on">
                <input type="email" name="correo" placeholder="Correo" autocomplete="username" required>
                <div class="campo-password"><input type="password" name="password" placeholder="Contraseña" autocomplete="current-password" required><button type="button" class="ver-password" aria-label="Mostrar contraseña"></button></div>
                <p><a href="recuperar.php">¿Has olvidado tu contraseña?</a></p>
                <button type="submit">Ingresar</button>
            </form>
        </div>
    </div>
</div>

<script>
const dropdowns = document.querySelectorAll('.dropdown');
dropdowns.forEach(item => {
    item.addEventListener('click', function (event) {
        event.stopPropagation();
        const trigger = this.querySelector('.item-trigger');
        const isOpen = this.classList.contains('active');
        dropdowns.forEach(drop => {
            drop.classList.remove('active');
            const btn = drop.querySelector('.item-trigger');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        });
        if (!isOpen) {
            this.classList.add('active');
            if (trigger) trigger.setAttribute('aria-expanded', 'true');
        }
    });
});
document.addEventListener('click', () => {
    dropdowns.forEach(drop => {
        drop.classList.remove('active');
        const btn = drop.querySelector('.item-trigger');
        if (btn) btn.setAttribute('aria-expanded', 'false');
    });
});

// Scripts básicos del header para visitantes
const visitanteTrigger = document.querySelector(".visitante-trigger");
const modalAuth = document.getElementById("autenticacion");
const authTabs = document.querySelectorAll("[data-auth-tab]");
const authPanels = document.querySelectorAll("[data-auth-panel]");
const modalCerrar = document.querySelector(".modal-cerrar");

visitanteTrigger?.addEventListener("click", () => {
    modalAuth.style.display = "flex";
    modalAuth.setAttribute("aria-hidden", "false");
});

modalCerrar?.addEventListener("click", () => {
    modalAuth.style.display = "none";
    modalAuth.setAttribute("aria-hidden", "true");
});

modalAuth?.addEventListener("click", (e) => {
    if (e.target === modalAuth) {
        modalAuth.style.display = "none";
        modalAuth.setAttribute("aria-hidden", "true");
    }
});

authTabs.forEach(tab => {
    tab.addEventListener("click", () => {
        authTabs.forEach(t => t.classList.remove("activo"));
        authPanels.forEach(p => p.classList.add("oculto"));
        
        tab.classList.add("activo");
        document.querySelector(`[data-auth-panel="${tab.dataset.authTab}"]`).classList.remove("oculto");
    });
});
document.querySelectorAll('.ver-password').forEach(function (boton) {
    boton.addEventListener('click', function () {
        const campo = this.parentElement.querySelector('input');
        if (!campo) return;
        campo.type = campo.type === 'password' ? 'text' : 'password';
    });
});
</script>
    <?php
}
?>

<link rel="stylesheet" href="styles.css">

<main class="formulario-container contacto-layout">
    <section class="contacto-intro" aria-labelledby="titulo-contacto">
        <p class="contacto-eyebrow">Atención al cliente</p>
        <h1 id="titulo-contacto">Estamos aquí para ayudarte.</h1>
        <p>Cuéntanos qué necesitas y nuestro equipo revisará tu mensaje desde la bandeja de atención.</p>
        <div class="contacto-destino">
            <span class="contacto-destino-icono" aria-hidden="true">✉</span>
            <span><strong>Equipo Always Beautiful</strong><small>Recepción directa en administración</small></span>
        </div>
    </section>

    <section class="contacto-formulario-columna" aria-label="Formulario de contacto">

    <?php if ($notificacion_contacto): ?>
        <?php
            $notificacionTipo = is_array($notificacion_contacto) ? ($notificacion_contacto['tipo'] ?? 'error') : 'error';
            $notificacionTexto = is_array($notificacion_contacto) ? ($notificacion_contacto['mensaje'] ?? '') : $notificacion_contacto;
        ?>
        <div class="contacto-notificacion <?= $notificacionTipo === 'exito' ? 'es-exito' : 'es-error' ?>" role="<?= $notificacionTipo === 'exito' ? 'status' : 'alert' ?>">
            <?= htmlspecialchars((string) $notificacionTexto, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form action="Procesar_formulario.php" method="POST" enctype="multipart/form-data" class="formulario formulario-contacto" autocomplete="on">
        <header class="contacto-formulario-header">
            <p class="contacto-formulario-kicker">Contacto</p>
            <h2>Envíanos un mensaje</h2>
            <p>Los campos marcados son obligatorios.</p>
        </header>
        <input type="hidden" name="token_csrf" value="<?= htmlspecialchars($_SESSION['token_csrf_contacto'], ENT_QUOTES, 'UTF-8') ?>">

        <?php if ($usuario_logueado): ?>
            <input type="hidden" name="nombre" value="<?= htmlspecialchars($nombre_usuario, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="correo" value="<?= htmlspecialchars($correo_usuario, ENT_QUOTES, 'UTF-8') ?>">
            <div class="contacto-identidad">Enviaremos tu mensaje asociado a <strong><?= htmlspecialchars($nombre_usuario, ENT_QUOTES, 'UTF-8') ?></strong> · <?= htmlspecialchars($correo_usuario, ENT_QUOTES, 'UTF-8') ?></div>
        <?php else: ?>
            <div class="contacto-campos-identidad">
                <label class="contacto-campo" for="contacto-nombre">Nombre
                    <input id="contacto-nombre" type="text" name="nombre" maxlength="100" value="<?= htmlspecialchars($datos_pendientes['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="name" required>
                </label>
                <label class="contacto-campo" for="contacto-correo">Correo electrónico
                    <input id="contacto-correo" type="email" name="correo" maxlength="100" value="<?= htmlspecialchars($datos_pendientes['correo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="email" required>
                </label>
            </div>
        <?php endif; ?>

        <label class="contacto-campo" for="contacto-asunto">Asunto
            <input id="contacto-asunto" type="text" name="asunto" maxlength="200" value="<?= htmlspecialchars($datos_pendientes['asunto'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="¿Sobre qué trata tu consulta?" required>
        </label>

        <label class="contacto-campo" for="contacto-mensaje">Mensaje
            <div class="mensaje-contenedor">
                <textarea id="contacto-mensaje" name="mensaje" rows="7" placeholder="Escribe aquí los detalles de tu consulta..." required><?= htmlspecialchars($datos_pendientes['mensaje'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <label class="foto-mensaje" title="Adjuntar una foto">
                    <span aria-hidden="true">📷</span>
                    <span>Adjuntar foto</span>
                    <input type="file" name="foto_mensaje" accept="image/jpeg,image/png,image/webp">
                </label>
                <span class="contacto-ayuda-archivo">JPG, PNG o WEBP, máximo 5 MB</span>
            </div>
        </label>

        <button type="submit">
            Enviar mensaje <span aria-hidden="true">→</span>
        </button>
    </form>
    </section>
</main>

</body>
</html>