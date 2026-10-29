<?php include("Seguridad.php"); ?>
<?php include("../conexion.php"); ?>
<?php $conexion = $GLOBALS['conexion']; ?>
<?php include("header-admin.php"); ?>

<style>
    .admin-container {
        background: linear-gradient(145deg, #fff8f8 0%, #f7e9ef 52%, #fff5f0 100%);
        box-sizing: border-box;
        min-height: calc(100vh - 120px);
        padding: 38px clamp(16px, 3vw, 42px) 56px;
    }

    .admin-encabezado {
        align-items: end;
        display: flex;
        justify-content: space-between;
        margin: 0 auto 24px;
        max-width: 1420px;
    }

    .admin-kicker {
        color: #ad4e70;
        font-size: 12px;
        font-weight: 700;
        margin: 0 0 8px;
        text-transform: uppercase;
    }

    .admin-container h2 {
        color: #71354e;
        font: 700 30px Georgia, "Times New Roman", serif;
        margin: 0;
    }

    .admin-fecha {
        color: #806d77;
        font-size: 13px;
        margin: 0 0 3px;
    }

    .tarjetas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 185px), 1fr));
        gap: 16px;
        margin: 0 auto 40px;
        max-width: 1420px;
    }

    .tarjeta {
        --tarjeta-acento: #c34d78;
        animation: tarjeta-entrada .42s both;
        background: rgba(255,255,255,.94);
        border: 1px solid #efd5df;
        border-radius: 9px;
        box-shadow: 0 7px 20px rgba(125,57,86,.07);
        color: inherit;
        display: block;
        min-height: 142px;
        padding: 19px 20px 16px;
        position: relative;
        text-decoration: none;
        position: relative;
        overflow: hidden;
        transition: box-shadow .2s ease, border-color .2s ease, transform .2s ease;
    }

    .tarjeta:nth-child(2) { animation-delay: .05s; }
    .tarjeta:nth-child(3) { animation-delay: .1s; }
    .tarjeta:nth-child(4) { animation-delay: .15s; }
    .tarjeta:nth-child(5) { animation-delay: .2s; }
    .tarjeta:nth-child(6) { animation-delay: .25s; }

    .tarjeta:hover {
        border-color: color-mix(in srgb, var(--tarjeta-acento) 45%, white);
        box-shadow: 0 14px 28px rgba(125,57,86,.14);
        transform: translateY(-4px);
    }

    .tarjeta:focus-visible {
        outline: 3px solid color-mix(in srgb, var(--tarjeta-acento) 45%, white);
        outline-offset: 3px;
    }

    .tarjeta::before {
        background: var(--tarjeta-acento);
        content: "";
        height: 4px;
        inset: 0 0 auto;
        position: absolute;
    }

    .tarjeta-contenido {
        position: relative;
    }

    .tarjeta-icono {
        align-items: center;
        background: color-mix(in srgb, var(--tarjeta-acento) 12%, white);
        border-radius: 7px;
        color: var(--tarjeta-acento);
        display: flex;
        font-size: 19px;
        font-weight: 700;
        height: 36px;
        justify-content: center;
        margin-bottom: 14px;
        width: 36px;
    }

    .tarjeta h3 {
        color: #55444d;
        font-size: 14px;
        font-weight: 650;
        margin: 0;
    }

    .tarjeta-numero {
        color: var(--tarjeta-acento);
        font: 700 34px Georgia, "Times New Roman", serif;
        line-height: 1.1;
        margin: 9px 0 5px;
    }

    .tarjeta-subtitulo {
        color: #81727a;
        font-size: 12px;
        margin: 0;
    }

    .tarjeta::after { color: var(--tarjeta-acento); content: "↗"; font-size: 17px; font-weight: 700; position: absolute; right: 18px; top: 17px; transition: transform .2s ease; }
    .tarjeta:hover::after { transform: translate(2px, -2px); }
    .tarjeta-usuarios { --tarjeta-acento: #c34d78; }
    .tarjeta-productos { --tarjeta-acento: #4b8b67; }
    .tarjeta-sesiones { --tarjeta-acento: #7970a8; }
    .tarjeta-mensajes { --tarjeta-acento: #d56d50; }
    .tarjeta-ventas { --tarjeta-acento: #bf5970; }
    .tarjeta-actividades { --tarjeta-acento: #438b8a; }

    @keyframes tarjeta-entrada {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .badge-alerta {
        display: inline-block;
        background: #be425c;
        color: white;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 0.8em;
        font-weight: bold;
        margin-top: 10px;
    }

    .seccion-resumen {
        background: rgba(255,255,255,.94);
        border: 1px solid #efd5df;
        padding: 26px;
        border-radius: 9px;
        box-shadow: 0 8px 22px rgba(125,57,86,.08);
        margin-top: 30px;
    }

    .seccion-resumen h3 {
        color: #8b3d60;
        margin-top: 0;
        border-bottom: 2px solid #e7c4d2;
        padding-bottom: 15px;
    }

    .lista-actividades {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .lista-actividades li {
        padding: 15px;
        border-bottom: 1px solid #f0e2e8;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .lista-actividades li:last-child {
        border-bottom: none;
    }

    .lista-actividades li:hover {
        background: #fff1f5;
    }

    @media (max-width: 768px) {
        .admin-container {
            padding: 26px 14px 38px;
        }

        .tarjetas-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 11px;
        }

        .admin-container h2 {
            font-size: 25px;
        }

        .admin-encabezado { align-items: flex-start; flex-direction: column; gap: 10px; }
        .tarjeta { min-height: 130px; padding: 16px 14px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .tarjeta { animation: none; transition: none; }
        .tarjeta::after { transition: none; }
    }
</style>

<div class="admin-container">
    <div class="admin-encabezado">
        <div>
            <p class="admin-kicker">Resumen general</p>
            <h2>Panel de Administración</h2>
        </div>
        <p class="admin-fecha"><?= htmlspecialchars(date('d/m/Y'), ENT_QUOTES, 'UTF-8') ?></p>
    </div>

    <?php
    $usuarios = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM usuarios"));
    $productos = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM productos"));
    $actividades = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM historial_actividades"));
    $mensajes_no_leidos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM mensajes_contacto WHERE estado='no_leido'"))['total'];
    $pedidos_pendientes = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM pedidos WHERE estado IN ('pendiente', 'procesando_pago')"))['total'];
    $sesionPath = session_save_path() ?: sys_get_temp_dir();
    $sesiones_activas = 0;
    if (is_dir($sesionPath)) {
        $sessFiles = scandir($sesionPath);
        foreach ($sessFiles as $archivo) {
            if (strpos($archivo, 'sess_') === 0) {
                $sesiones_activas++;
            }
        }
    }
    ?>

    <div class="tarjetas-grid">
        <a class="tarjeta tarjeta-usuarios" href="usuarios.php" aria-label="Ver usuarios registrados">
            <div class="tarjeta-contenido">
            <div class="tarjeta-icono" aria-hidden="true">U</div>
                <h3>Usuarios</h3>
                <div class="tarjeta-numero"><?php echo $usuarios; ?></div>
                <p class="tarjeta-subtitulo">Usuarios registrados</p>
            </div>
        </a>

        <a class="tarjeta tarjeta-productos" href="productos.php" aria-label="Gestionar productos">
            <div class="tarjeta-contenido">
            <div class="tarjeta-icono" aria-hidden="true">P</div>
                <h3>Productos</h3>
                <div class="tarjeta-numero"><?php echo $productos; ?></div>
                <p class="tarjeta-subtitulo">Total de productos</p>
            </div>
        </a>

        <div class="tarjeta tarjeta-sesiones">
            <div class="tarjeta-contenido">
            <div class="tarjeta-icono" aria-hidden="true">S</div>
                <h3>Sesiones</h3>
                <div class="tarjeta-numero"><?php echo $sesiones_activas; ?></div>
                <p class="tarjeta-subtitulo">Sesiones activas</p>
            </div>
        </div>

        <a class="tarjeta tarjeta-mensajes" href="mensajes.php" aria-label="Revisar mensajes sin leer">
            <div class="tarjeta-contenido">
            <div class="tarjeta-icono" aria-hidden="true">M</div>
                <h3>Mensajes</h3>
                <div class="tarjeta-numero"><?php echo $mensajes_no_leidos; ?></div>
                <p class="tarjeta-subtitulo">Mensajes sin leer</p>
                <?php if ($mensajes_no_leidos > 0): ?>
                    <span class="badge-alerta">Requiere atención</span>
                <?php endif; ?>
            </div>
        </a>

        <a class="tarjeta tarjeta-ventas" href="ventas.php" aria-label="Revisar pedidos pendientes">
            <div class="tarjeta-contenido">
            <div class="tarjeta-icono" aria-hidden="true">$</div>
                <h3>Pedidos Pendientes</h3>
                <div class="tarjeta-numero"><?php echo $pedidos_pendientes; ?></div>
                <p class="tarjeta-subtitulo">Esperando procesamiento</p>
            </div>
        </a>

        <a class="tarjeta tarjeta-actividades" href="reportes.php" aria-label="Ver actividades y reportes">
            <div class="tarjeta-contenido">
            <div class="tarjeta-icono" aria-hidden="true">A</div>
                <h3>Actividades</h3>
                <div class="tarjeta-numero"><?php echo $actividades; ?></div>
                <p class="tarjeta-subtitulo">Registros de actividad</p>
            </div>
        </a>
    </div>

</div>

</body>
</html>