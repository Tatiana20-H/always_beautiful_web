<?php 
session_start();
if (strtolower((string) ($_SESSION['rol'] ?? '')) === 'admin') {
    header('Location: Administrador/index.php');
    exit();
}
include("conexion.php");
$conexion = $GLOBALS['conexion'];
$correoAdministrador = correoAdministradorNotificaciones();
$enlaceGmailAdministrador = 'https://mail.google.com/mail/?view=cm&fs=1&to=' . rawurlencode($correoAdministrador) . '&su=' . rawurlencode('Consulta a Always Beautiful');

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

if (!empty($_SESSION['usuario_id']) && ($_SESSION['rol'] ?? '') === 'usuario') {
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
                                    <a href="perfumes.php">Perfumes</a>
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
                <select name="genero" required>
                    <option value="">Selecciona tu género</option>
                    <option value="mujer">Mujer</option>
                    <option value="hombre">Hombre</option>
                </select>
                <div class="campo-password"><input type="password" name="password" placeholder="Contraseña (8-16: letras, número y símbolo)" minlength="8" maxlength="16" required><button type="button" class="ver-password" aria-label="Mostrar contraseña">👁️</button></div>
                <div class="campo-password"><input type="password" name="password_confirm" placeholder="Confirmar contraseña" minlength="8" maxlength="16" required><button type="button" class="ver-password" aria-label="Mostrar confirmación">👁️</button></div>
                <button type="submit">Crear cuenta</button>
            </form>
        </div>

        <div class="auth-panel" data-auth-panel="login">
            <h2>Inicia sesión para continuar</h2>
            <form action="login.php" method="POST">
                <input type="email" name="correo" placeholder="Correo" required>
                <div class="campo-password"><input type="password" name="password" placeholder="Contraseña" required><button type="button" class="ver-password" aria-label="Mostrar contraseña">👁️</button></div>
                <select name="rol" required><option value="usuario">Usuario</option><option value="admin">Administrador</option></select>
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
        <a class="contacto-destino" href="<?= htmlspecialchars($enlaceGmailAdministrador, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
            <span class="contacto-destino-icono" aria-hidden="true">✉</span>
            <span><strong>Equipo Always Beautiful</strong><small>Recepción directa en administración</small></span>
        </a>
    </section>

.formulario-contacto {
    background: #fbf7f3;
    border: 1px solid #d7b8ad;
    padding: 36px;
    border-radius: 12px;
    box-shadow: 0 12px 30px rgba(58, 39, 34, 0.12);
}

.formulario-contacto h2 {
    text-align: center;
    color: #481b18;
    margin-bottom: 30px;
}

.usuario-info {
    background: #f1e3da;
    border-left: 4px solid #b87552;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 15px;
    color: #403834;
    font-size: 0.9em;
}

.aviso-registro {
    background: #f1e3da;
    color: #403834;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    border-left: 4px solid #b87552;
    text-align: center;
}

.aviso-registro a {
    color: #743629;
    font-weight: bold;
    text-decoration: none;
    cursor: pointer;
}

.aviso-registro a:hover {
    text-decoration: underline;
}

.formulario-contacto input,
.formulario-contacto textarea {
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    border-radius: 10px;
    border: 1px solid #cdbfb3;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
    transition: border-color 0.3s;
}

.formulario-contacto input:focus,
.formulario-contacto textarea:focus {
    outline: none;
    border-color: #a96d4b;
    box-shadow: 0 0 0 3px rgba(184, 117, 82, 0.16);
}

.formulario-contacto input:disabled {
    background: #f0f0f0;
    cursor: not-allowed;
}

.formulario-contacto button {
    width: 100%;
    padding: 12px;
    background: #481b18;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    font-size: 1em;
    transition: all 0.3s;
    margin-top: 10px;
}

.formulario-contacto button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(72, 27, 24, 0.2);
}

.formulario-contacto button:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.contacto-acceso {
    background: #fbf7f3;
    border: 1px solid #d7b8ad;
    border-radius: 12px;
    box-shadow: 0 12px 30px rgba(58, 39, 34, 0.12);
    margin: 0 auto;
    max-width: 600px;
    padding: 36px;
    text-align: center;
}

.contacto-acceso h2 {
    color: #481b18;
    margin-top: 0;
}

.contacto-acceso p {
    color: #5b3c35;
    line-height: 1.6;
}

.contacto-acceso button {
    background: #481b18;
    border: 0;
    border-radius: 6px;
    color: #fff;
    cursor: pointer;
    font-weight: 700;
    padding: 12px 18px;
}

@media (max-width: 600px) {
    .formulario-contacto, .contacto-acceso { padding: 24px 18px; }
}
</style>

<link rel="stylesheet" href="styles.css">

<div class="formulario-container">

    <?php if ($notificacion_contacto): ?>
        <?php
            $notificacionTipo = is_array($notificacion_contacto) ? ($notificacion_contacto['tipo'] ?? 'error') : 'error';
            $notificacionTexto = is_array($notificacion_contacto) ? ($notificacion_contacto['mensaje'] ?? '') : $notificacion_contacto;
        ?>
        <div class="contacto-notificacion <?= $notificacionTipo === 'exito' ? 'es-exito' : 'es-error' ?>" role="<?= $notificacionTipo === 'exito' ? 'status' : 'alert' ?>">
            <?= htmlspecialchars((string) $notificacionTexto, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($usuario_logueado): ?>
        <div class="usuario-info">
            Sesión iniciada como <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>
        </div>
        <form action="Procesar_formulario.php" method="POST" enctype="multipart/form-data" class="formulario formulario-contacto">
            <h2>Contáctanos</h2>
            <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($nombre_usuario); ?>">
            <input type="hidden" name="correo" value="<?php echo htmlspecialchars($correo_usuario); ?>">
            <input type="text" name="asunto" placeholder="Asunto" maxlength="200" required>
            <div class="mensaje-contenedor">
                <textarea name="mensaje" placeholder="Escribe tu mensaje..." maxlength="5000" required></textarea>
                <label class="foto-mensaje" title="Adjuntar una imagen">
                    <span>Adjuntar imagen</span>
                    <input type="file" name="foto_mensaje" accept="image/jpeg,image/png,image/webp">
                </label>
            </div>
            <button type="submit">Enviar mensaje</button>
        </form>
    <?php else: ?>
        <section class="contacto-acceso">
            <h2>Inicia sesión para contactarnos</h2>
            <p>El formulario de contacto está disponible para usuarios que iniciaron sesión.</p>
            <button type="button" onclick="abrirLoginContacto()">Iniciar sesión</button>
        </section>
    <?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
<script>
const campoFotoMensaje = document.querySelector('input[name="foto_mensaje"]');
const vistaFotoMensaje = document.getElementById('foto-mensaje-vista');
const miniaturaFotoMensaje = document.getElementById('foto-mensaje-miniatura');
const nombreFotoMensaje = document.getElementById('foto-mensaje-nombre');
const quitarFotoMensaje = document.getElementById('foto-mensaje-quitar');
let urlVistaFotoMensaje = null;

<script>
function abrirLoginContacto() {
    const modal = document.getElementById('autenticacion');
    if (!modal) return;
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    document.querySelectorAll('[data-auth-tab]').forEach(tab => tab.classList.toggle('activo', tab.dataset.authTab === 'login'));
    document.querySelectorAll('[data-auth-panel]').forEach(panel => panel.classList.toggle('oculto', panel.dataset.authPanel !== 'login'));
}
</script>

</body>
</html>