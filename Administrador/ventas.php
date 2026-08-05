<?php include("Seguridad.php"); ?>
<?php include("../conexion.php"); ?>
<?php include("header-admin.php"); ?>

<div style="padding:25px;">
    <h2>Ventas</h2>

    <?php
    $resultado = mysqli_query($conexion, "SELECT * FROM historial_actividades WHERE accion = 'PAGO' OR accion = 'VENTA'");
    ?>

    <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse:collapse; background:#fff;">
        <tr style="background:#f8f8f8;">
            <th>ID</th>
            <th>Usuario ID</th>
            <th>Acción</th>
            <th>Descripción</th>
            <th>IP</th>
            <th>Fecha</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($resultado)): ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['usuario_id']) ?></td>
                <td><?= htmlspecialchars($row['accion']) ?></td>
                <td><?= htmlspecialchars($row['descripcion']) ?></td>
                <td><?= htmlspecialchars($row['ip_address']) ?></td>
                <td><?= htmlspecialchars($row['fecha_accion']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>