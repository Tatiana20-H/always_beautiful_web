<?php include("Seguridad.php"); ?>
<?php include("../conexion.php"); ?>
<?php $conexion = $GLOBALS['conexion']; ?>
<?php include("header-admin.php"); ?>

<style>
    .admin-container {
        padding: 30px;
        background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
        min-height: calc(100vh - 100px);
    }

    .admin-container h2 {
        color: #d63384;
        font-size: 2em;
        margin-bottom: 30px;
        font-weight: bold;
    }

    .tarjetas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .tarjeta {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border-left: 5px solid transparent;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .tarjeta::before {
        content: '';
        position: absolute;
        top: 0;
        right: -100%;
        width: 100%;
        height: 100%;
        background: rgba(214, 51, 132, 0.05);
        transition: right 0.3s ease;
        z-index: 0;
    }

    .tarjeta:hover::before {
        right: 0;
    }

    .tarjeta:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(214, 51, 132, 0.15);
    }

    .tarjeta-contenido {
        position: relative;
        z-index: 1;
    }

    .tarjeta-icono {
        font-size: 2.5em;
        margin-bottom: 10px;
    }

    .tarjeta h3 {
        margin: 0 0 10px 0;
        color: #333;
        font-size: 1em;
        font-weight: 600;
    }

    .tarjeta-numero {
        font-size: 2.5em;
        font-weight: 700;
        margin: 10px 0;
        color: #d63384;
    }

    .tarjeta-subtitulo {
        font-size: 0.9em;
        color: #999;
    }

    .tarjeta-usuarios {
        border-left-color: #007bff;
    }

    .tarjeta-usuarios .tarjeta-numero {
        color: #007bff;
    }

    .tarjeta-productos {
        border-left-color: #28a745;
    }

    .tarjeta-productos .tarjeta-numero {
        color: #28a745;
    }

    .tarjeta-mensajes {
        border-left-color: #ffc107;
    }

    .tarjeta-mensajes .tarjeta-numero {
        color: #ffc107;
    }

    .tarjeta-actividades {
        border-left-color: #17a2b8;
    }

    .tarjeta-actividades .tarjeta-numero {
        color: #17a2b8;
    }

    .tarjeta-ventas {
        border-left-color: #dc3545;
    }

    .tarjeta-ventas .tarjeta-numero {
        color: #dc3545;
    }

    .badge-alerta {
        display: inline-block;
        background: #dc3545;
        color: white;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8em;
        font-weight: bold;
        margin-top: 10px;
    }

    .seccion-resumen {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-top: 30px;
    }

    .seccion-resumen h3 {
        color: #d63384;
        margin-top: 0;
        border-bottom: 2px solid #d63384;
        padding-bottom: 15px;
    }

    .lista-actividades {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .lista-actividades li {
        padding: 15px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .lista-actividades li:last-child {
        border-bottom: none;
    }

    .lista-actividades li:hover {
        background: #f8f9fa;
    }

    @media (max-width: 768px) {
        .admin-container {
            padding: 15px;
        }

        .tarjetas-grid {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .admin-container h2 {
            font-size: 1.5em;
        }
    }
</style>

<div class="admin-container">
    <h2>📊 Bienvenido al Panel de Administración</h2>

    <?php
    $usuarios = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM usuarios"));
    $productos = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM productos"));
    $actividades = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM historial_actividades"));
    $mensajes_no_leidos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM mensajes_contacto WHERE estado='no_leido'"))['total'];
    $pedidos_pendientes = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM pedidos WHERE estado='pendiente'"))['total'];
    ?>

    <div class="tarjetas-grid">
        <div class="tarjeta tarjeta-usuarios">
            <div class="tarjeta-contenido">
                <div class="tarjeta-icono">👥</div>
                <h3>Usuarios</h3>
                <div class="tarjeta-numero"><?php echo $usuarios; ?></div>
                <p class="tarjeta-subtitulo">Usuarios registrados</p>
            </div>
        </div>

        <div class="tarjeta tarjeta-productos">
            <div class="tarjeta-contenido">
                <div class="tarjeta-icono">🛍️</div>
                <h3>Productos</h3>
                <div class="tarjeta-numero"><?php echo $productos; ?></div>
                <p class="tarjeta-subtitulo">Total de productos</p>
            </div>
        </div>

        <div class="tarjeta tarjeta-mensajes">
            <div class="tarjeta-contenido">
                <div class="tarjeta-icono">📧</div>
                <h3>Mensajes</h3>
                <div class="tarjeta-numero"><?php echo $mensajes_no_leidos; ?></div>
                <p class="tarjeta-subtitulo">Mensajes sin leer</p>
                <?php if ($mensajes_no_leidos > 0): ?>
                    <span class="badge-alerta">⚠️ Requiere atención</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="tarjeta tarjeta-ventas">
            <div class="tarjeta-contenido">
                <div class="tarjeta-icono">💰</div>
                <h3>Pedidos Pendientes</h3>
                <div class="tarjeta-numero"><?php echo $pedidos_pendientes; ?></div>
                <p class="tarjeta-subtitulo">Esperando procesamiento</p>
            </div>
        </div>

        <div class="tarjeta tarjeta-actividades">
            <div class="tarjeta-contenido">
                <div class="tarjeta-icono">📋</div>
                <h3>Actividades</h3>
                <div class="tarjeta-numero"><?php echo $actividades; ?></div>
                <p class="tarjeta-subtitulo">Registros de actividad</p>
            </div>
        </div>
    </div>

</div>

</body>
</html>