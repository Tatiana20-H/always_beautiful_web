<?php 
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];

// Obtener datos del usuario si está logueado
$usuario_logueado = false;
$nombre_usuario = "";
$correo_usuario = "";
$notificacion_contacto = $_SESSION['notificacion_contacto'] ?? null;
unset($_SESSION['notificacion_contacto']);

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
                    <a href="accesorios.php">Accesorios</a>
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
                    <a href="formulario.php">Formulario</a>
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
                <button type="button" class="perfil-trigger visitante-trigger" data-auth-mode="register" aria-label="Registrarse o iniciar sesión">
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
            <button type="button" class="auth-tab activo" data-auth-tab="register">Crear cuenta</button>
            <button type="button" class="auth-tab" data-auth-tab="login">Iniciar sesión</button>
        </div>

        <div class="auth-panel" data-auth-panel="register">
            <h2 id="titulo-auth">Regístrate para continuar</h2>
            <form action="guardar-usuario.php" method="POST">
                <input type="text" name="nombre" placeholder="Nombre completo" required>
                <input type="email" name="correo" placeholder="Correo" required>
                <input type="date" name="fecha_nacimiento" required>
                <div class="campo-password"><input type="password" name="password" placeholder="Contraseña" minlength="6" maxlength="72" required><button type="button" class="ver-password" aria-label="Mostrar contraseña">👁️</button></div>
                <div class="campo-password"><input type="password" name="password_confirm" placeholder="Confirmar contraseña" minlength="6" maxlength="72" required><button type="button" class="ver-password" aria-label="Mostrar confirmación">👁️</button></div>
                <button type="submit">Crear cuenta</button>
            </form>
        </div>

        <div class="auth-panel oculto" data-auth-panel="login">
            <h2>Inicia sesión para continuar</h2>
            <form action="login.php" method="POST">
                <input type="text" name="nombre" placeholder="Nombre" required>
                <input type="email" name="correo" placeholder="Correo" required>
                <div class="campo-password"><input type="password" name="password" placeholder="Contraseña" required><button type="button" class="ver-password" aria-label="Mostrar contraseña">👁️</button></div>
                <p><a href="recuperar.php">¿Has olvidado tu contraseña?</a></p>
                <button type="submit">Ingresar</button>
            </form>
        </div>
    </div>
</div>

<script>
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
</script>
    <?php
}
?>

<link rel="stylesheet" href="styles.css">

<style>
.formulario-container {
    max-width: 600px;
    margin: 50px auto;
}

.formulario-contacto {
    background: #ffe6f0;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.formulario-contacto h2 {
    text-align: center;
    color: #d63384;
    margin-bottom: 30px;
}

.usuario-info {
    background: rgba(214, 51, 132, 0.1);
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 15px;
    color: #555;
    font-size: 0.9em;
}

.aviso-registro {
    background: #fff3cd;
    color: #856404;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    border-left: 4px solid #ffc107;
    text-align: center;
}

.aviso-registro a {
    color: #d63384;
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
    border: 2px solid #ddd;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
    transition: border-color 0.3s;
}

.formulario-contacto input:focus,
.formulario-contacto textarea:focus {
    outline: none;
    border-color: #d63384;
    box-shadow: 0 0 5px rgba(214, 51, 132, 0.3);
}

.formulario-contacto input:disabled {
    background: #f0f0f0;
    cursor: not-allowed;
}

.formulario-contacto button {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #d63384 0%, #e85ba8 100%);
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: bold;
    font-size: 1em;
    transition: all 0.3s;
    margin-top: 10px;
}

.formulario-contacto button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(214, 51, 132, 0.3);
}

.formulario-contacto button:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.formulario-contacto form[style*="opacity: 0.6"] {
    pointer-events: none;
}
</style>

<link rel="stylesheet" href="styles.css">

<div class="formulario-container">

    <?php if ($notificacion_contacto): ?>
        <div class="notificacion-contacto" role="alert">
            <?php echo htmlspecialchars($notificacion_contacto); ?>
        </div>
    <?php endif; ?>

    <?php if (!$usuario_logueado): ?>
        <div class="aviso-registro">
            <strong>⚠️ Debes iniciar sesión</strong><br>
            Para enviar un mensaje, primero debes <a onclick="document.getElementById('autenticacion').style.display='flex'">registrarte o iniciar sesión</a>.
        </div>
    <?php else: ?>
        <div class="usuario-info">
            ✅ Conectado como: <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>
        </div>
    <?php endif; ?>

    <form action="Procesar_formulario.php" method="POST" enctype="multipart/form-data" class="formulario formulario-contacto" <?php echo !$usuario_logueado ? 'style="opacity: 0.6; pointer-events: none;"' : ''; ?>>

        <h2>Contáctanos 💌</h2>

        <?php if ($usuario_logueado): ?>
            <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($nombre_usuario); ?>">
            <input type="hidden" name="correo" value="<?php echo htmlspecialchars($correo_usuario); ?>">
        <?php endif; ?>

        <input type="text" name="asunto" placeholder="Asunto" 
               <?php echo !$usuario_logueado ? 'disabled' : ''; ?> required>

        <div class="mensaje-contenedor">
            <textarea name="mensaje" placeholder="Escribe tu mensaje..." 
                      <?php echo !$usuario_logueado ? 'disabled' : ''; ?> required></textarea>
            <label class="foto-mensaje" title="Adjuntar una foto">
                <span aria-hidden="true">📷</span>
                <span>Subir foto</span>
                <input type="file" name="foto_mensaje" accept="image/jpeg,image/png,image/webp">
            </label>
        </div>

        <button type="submit" <?php echo !$usuario_logueado ? 'disabled' : ''; ?>>
            <?php echo $usuario_logueado ? 'Enviar mensaje' : 'Inicia sesión para enviar'; ?>
        </button>

    </form>

</div>

</body>
</html>