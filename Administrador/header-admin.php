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
        background: linear-gradient(180deg, #f6dff4 0%, #c0b0c2 100%);
        border-radius: 32px;
        box-shadow: 0 8px 18px rgba(105, 76, 98, .14);
        color: #fff;
        margin: 26px 30px 0;
        overflow: visible;
        padding: 0 30px;
        position: relative;
        text-align: left;
    }

    .barra-principal {
        align-items: flex-start;
        display: flex;
        margin: 0 auto;
        max-width: 1380px;
        min-height: 128px;
        position: relative;
    }

    .marca {
        align-items: center;
        color: #fff;
        display: flex;
        flex: 0 0 auto;
        gap: 10px;
        position: relative;
        top: 12px;
        z-index: 2;
    }

    .logo-header {
        background: transparent;
        border-radius: 0;
        height: 84px;
        object-fit: contain;
        padding: 0;
        width: 112px;
    }

    .nombre-marca {
        display: none;
    }

    .header-admin .menu {
        align-items: center;
        display: flex;
        gap: 4px;
        justify-content: flex-start;
        left: 132px;
        margin: 0;
        min-width: max-content;
        position: absolute;
        top: 50px;
        z-index: 3;
    }

    .header-admin .menu a {
        align-items: center;
        background: transparent;
        border: 0;
        border-radius: 5px;
        color: #fff;
        display: flex;
        font-size: 20px;
        font-weight: 700;
        gap: 4px;
        letter-spacing: 0;
        padding: 12px 8px;
        text-decoration: none;
        text-transform: none;
        transition: all 0.3s;
        position: relative;
    }

    .header-admin .menu a:hover {
        background: rgba(255, 255, 255, .18);
        color: #fff;
    }

    /* Colores por módulo */
    .header-admin .menu a[href*="usuarios.php"] {
        color: #0056b3;
    }

    .header-admin .menu a[href*="productos.php"] {
        color: #28a745;
    }

    .header-admin .menu a[href*="mensajes.php"] {
        color: #ffc107;
    }

    .header-admin .menu a[href*="ventas.php"] {
        color: #dc3545;
    }

    .header-admin .menu a[href*="reportes.php"] {
        color: #17a2b8;
    }

    .menu-icon {
        border: 2px solid rgba(255, 255, 255, .85);
        border-radius: 50%;
        display: inline-block;
        height: 25px;
        margin-right: 2px;
        object-fit: cover;
        vertical-align: middle;
        width: 25px;
    }

    .badge-mensajes {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75em;
        font-weight: bold;
    }

    .header-admin .perfil-menu {
        position: absolute;
        right: 0;
        top: 14px;
        z-index: 4;
    }

    .header-admin .perfil-trigger {
        background: rgba(255,255,255,0.2);
        border: 2px solid white;
        border-radius: 50%;
        width: 48px;
        height: 48px;
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
        padding: 8px 0;
        transition: color 0.3s;
        border-radius: 0;
        background: none !important;
        transform: none !important;
    }

    .header-admin .perfil-submenu a:hover {
        color: #d63384;
        font-weight: bold;
    }

    @media (max-width: 768px) {
        .header-admin {
            margin: 20px 16px 0;
            padding: 0 16px;
        }

        .header-admin .menu {
            left: 112px;
            justify-content: flex-start;
            max-width: calc(100% - 112px);
            overflow-x: auto;
            top: 50px;
            transform: none;
            width: calc(100% - 112px);
        }

        .header-admin .menu a {
            font-size: 14px;
            padding: 10px 5px;
        }

        .menu-icon {
            height: 20px;
            width: 20px;
        }

        .header-admin .perfil-menu {
            right: 0;
            top: 10px;
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
            <a href="index.php" title="Ir al inicio">🏠 Inicio</a>
            <a href="usuarios.php" title="Gestionar usuarios">👤 Usuarios</a>
            <a href="productos.php" title="Gestionar productos">🛍️ Productos</a>
            <a href="mensajes.php" title="Ver mensajes de contacto" style="position: relative;">
                📧 Mensajes
                <?php if ($no_leidos_mensajes > 0): ?>
                    <span class="badge-mensajes"><?php echo $no_leidos_mensajes; ?></span>
                <?php endif; ?>
            </a>
            <a href="ventas.php" title="Ver ventas">💰 Ventas</a>
            <a href="reportes.php" title="Ver reportes">📊 Reportes</a>
            <a href="logout.php" title="Cerrar sesión">🚪 Cerrar</a>
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
