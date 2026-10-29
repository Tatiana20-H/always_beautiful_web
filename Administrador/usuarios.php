<?php include("Seguridad.php"); ?>
<?php include("../conexion.php"); ?>
<?php $conexion = $GLOBALS['conexion']; ?>
<?php include("header-admin.php"); ?>

<style>
    .usuarios-container {
        background: linear-gradient(145deg, #fff8f8 0%, #f7e9ef 52%, #fff5f0 100%);
        box-sizing: border-box;
        min-height: calc(100vh - 120px);
        padding: 38px clamp(16px, 3vw, 42px) 56px;
    }

    .usuarios-container h2 {
        color: #71354e;
        font: 700 30px Georgia, "Times New Roman", serif;
        margin: 0 0 22px;
        text-align: center;
    }

    .usuarios-container > h2:not(:first-child) {
        margin-top: 36px;
    }

    .tabla-usuarios {
        background: rgba(255, 255, 255, .94);
        border: 1px solid #efd5df;
        border-radius: 9px;
        box-shadow: 0 10px 28px rgba(125, 57, 86, .09);
        margin: 0 auto 34px;
        max-width: 1420px;
        overflow-x: auto;
        overscroll-behavior-inline: contain;
    }

    .tabla-usuarios table {
        min-width: 720px;
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .tabla-usuarios th {
        background: linear-gradient(100deg, #793653 0%, #a8476d 56%, #c65f70 100%);
        color: #fff;
        font-size: 13px;
        letter-spacing: .02em;
        padding: 15px 18px;
        text-align: left;
        border: none;
        white-space: nowrap;
    }

    .tabla-usuarios td {
        border-bottom: 1px solid #f1e3e8;
        color: #493b42;
        font-size: 14px;
        padding: 15px 18px;
        white-space: nowrap;
    }

    .tabla-usuarios tbody tr {
        transition: background-color .16s ease;
    }

    .tabla-usuarios tbody tr:hover {
        background: #fff1f5;
    }

    .tabla-usuarios tr:last-child td {
        border-bottom: none;
    }

    .badge-rol, .badge-estado {
        align-items: center;
        border-radius: 999px;
        display: inline-flex;
        font-size: 12px;
        font-weight: 700;
        gap: 7px;
        line-height: 1;
        padding: 7px 10px;
        white-space: nowrap;
    }

    .badge-admin {
        background: #f8e5ee;
        color: #85385a;
    }

    .badge-usuario {
        background: #e6eee4;
        color: #3d6845;
    }

    .badge-estado::before {
        background: currentColor;
        border-radius: 50%;
        content: "";
        height: 7px;
        width: 7px;
    }

    .badge-estado.activo {
        background: #e6eee4;
        color: #3d6845;
    }

    .badge-estado.inactivo {
        background: #f8e8e3;
        color: #9b4638;
    }

    .sin-usuarios {
        background: rgba(255, 255, 255, .94);
        border: 1px solid #efd5df;
        border-radius: 9px;
        color: #69545f;
        margin: 0 auto 34px;
        max-width: 1420px;
        padding: 38px 20px;
        text-align: center;
    }

    .estadisticas {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin: 0 auto 24px;
        max-width: 1420px;
    }

    .stat-card {
        animation: stat-entrada .45s both;
        background: rgba(255, 255, 255, .94);
        border: 1px solid #efd5df;
        border-top: 3px solid #c34d78;
        border-radius: 8px;
        box-shadow: 0 6px 18px rgba(125, 57, 86, .07);
        min-width: 0;
        padding: 17px 18px 15px;
        text-align: left;
        text-decoration: none;
        transition: box-shadow .2s ease, transform .2s ease;
    }

    .stat-card:nth-child(2) {
        animation-delay: .06s;
        border-top-color: #4d8b67;
    }

    .stat-card:nth-child(3) {
        animation-delay: .12s;
        border-top-color: #dd795b;
    }

    .stat-card:nth-child(4) {
        animation-delay: .18s;
        border-top-color: #8b658f;
    }

    .stat-card:hover {
        box-shadow: 0 12px 25px rgba(125, 57, 86, .13);
        transform: translateY(-3px);
    }

    .stat-card.activa { outline: 2px solid #71354e; outline-offset: 2px; }
    .stat-card:focus-visible { outline: 3px solid #71354e; outline-offset: 3px; }
    .tabla-usuarios table { min-width: 820px; }
    .filtro-usuarios { color: #69545f; margin: -8px auto 16px; max-width: 1420px; }

    .stat-card h3 {
        color: #765f6a;
        font-size: 13px;
        font-weight: 600;
        margin: 0;
    }

    .stat-numero {
        color: #8f3d60;
        font: 700 30px Georgia, "Times New Roman", serif;
        margin: 8px 0 0;
    }

    @keyframes stat-entrada {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 768px) {
        .usuarios-container {
            padding: 28px 14px 40px;
        }

        .estadisticas {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .usuarios-container h2 {
            font-size: 26px;
        }

        .tabla-usuarios th, .tabla-usuarios td {
            padding: 13px 12px;
        }
    }

    @media (max-width: 420px) {
        .estadisticas { grid-template-columns: 1fr 1fr; }
        .stat-card { padding: 14px 12px; }
        .stat-card h3 { font-size: 12px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .stat-card { animation: none; transition: none; }
    }
</style>

<div class="usuarios-container">
    <h2>Usuarios Registrados</h2>

    <?php
    $estadisticasUsuarios = mysqli_query($conexion, "SELECT COUNT(*) AS total, SUM(rol <> 'admin' AND activo = 1) AS activos, SUM(rol <> 'admin') AS clientes, SUM(rol = 'admin') AS administradores FROM usuarios");
    $resumenUsuarios = $estadisticasUsuarios ? mysqli_fetch_assoc($estadisticasUsuarios) : [];
    $total_usuarios = (int) ($resumenUsuarios['total'] ?? 0);
    $usuarios_activos = (int) ($resumenUsuarios['activos'] ?? 0);
    $total_clientes = (int) ($resumenUsuarios['clientes'] ?? 0);
    $total_admins = (int) ($resumenUsuarios['administradores'] ?? 0);
    $filtroUsuarios = is_string($_GET['vista'] ?? null) ? $_GET['vista'] : 'todos';
    if (!in_array($filtroUsuarios, ['todos', 'activos', 'clientes', 'administradores'], true)) {
        $filtroUsuarios = 'todos';
    }
    $condicionesUsuarios = [
        'todos' => '',
        'activos' => ' WHERE activo = 1 AND rol <> \'admin\'',
        'clientes' => ' WHERE rol <> \'admin\'',
        'administradores' => ' WHERE rol = \'admin\'',
    ];
    $resultadoUsuarios = mysqli_query($conexion, 'SELECT id, nombre, correo, rol, activo, fecha_registro FROM usuarios' . $condicionesUsuarios[$filtroUsuarios] . ' ORDER BY (rol = \'admin\') ASC, id ASC');
    ?>

    <div class="estadisticas">
        <a class="stat-card <?= $filtroUsuarios === 'todos' ? 'activa' : '' ?>" href="usuarios.php?vista=todos" aria-current="<?= $filtroUsuarios === 'todos' ? 'page' : 'false' ?>">
            <h3>Total de Usuarios</h3>
            <div class="stat-numero"><?php echo $total_usuarios; ?></div>
        </a>
        <a class="stat-card <?= $filtroUsuarios === 'activos' ? 'activa' : '' ?>" href="usuarios.php?vista=activos" aria-current="<?= $filtroUsuarios === 'activos' ? 'page' : 'false' ?>">
            <h3>Usuarios Activos</h3>
            <div class="stat-numero"><?php echo $usuarios_activos; ?></div>
        </a>
        <a class="stat-card <?= $filtroUsuarios === 'clientes' ? 'activa' : '' ?>" href="usuarios.php?vista=clientes" aria-current="<?= $filtroUsuarios === 'clientes' ? 'page' : 'false' ?>">
            <h3>Clientes</h3>
            <div class="stat-numero"><?php echo $total_clientes; ?></div>
        </a>
        <a class="stat-card <?= $filtroUsuarios === 'administradores' ? 'activa' : '' ?>" href="usuarios.php?vista=administradores" aria-current="<?= $filtroUsuarios === 'administradores' ? 'page' : 'false' ?>">
            <h3>Administradores</h3>
            <div class="stat-numero"><?php echo $total_admins; ?></div>
        </a>
    </div>

    <p class="filtro-usuarios"><?php
        echo match ($filtroUsuarios) {
            'activos' => 'Mostrando clientes activos.',
            'clientes' => 'Mostrando todos los clientes, incluidos los inactivos.',
            'administradores' => 'Mostrando las cuentas administradoras.',
            default => 'Mostrando clientes y administradores.',
        };
    ?></p>

    <?php if ($resultadoUsuarios && mysqli_num_rows($resultadoUsuarios) > 0): ?>
        <div class="tabla-usuarios">
            <table>
                <thead>
                    <tr>
                        <th>N.º</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Fecha Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $numeroUsuario = 0; while ($row = mysqli_fetch_assoc($resultadoUsuarios)): $numeroUsuario++; ?>
                        <tr>
                            <td><strong><?= $numeroUsuario ?></strong></td>
                            <td><?= htmlspecialchars((string) $row['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $row['correo'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $row['rol'] === 'admin' ? '<span class="badge-rol badge-admin">Administrador</span>' : '<span class="badge-rol badge-usuario">Cliente</span>' ?></td>
                            <td><?= (int) $row['activo'] === 1 ? '<span class="badge-estado activo">Activo</span>' : '<span class="badge-estado inactivo">Inactivo</span>' ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $row['fecha_registro'])), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="sin-usuarios">
            <h3>No hay usuarios en esta vista</h3>
            <p>Prueba otra categoría de cuentas.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>