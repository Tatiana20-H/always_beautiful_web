<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir conexión para contar mensajes sin leer
include_once(__DIR__ . '/../conexion.php');
$no_leidos_mensajes = 0;
if (isset($conexion)) {
    $resultado = mysqli_query($conexion, "SELECT COUNT(*) as total FROM mensajes_contacto WHERE estado='no_leido'");
    if ($resultado) {
        $no_leidos_mensajes = mysqli_fetch_assoc($resultado)['total'];
    }
}
?>

<link rel="stylesheet" href="../styles.css">

<style>
.header-admin {
    background: linear-gradient(135deg, #d63384 0%, #e85ba8 100%);
    border-bottom: 3px solid #c5297a;
}

.header-admin .barra-principal {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 25px;
}

.header-admin .marca {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: white;
    font-weight: bold;
    font-size: 1.3em;
    transition: opacity 0.3s;
}

.header-admin .marca:hover {
    opacity: 0.8;
}

.header-admin .menu {
    display: flex;
    gap: 0;
    align-items: center;
    flex: 1;
    margin: 0 20px;
}

.admin-etiqueta {
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8em;
    font-weight: bold;
    margin-right: 15px;
}

.header-admin .menu a {
    display: flex;
    align-items: center;
    gap: 8px;
    color: white;
    text-decoration: none;
    padding: 10px 15px;
    border-radius: 8px;
    transition: all 0.3s;
    font-weight: 500;
    white-space: nowrap;
    position: relative;
}

.header-admin .menu a:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-2px);
}

.header-admin .menu-icon {
    width: 20px;
    height: 20px;
    filter: brightness(0) invert(1);
}

.badge-mensajes {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #ff6b6b;
    color: white;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75em;
    font-weight: bold;
    border: 2px solid white;
}

.header-admin .perfil-menu {
    position: relative;
    display: flex;
    align-items: center;
}

.header-admin .perfil-trigger {
    background: rgba(255,255,255,0.2);
    border: 2px solid white;
    border-radius: 50%;
    width: 45px;
    height: 45px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    transition: all 0.3s;
}

.header-admin .perfil-trigger:hover {
    background: rgba(255,255,255,0.3);
    transform: scale(1.05);
}

.header-admin .perfil-trigger img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.header-admin .avatar-generico {
    color: white;
    font-size: 1.5em;
}

.header-admin .perfil-submenu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 10px;
    padding: 15px;
    min-width: 200px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s;
    margin-top: 10px;
    z-index: 1000;
}

.header-admin .perfil-menu.active .perfil-submenu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.header-admin .perfil-submenu strong {
    display: block;
    color: #d63384;
    margin-bottom: 10px;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 10px;
}

.header-admin .perfil-submenu a {
    display: block;
    color: #555;
    text-decoration: none;
    padding: 10px 0;
    transition: color 0.3s;
    border-radius: 0;
    background: none !important;
    transform: none !important;
    gap: 0;
    margin: 0;
    padding: 8px 0;
}

.header-admin .perfil-submenu a:hover {
    color: #d63384;
    font-weight: bold;
}

@media (max-width: 768px) {
    .header-admin .menu {
        flex-wrap: wrap;
        gap: 5px;
    }

    .header-admin .menu a {
        padding: 8px 10px;
        font-size: 0.9em;
    }

    .admin-etiqueta {
        display: none;
    }
}
</style>

<header class="header header-admin">
    <div class="barra-principal">
        <a href="index.php" class="marca" aria-label="Always Beautiful, panel administrador">
            <img src="../IMG/logo.png" alt="Always Beautiful" class="logo-header">
            <span class="nombre-marca">Always Beautiful</span>
        </a>
    <nav class="menu" aria-label="Navegación del administrador">
        <span class="admin-etiqueta">🛡️ Panel administrador</span>
        <a href="index.php" title="Ir al inicio"><img src="../IMG/Inicio-logo.png" alt="" class="menu-icon">Inicio</a>
        <a href="productos.php" title="Gestionar productos"><img src="../IMG/productos-logo.png" alt="" class="menu-icon">Productos</a>
        <a href="usuarios.php" title="Gestionar usuarios"><img src="../IMG/nosotros-logo.png" alt="" class="menu-icon">Usuarios</a>
        <a href="mensajes.php" title="Ver mensajes de contacto" style="position: relative;">
            <img src="../IMG/contacto-logo.png" alt="" class="menu-icon">Mensajes
            <?php if ($no_leidos_mensajes > 0): ?>
                <span class="badge-mensajes"><?php echo $no_leidos_mensajes; ?></span>
            <?php endif; ?>
        </a>
        <a href="ventas.php" title="Ver ventas"><img src="../IMG/blog-logo.png" alt="" class="menu-icon">Ventas</a>
        <a href="reportes.php" title="Ver reportes"><img src="../IMG/politicas-logo.png" alt="" class="menu-icon">Reportes</a>
        <a href="logout.php" title="Cerrar sesión" style="background: rgba(255,255,255,0.1); margin-left: auto;">🚪 Cerrar</a>
    </nav>

    <div class="perfil-menu">
        <button type="button" class="perfil-trigger" aria-label="Abrir menú de administrador" aria-expanded="false">
            <?php if (!empty($_SESSION['foto_perfil'])): ?>
                <img src="../<?= htmlspecialchars($_SESSION['foto_perfil']) ?>" alt="Foto de perfil">
            <?php else: ?>
                <span class="avatar-generico" aria-hidden="true">👤</span>
            <?php endif; ?>
        </button>
        <div class="perfil-submenu">
            <strong><?= htmlspecialchars($_SESSION['usuario'] ?? $_SESSION['nombre'] ?? 'Administrador') ?></strong>
            <a href="../perfil.php">👤 Mi perfil</a>
            <a href="logout.php">🚪 Cerrar sesión</a>
        </div>
    </div>
    </div>
</header>

<script>
const menuPerfilAdmin = document.querySelector(".header-admin .perfil-menu");
if (menuPerfilAdmin) {
    menuPerfilAdmin.querySelector(".perfil-trigger").addEventListener("click", function (event) {
        event.stopPropagation();
        const abierto = menuPerfilAdmin.classList.toggle("active");
        this.setAttribute("aria-expanded", abierto ? "true" : "false");
    });
    document.addEventListener("click", function () {
        menuPerfilAdmin.classList.remove("active");
        menuPerfilAdmin.querySelector(".perfil-trigger").setAttribute("aria-expanded", "false");
    });
}
</script>