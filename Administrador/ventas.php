<?php include("Seguridad.php"); ?>
<?php include("../conexion.php"); ?>
<?php $conexion = $GLOBALS['conexion']; ?>
<?php include("header-admin.php"); ?>

<style>
    .ventas-container {
        padding: 30px;
        background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
        min-height: calc(100vh - 100px);
    }

    .ventas-container h2 {
        color: #dc3545;
        font-size: 2em;
        margin-bottom: 30px;
        font-weight: bold;
    }

    .tabla-ventas {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }

    .tabla-ventas table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .tabla-ventas th {
        background: linear-gradient(135deg, #dc3545 0%, #e85b5b 100%);
        color: white;
        padding: 20px;
        text-align: left;
        font-weight: 600;
        border: none;
    }

    .tabla-ventas td {
        padding: 18px 20px;
        border-bottom: 1px solid #e9ecef;
        color: #555;
    }

    .tabla-ventas tr:hover {
        background: #f8f9fa;
    }

    .tabla-ventas tr:last-child td {
        border-bottom: none;
    }

    .badge-accion {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85em;
        font-weight: bold;
        background: #e85b5b;
        color: white;
    }

    .sin-ventas {
        text-align: center;
        padding: 50px;
        color: #999;
        background: white;
        border-radius: 15px;
    }

    .estadisticas-ventas {
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
        border-left: 5px solid #dc3545;
    }

    .stat-card h3 {
        margin-top: 0;
        color: #555;
        font-size: 0.95em;
    }

    .stat-numero {
        font-size: 2em;
        font-weight: 700;
        color: #dc3545;
        margin: 10px 0;
    }

    @media (max-width: 768px) {
        .ventas-container {
            padding: 15px;
        }
    }
</style>

<div class="ventas-container">
    <h2>💰 Registro de Ventas</h2>

    <?php
    $total_ventas = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM pedidos"))['total'];
    $ventas_pendientes = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM pedidos WHERE estado='pendiente'"))['total'];
    $ventas_completadas = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM pedidos WHERE estado='completado'"))['total'];
    
    $resultado_total = mysqli_query($conexion, "SELECT SUM(total) as monto FROM pedidos WHERE estado='completado'");
    $monto_total = mysqli_fetch_assoc($resultado_total)['monto'] ?? 0;
    ?>

    <div class="estadisticas-ventas">
        <div class="stat-card">
            <h3>Total de Pedidos</h3>
            <div class="stat-numero"><?php echo $total_ventas; ?></div>
        </div>
        <div class="stat-card" style="border-left-color: #ffc107;">
            <h3>Pendientes</h3>
            <div class="stat-numero" style="color: #ffc107;"><?php echo $ventas_pendientes; ?></div>
        </div>
        <div class="stat-card" style="border-left-color: #28a745;">
            <h3>Completados</h3>
            <div class="stat-numero" style="color: #28a745;"><?php echo $ventas_completadas; ?></div>
        </div>
        <div class="stat-card" style="border-left-color: #17a2b8;">
            <h3>Monto Total</h3>
            <div class="stat-numero" style="color: #17a2b8;">$<?php echo number_format($monto_total, 0, ',', '.'); ?></div>
        </div>
    </div>

    <?php
    $resultado = mysqli_query($conexion, "SELECT * FROM historial_actividades WHERE accion = 'PAGO' OR accion = 'VENTA' ORDER BY fecha_accion DESC");
    if (mysqli_num_rows($resultado) > 0):
    ?>
        <div class="tabla-ventas">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario ID</th>
                        <th>Acción</th>
                        <th>Descripción</th>
                        <th>IP</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($resultado)): ?>
                        <tr>
                            <td><strong>#<?= htmlspecialchars($row['id']) ?></strong></td>
                            <td><?= htmlspecialchars($row['usuario_id']) ?></td>
                            <td><span class="badge-accion"><?= htmlspecialchars($row['accion']) ?></span></td>
                            <td><?= htmlspecialchars($row['descripcion']) ?></td>
                            <td><code><?= htmlspecialchars($row['ip_address']) ?></code></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['fecha_accion'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php
    else:
    ?>
        <div class="sin-ventas">
            <h3>📭 No hay ventas registradas</h3>
            <p>Por el momento no hay registro de transacciones.</p>
        </div>
    <?php
    endif;
    ?>
</div>