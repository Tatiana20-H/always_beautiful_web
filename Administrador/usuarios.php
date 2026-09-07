<?php include("Seguridad.php"); ?>
<?php include("../conexion.php"); ?>
<?php $conexion = $GLOBALS['conexion']; ?>
<?php include("header-admin.php"); ?>

<style>
    .usuarios-container {
        padding: 30px;
        background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
        min-height: calc(100vh - 100px);
    }

    .usuarios-container h2 {
        color: #0056b3;
        font-size: 2em;
        margin-bottom: 30px;
        font-weight: bold;
    }

    .tabla-usuarios {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }

    .tabla-usuarios table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .tabla-usuarios th {
        background: linear-gradient(135deg, #0056b3 0%, #0066cc 100%);
        color: white;
        padding: 20px;
        text-align: left;
        font-weight: 600;
        border: none;
    }

    .tabla-usuarios td {
        padding: 18px 20px;
        border-bottom: 1px solid #e9ecef;
        color: #555;
    }

    .tabla-usuarios tr:hover {
        background: #f8f9fa;
    }

    .tabla-usuarios tr:last-child td {
        border-bottom: none;
    }

    .badge-rol {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85em;
        font-weight: bold;
        white-space: nowrap;
    }

    .badge-admin {
        background: #dc3545;
        color: white;
    }

    .badge-usuario {
        background: #28a745;
        color: white;
    }

    .sin-usuarios {
        text-align: center;
        padding: 50px;
        color: #999;
        background: white;
        border-radius: 15px;
    }

    .estadisticas {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        text-align: center;
        border-left: 5px solid #d63384;
    }

    .stat-card h3 {
        margin-top: 0;
        color: #555;
        font-size: 0.95em;
    }

    .stat-numero {
        font-size: 2em;
        font-weight: 700;
        color: #d63384;
        margin: 10px 0;
    }

    @media (max-width: 768px) {
        .usuarios-container {
            padding: 15px;
        }

        .tabla-usuarios table {
            font-size: 0.9em;
        }

        .tabla-usuarios th, .tabla-usuarios td {
            padding: 12px 10px;
        }
    }
</style>

<div class="usuarios-container">
    <h2>👥 Usuarios Registrados</h2>

    <?php
    $total_usuarios = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM usuarios"))['total'];
    $total_admins = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM usuarios WHERE rol='admin'"))['total'];
    $total_clientes = $total_usuarios - $total_admins;
    $usuarios_activos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM usuarios WHERE activo=TRUE"))['total'];
    ?>

    <div class="estadisticas">
        <div class="stat-card">
            <h3>Total de Usuarios</h3>
            <div class="stat-numero"><?php echo $total_usuarios; ?></div>
        </div>
        <div class="stat-card" style="border-left-color: #28a745;">
            <h3>Usuarios Activos</h3>
            <div class="stat-numero" style="color: #28a745;"><?php echo $usuarios_activos; ?></div>
        </div>
        <div class="stat-card" style="border-left-color: #007bff;">
            <h3>Clientes</h3>
            <div class="stat-numero" style="color: #007bff;"><?php echo $total_clientes; ?></div>
        </div>
        <div class="stat-card" style="border-left-color: #ffc107;">
            <h3>Administradores</h3>
            <div class="stat-numero" style="color: #ffc107;"><?php echo $total_admins; ?></div>
        </div>
    </div>

    <?php
    $resultado = mysqli_query($conexion, "SELECT * FROM usuarios ORDER BY fecha_registro DESC");
    if (mysqli_num_rows($resultado) > 0):
    ?>
        <div class="tabla-usuarios">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
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
                        $badge_rol = $row['rol'] === 'admin' ? '<span class="badge-rol badge-admin">🛡️ Administrador</span>' : '<span class="badge-rol badge-usuario">👤 Cliente</span>';
                        $estado = $row['activo'] ? '<span style="color: #28a745; font-weight: bold;">✅ Activo</span>' : '<span style="color: #dc3545; font-weight: bold;">❌ Inactivo</span>';
                        $fecha = date('d/m/Y', strtotime($row['fecha_registro']));
                        
                        echo "<tr>
                            <td><strong>#{$row['id']}</strong></td>
                            <td>{$row['nombre']}</td>
                            <td>{$row['correo']}</td>
                            <td>$badge_rol</td>
                            <td>$estado</td>
                            <td>$fecha</td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php
    else:
    ?>
        <div class="sin-usuarios">
            <h3>😔 No hay usuarios registrados</h3>
            <p>Por el momento no hay usuarios en el sistema.</p>
        </div>
    <?php
    endif;
    ?>
</div>

</body>
</html>