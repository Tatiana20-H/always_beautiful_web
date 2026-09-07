<?php 
session_start();
include("conexion.php");

$mensaje_exito = "";
$mensaje_error = "";

// Procesar envío de formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enviar_mensaje'])) {
    $nombre = trim(mysqli_real_escape_string($conexion, $_POST['nombre'] ?? ''));
    $correo = trim(mysqli_real_escape_string($conexion, $_POST['correo'] ?? ''));
    $asunto = trim(mysqli_real_escape_string($conexion, $_POST['asunto'] ?? ''));
    $mensaje = trim(mysqli_real_escape_string($conexion, $_POST['mensaje'] ?? ''));
    $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : NULL;

    // Validar campos
    if (empty($nombre) || empty($correo) || empty($asunto) || empty($mensaje)) {
        $mensaje_error = "⚠️ Por favor completa todos los campos.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = "⚠️ Por favor ingresa un correo válido.";
    } else {
        // Insertar en base de datos
        $sql = "INSERT INTO mensajes_contacto (usuario_id, nombre, correo, asunto, mensaje, estado) 
                VALUES (?, ?, ?, ?, ?, 'no_leido')";
        
        if ($stmt = mysqli_prepare($conexion, $sql)) {
            mysqli_stmt_bind_param($stmt, "issss", $usuario_id, $nombre, $correo, $asunto, $mensaje);
            
            if (mysqli_stmt_execute($stmt)) {
                $mensaje_exito = "✅ ¡Mensaje enviado con éxito! El administrador se pondrá en contacto pronto.";
                // Limpiar formulario
                $_POST = array();
                
                // Intentar enviar email (si está configurado)
                $email_asunto = "Hemos recibido tu mensaje - Always Beautiful";
                $email_cuerpo = "Hola $nombre,\n\n¡Gracias por contactarnos! Hemos recibido tu mensaje y nos pondremos en contacto pronto.\n\nAsunto: $asunto\n\nSaludos,\nEquipo Always Beautiful";
                $email_headers = "From: AdminAlways@gmail.com\r\n";
                $email_headers .= "Reply-To: AdminAlways@gmail.com\r\n";
                $email_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                
                @mail($correo, $email_asunto, $email_cuerpo, $email_headers);
            } else {
                $mensaje_error = "❌ Error al enviar el mensaje. Intenta de nuevo.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $mensaje_error = "❌ Error en la base de datos: " . mysqli_error($conexion);
        }
    }
}

// Obtener datos del usuario si está logueado
$nombre_usuario = "";
$correo_usuario = "";
$usuario_logueado = false;

if (isset($_SESSION['usuario_id'])) {
    $usuario_logueado = true;
    $resultado = mysqli_query($conexion, "SELECT nombre, correo FROM usuarios WHERE id=" . intval($_SESSION['usuario_id']));
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $usuario = mysqli_fetch_assoc($resultado);
        $nombre_usuario = $usuario['nombre'];
        $correo_usuario = $usuario['correo'];
    }
}

// NO incluir header.php aquí si no estamos logueados para evitar el modal
// Incluir header.php SOLO si está logueado
if ($usuario_logueado) {
    include("header.php");
} else {
    // Para usuarios no logueados, crear un header simplificado sin el modal que interfiera
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Contacto - Always Beautiful</title>
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
.contacto-container{
    max-width: 600px;
    margin: 50px auto;
    background: #ffe6f0;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.contacto-container h2{
    text-align: center;
    color: #d63384;
    margin-bottom: 20px;
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

.contacto-container input,
.contacto-container textarea{
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    border-radius: 10px;
    border: 2px solid #ddd;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
    transition: border-color 0.3s;
}

.contacto-container input:focus,
.contacto-container textarea:focus {
    outline: none;
    border-color: #d63384;
    box-shadow: 0 0 5px rgba(214, 51, 132, 0.3);
}

.contacto-container input:disabled {
    background: #f0f0f0;
    cursor: not-allowed;
}

.contacto-container button{
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
}

.contacto-container button:hover{
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(214, 51, 132, 0.3);
}

.contacto-container button:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.mensaje-exito {
    background: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    border-left: 4px solid #28a745;
}

.mensaje-error {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    border-left: 4px solid #dc3545;
}

.usuario-info {
    background: rgba(214, 51, 132, 0.1);
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 15px;
    color: #555;
    font-size: 0.9em;
}
</style>

<div class="contacto-container">
    <h2>💌 Contáctanos</h2>

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

    <?php if (!empty($mensaje_exito)): ?>
        <div class="mensaje-exito"><?php echo $mensaje_exito; ?></div>
    <?php endif; ?>

    <?php if (!empty($mensaje_error)): ?>
        <div class="mensaje-error"><?php echo $mensaje_error; ?></div>
    <?php endif; ?>

    <form action="contacto.php" method="POST" <?php echo !$usuario_logueado ? 'style="opacity: 0.6; pointer-events: none;"' : ''; ?>>
        <input type="text" name="nombre" placeholder="Tu nombre" 
               value="<?php echo !empty($nombre_usuario) ? htmlspecialchars($nombre_usuario) : htmlspecialchars($_POST['nombre'] ?? ''); ?>" 
               <?php echo $usuario_logueado ? 'readonly' : ''; ?> required>

        <input type="email" name="correo" placeholder="Tu correo" 
               value="<?php echo !empty($correo_usuario) ? htmlspecialchars($correo_usuario) : htmlspecialchars($_POST['correo'] ?? ''); ?>" 
               <?php echo $usuario_logueado ? 'readonly' : ''; ?> required>

        <input type="text" name="asunto" placeholder="Asunto del mensaje" 
               value="<?php echo htmlspecialchars($_POST['asunto'] ?? ''); ?>" 
               <?php echo !$usuario_logueado ? 'disabled' : ''; ?> required>

        <textarea name="mensaje" rows="5" placeholder="Escribe tu mensaje..." 
                  <?php echo !$usuario_logueado ? 'disabled' : ''; ?> required><?php echo htmlspecialchars($_POST['mensaje'] ?? ''); ?></textarea>

        <button type="submit" name="enviar_mensaje" <?php echo !$usuario_logueado ? 'disabled' : ''; ?>>
            <?php echo $usuario_logueado ? 'Enviar mensaje' : 'Inicia sesión para enviar'; ?>
        </button>
    </form>
</div>

</body>
</html>