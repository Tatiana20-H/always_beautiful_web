<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Always Beautiful</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<?php
$notificacion = $_SESSION['notificacion'] ?? null;
if ($notificacion) {
    unset($_SESSION['notificacion']);
}
?>

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
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <span class="saludo">Hola, <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?></span>
            <div class="perfil-menu">
                <button type="button" class="perfil-trigger" aria-label="Abrir menú de usuario" aria-expanded="false">
                    <?php if (!empty($_SESSION['foto_perfil'])): ?>
                        <img src="<?= htmlspecialchars($_SESSION['foto_perfil']) ?>" alt="Foto de perfil">
                    <?php else: ?>
                        <span class="avatar-generico" aria-hidden="true">&#128100;</span>
                    <?php endif; ?>
                </button>
                <div class="perfil-submenu">
                    <strong><?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?></strong>
                    <a href="perfil.php">Mi cuenta</a>
                    <a href="logout.php">Cerrar sesión</a>
                </div>
            </div>
        <?php else: ?>
            <button type="button" class="perfil-trigger visitante-trigger" data-auth-mode="register" aria-label="Registrarse o iniciar sesión">
                <span class="avatar-generico" aria-hidden="true">&#128100;</span>
            </button>
        <?php endif; ?>
            </div>

            <?php
$totalCarrito = 0;
if(isset($_SESSION['carrito'])){
    foreach($_SESSION['carrito'] as $p){
        $totalCarrito += $p['cantidad'];
    }
}
?>

            <?php if (isset($_SESSION['usuario_id'])): ?>
                <a href="ver_carrito.php" class="carrito" aria-label="Carrito de compras">
                    <span aria-hidden="true">&#128722;</span><span class="contador-carrito"><?= $totalCarrito ?></span>
                </a>
            <?php endif; ?>
        </div>
    </div>

</header>

<?php if (!isset($_SESSION['usuario_id']) || $notificacion): ?>
<div class="modal-auth" id="autenticacion" aria-hidden="true">
    <div class="modal-auth-contenido" role="dialog" aria-modal="true" aria-labelledby="titulo-auth">
        <button type="button" class="modal-cerrar" aria-label="Cerrar">&times;</button>
        <img src="IMG/logo.png" alt="Always Beautiful" class="modal-logo">
        <div class="auth-tabs">
            <button type="button" class="auth-tab activo" data-auth-tab="register">Crear cuenta</button>
            <button type="button" class="auth-tab" data-auth-tab="login">Iniciar sesión</button>
        </div>

        <?php if ($notificacion): ?>
            <div class="notificacion-modal <?= htmlspecialchars($notificacion['tipo']) ?>" id="notificacion-auth" role="alert">
                <?= htmlspecialchars($notificacion['mensaje']) ?>
            </div>
        <?php endif; ?>

        <div class="auth-panel" data-auth-panel="register">
            <h2 id="titulo-auth">Regístrate para continuar</h2>
            <form action="guardar-usuario.php" method="POST">
                <input type="text" name="nombre" placeholder="Nombre completo" required>
                <input type="email" name="correo" placeholder="Correo" required>
                <input type="date" name="fecha_nacimiento" required>
                <select name="genero" required>
                    <option value="">Selecciona tu género</option>
                    <option value="mujer">Mujer</option>
                    <option value="hombre">Hombre</option>
                </select>
                <div class="campo-password"><input type="password" name="password" placeholder="Contraseña" minlength="6" maxlength="72" required><button type="button" class="ver-password" aria-label="Mostrar contraseña">&#128065;</button></div>
                <div class="campo-password"><input type="password" name="password_confirm" placeholder="Confirmar contraseña" minlength="6" maxlength="72" required><button type="button" class="ver-password" aria-label="Mostrar confirmación">&#128065;</button></div>
                <button type="submit">Crear cuenta</button>
            </form>
        </div>

        <div class="auth-panel oculto" data-auth-panel="login">
            <h2>Inicia sesión para continuar</h2>
            <form action="login.php" method="POST">
                <input type="text" name="nombre" placeholder="Nombre" required>
                <input type="email" name="correo" placeholder="Correo" required>
                <div class="campo-password"><input type="password" name="password" placeholder="Contraseña" required><button type="button" class="ver-password" aria-label="Mostrar contraseña">&#128065;</button></div>
                <p><a href="recuperar.php">¿Has olvidado tu contraseña?</a></p>
                <button type="submit">Ingresar</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
