<?php include_once("Seguridad.php"); ?>
<?php include_once("../conexion.php"); ?>
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
    <h2>Usuarios registrados</h2>

    <?php
    $total_usuarios = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM usuarios"))['total'];
    $total_admins = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM usuarios WHERE rol='admin'"))['total'];
    $total_clientes = $total_usuarios - $total_admins;
    $usuarios_activos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM usuarios WHERE activo=TRUE"))['total'];
    $filtroUsuarios = $_GET['filtro'] ?? 'todos';
    $condiciones = [
        'activos' => 'WHERE activo = 1',
        'clientes' => "WHERE rol = 'usuario'",
        'administradores' => "WHERE rol = 'admin'",
        'todos' => ''
    ];
    if (!isset($condiciones[$filtroUsuarios])) $filtroUsuarios = 'todos';
    ?>

    <div class="estadisticas">
        <a class="stat-card" href="?filtro=todos">
            <h3>Total de Usuarios</h3>
            <div class="stat-numero"><?php echo $total_usuarios; ?></div>
        </a>
        <a class="stat-card" href="?filtro=activos" style="border-left-color: #28a745;">
            <h3>Usuarios Activos</h3>
            <div class="stat-numero" style="color: #28a745;"><?php echo $usuarios_activos; ?></div>
        </a>
        <a class="stat-card" href="?filtro=clientes" style="border-left-color: #007bff;">
            <h3>Clientes</h3>
            <div class="stat-numero" style="color: #007bff;"><?php echo $total_clientes; ?></div>
        </a>
        <a class="stat-card" href="?filtro=administradores" style="border-left-color: #ffc107;">
            <h3>Administradores</h3>
            <div class="stat-numero" style="color: #ffc107;"><?php echo $total_admins; ?></div>
        </a>
    </div>

    <?php
    $resultado = mysqli_query($conexion, 'SELECT * FROM usuarios ' . $condiciones[$filtroUsuarios] . ' ORDER BY id ASC');
    if (mysqli_num_rows($resultado) > 0):
    ?>
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
                    <?php
                    while ($row = mysqli_fetch_assoc($resultado)) {
                        $badge_rol = $row['rol'] === 'admin' ? '<span class="badge-rol badge-admin">Administrador</span>' : '<span class="badge-rol badge-usuario">Cliente</span>';
                        $estado = $row['activo'] ? '<span style="color: #28a745; font-weight: bold;">Activo</span>' : '<span style="color: #dc3545; font-weight: bold;">Inactivo</span>';
                        $fecha = date('d/m/Y', strtotime($row['fecha_registro']));
                        
                        echo "<tr>
                            <td><strong>#{$row['id']}</strong></td>
                            <td>" . htmlspecialchars($row['nombre']) . "</td>
                            <td>" . htmlspecialchars($row['correo']) . "</td>
                            <td>$badge_rol</td>
                            <td>$estado</td>
                            <td>$fecha</td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="sin-usuarios">
            <h3>No hay usuarios registrados</h3>
            <p>Por el momento no hay usuarios en el sistema.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>