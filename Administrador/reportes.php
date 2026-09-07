<?php include("Seguridad.php"); ?>
<?php include("../conexion.php"); ?>
<?php $conexion = $GLOBALS['conexion']; ?>
<?php include("header-admin.php"); ?>

<style>
    .reportes-container {
        padding: 30px;
        background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
        min-height: calc(100vh - 100px);
    }

    .reportes-container h2 {
        color: #17a2b8;
        font-size: 2em;
        margin-bottom: 30px;
        font-weight: bold;
    }

    .reportes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .reporte-card {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        border-left: 5px solid transparent;
        text-align: center;
        transition: all 0.3s;
    }

    .reporte-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.12);
    }

    .reporte-icono {
        font-size: 3em;
        margin-bottom: 15px;
    }

    .reporte-titulo {
        color: #555;
        font-size: 1em;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .reporte-numero {
        font-size: 2.5em;
        font-weight: 700;
        margin: 10px 0;
    }

    .reporte-card-usuarios {
        border-left-color: #007bff;
    }

    .reporte-card-usuarios .reporte-numero {
        color: #007bff;
    }

    .reporte-card-productos {
        border-left-color: #28a745;
    }

    .reporte-card-productos .reporte-numero {
        color: #28a745;
    }

    .reporte-card-actividades {
        border-left-color: #17a2b8;
    }

    .reporte-card-actividades .reporte-numero {
        color: #17a2b8;
    }

    .reporte-card-mensajes {
        border-left-color: #ffc107;
    }

    .reporte-card-mensajes .reporte-numero {
        color: #ffc107;
    }

    .resumen-general {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }

    .resumen-general h3 {
        color: #d63384;
        margin-top: 0;
        border-bottom: 2px solid #d63384;
        padding-bottom: 15px;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        padding: 15px 0;
        border-bottom: 1px solid #e9ecef;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #555;
    }

    .info-valor {
        color: #d63384;
        font-weight: bold;
    }

    @media (max-width: 768px) {
        .reportes-container {
            padding: 15px;
        }

        .reportes-grid {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }
    }
</style>

<div class="reportes-container">
    <h2>📊 Reportes Administrativos</h2>

    <?php
    $usuarios = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM usuarios"));
    $productos = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM productos"));
    $actividades = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM historial_actividades"));
    $mensajes = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM mensajes_contacto"));
    $pedidos = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM pedidos"));
    $usuarios_activos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM usuarios WHERE activo=TRUE"))['total'];
    ?>

    <div class="reportes-grid">
        <div class="reporte-card reporte-card-usuarios">
            <div class="reporte-icono">👥</div>
            <div class="reporte-titulo">Usuarios Totales</div>
            <div class="reporte-numero"><?php echo $usuarios; ?></div>
        </div>

        <div class="reporte-card reporte-card-productos">
            <div class="reporte-icono">🛍️</div>
            <div class="reporte-titulo">Productos</div>
            <div class="reporte-numero"><?php echo $productos; ?></div>
        </div>

        <div class="reporte-card reporte-card-mensajes">
            <div class="reporte-icono">📧</div>
            <div class="reporte-titulo">Mensajes de Contacto</div>
            <div class="reporte-numero"><?php echo $mensajes; ?></div>
        </div>

        <div class="reporte-card reporte-card-actividades">
            <div class="reporte-icono">💳</div>
            <div class="reporte-titulo">Pedidos Realizados</div>
            <div class="reporte-numero"><?php echo $pedidos; ?></div>
        </div>
    </div>

    <div class="resumen-general">
        <h3>📈 Resumen General del Sistema</h3>
        <div class="info-item">
            <span class="info-label">Total de Usuarios Registrados:</span>
            <span class="info-valor"><?php echo $usuarios; ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Usuarios Activos:</span>
            <span class="info-valor"><?php echo $usuarios_activos; ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Productos en Catálogo:</span>
            <span class="info-valor"><?php echo $productos; ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Pedidos Realizados:</span>
            <span class="info-valor"><?php echo $pedidos; ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Mensajes de Contacto:</span>
            <span class="info-valor"><?php echo $mensajes; ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Registros de Actividad:</span>
            <span class="info-valor"><?php echo $actividades; ?></span>
        </div>
    </div>
</div>