let items = document.querySelectorAll(".dropdown");

items.forEach(item => {
    item.addEventListener("click", function (e) {

        e.stopPropagation();

        items.forEach(i => {
            if (i !== item) {
                i.classList.remove("active");
                const trigger = i.querySelector(".item-trigger");
                if (trigger) trigger.setAttribute("aria-expanded", "false");
            }
        });

        item.classList.toggle("active");
        const trigger = item.querySelector(".item-trigger");
        if (trigger) trigger.setAttribute("aria-expanded", item.classList.contains("active") ? "true" : "false");
    });
});

document.addEventListener("click", function () {
    items.forEach(i => {
        i.classList.remove("active");
        const trigger = i.querySelector(".item-trigger");
        if (trigger) trigger.setAttribute("aria-expanded", "false");
    });
});

const perfilMenus = document.querySelectorAll(".perfil-menu");
perfilMenus.forEach(menu => {
    menu.addEventListener("click", function (event) {
        event.stopPropagation();
        const abierto = this.classList.toggle("active");
        this.querySelector(".perfil-trigger").setAttribute("aria-expanded", abierto ? "true" : "false");
    });
});

document.addEventListener("click", function () {
    perfilMenus.forEach(menu => {
        menu.classList.remove("active");
        menu.querySelector(".perfil-trigger").setAttribute("aria-expanded", "false");
    });
});

const modalAuth = document.getElementById("autenticacion");
const authTabs = document.querySelectorAll("[data-auth-tab]");
const authPanels = document.querySelectorAll("[data-auth-panel]");
const notificacionAuth = document.getElementById("notificacion-auth");

document.querySelectorAll('.ver-password').forEach(function (boton) {
    boton.addEventListener('click', function () {
        const campo = this.parentElement.querySelector('input');
        campo.type = campo.type === 'password' ? 'text' : 'password';
    });
});

function abrirAutenticacion(modo) {
    if (!modalAuth) return;
    modalAuth.classList.add("visible");
    modalAuth.setAttribute("aria-hidden", "false");
    authTabs.forEach(tab => tab.classList.toggle("activo", tab.dataset.authTab === modo));
    authPanels.forEach(panel => panel.classList.toggle("oculto", panel.dataset.authPanel !== modo));
}

<?php if ($notificacion): ?>
abrirAutenticacion("<?= htmlspecialchars($notificacion['modo'] ?? 'register') ?>");
if (notificacionAuth) {
    setTimeout(() => {
        notificacionAuth.classList.add("oculto");
        if (modalAuth) {
            modalAuth.classList.remove("visible");
            modalAuth.setAttribute("aria-hidden", "true");
        }
    }, 2000);
}
<?php endif; ?>

document.querySelectorAll("[data-auth-mode]").forEach(trigger => {
    trigger.addEventListener("click", function (event) {
        event.preventDefault();
        abrirAutenticacion(this.dataset.authMode);
    });
});

authTabs.forEach(tab => tab.addEventListener("click", () => abrirAutenticacion(tab.dataset.authTab)));

if (modalAuth) {
    modalAuth.querySelector(".modal-cerrar").addEventListener("click", () => {
        modalAuth.classList.remove("visible");
        modalAuth.setAttribute("aria-hidden", "true");
    });
    modalAuth.addEventListener("click", event => {
        if (event.target === modalAuth) modalAuth.querySelector(".modal-cerrar").click();
    });
}

document.addEventListener("submit", function (event) {
    <?php if (!isset($_SESSION['usuario_id'])): ?>
    if (event.target.matches("form[action='carrito.php'], form.formulario-contacto")) {
        event.preventDefault();
        abrirAutenticacion("register");
    }
    <?php endif; ?>
});

document.addEventListener("focusin", function (event) {
    <?php if (!isset($_SESSION['usuario_id'])): ?>
    if (event.target.closest("form.formulario-contacto")) {
        event.target.blur();
        abrirAutenticacion("register");
    }
    <?php endif; ?>
});

document.addEventListener("click", function (event) {
    <?php if (!isset($_SESSION['usuario_id'])): ?>
    const card = event.target.closest(".card");
    if (card && !event.target.closest("form") && !event.target.closest("a[href*='producto.php']")) {
        abrirAutenticacion("register");
    }
    <?php endif; ?>
});
</script>

