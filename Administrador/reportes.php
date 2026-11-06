<?php include_once("Seguridad.php"); ?>
<?php include_once("../conexion.php"); ?>
<?php $conexion = $GLOBALS['conexion']; ?>
<?php include("header-admin.php"); ?>

<style>
    .reportes-container {
        background: linear-gradient(145deg, #f7eee7 0%, #f5e8df 48%, #f8f2ed 100%);
        box-sizing: border-box;
        min-height: calc(100vh - 120px);
        padding: clamp(24px, 4vw, 46px) clamp(16px, 3vw, 42px) 56px;
    }

    .reportes-cabecera {
        align-items: flex-end;
        display: flex;
        justify-content: space-between;
        margin: 0 auto 28px;
        max-width: 1320px;
        gap: 20px;
    }

    .reportes-ceja { color: #9c624a; font-size: 11px; font-weight: 700; margin: 0 0 8px; }
    .reportes-cabecera h1 { color: #3a2722; font: 600 32px Georgia, "Times New Roman", serif; margin: 0; }
    .reportes-fecha { color: #77685f; font-size: 13px; white-space: nowrap; }

    .reportes-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 30px;
        margin-inline: auto;
        max-width: 1320px;
    }

    .reporte-card {
        animation: reporte-aparece .48s both;
        background: rgba(255, 253, 249, .88);
        border: 1px solid rgba(120, 83, 65, .14);
        border-top: 3px solid var(--reporte-acento);
        border-radius: 8px;
        box-shadow: 0 10px 24px rgba(58, 39, 34, .07);
        min-width: 0;
        padding: 21px 22px 18px;
        position: relative;
        transition: box-shadow .2s ease, transform .2s ease;
    }

    .reporte-card:hover {
        box-shadow: 0 16px 30px rgba(58, 39, 34, .13);
        transform: translateY(-3px);
    }

    .reporte-card:nth-child(2) { animation-delay: .06s; }
    .reporte-card:nth-child(3) { animation-delay: .12s; }
    .reporte-card:nth-child(4) { animation-delay: .18s; }

    .reporte-titulo {
        color: #6a5a51;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 12px;
    }

    .reporte-numero {
        color: var(--reporte-acento);
        font: 700 34px Georgia, "Times New Roman", serif;
        line-height: 1;
        margin: 0;
    }

    .reporte-card-usuarios { --reporte-acento: #9a4f69; }
    .reporte-card-productos { --reporte-acento: #477a59; }
    .reporte-card-mensajes { --reporte-acento: #b27628; }
    .reporte-card-actividades { --reporte-acento: #397c7a; }
    .reporte-meta { color: #83736a; font-size: 11px; margin-top: 10px; }

    .resumen-general {
        background: rgba(255, 253, 249, .9);
        border: 1px solid rgba(120, 83, 65, .14);
        border-radius: 8px;
        box-shadow: 0 12px 30px rgba(58, 39, 34, .08);
        margin: 0 auto;
        max-width: 1320px;
        padding: clamp(20px, 3vw, 30px);
    }

    .resumen-cabecera { align-items: end; border-bottom: 1px solid #e8ded5; display: flex; justify-content: space-between; gap: 12px; margin-bottom: 18px; padding-bottom: 16px; }
    .resumen-general h3 {
        color: #3a2722;
        font: 600 21px Georgia, "Times New Roman", serif;
        margin: 0;
    }
    .resumen-cabecera small { color: #88766c; font-size: 11px; }

    .resumen-metricas { display: grid; gap: 12px; grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .resumen-metrica { background: #fffaf5; border: 1px solid #ece1d8; border-left: 3px solid var(--metrica-acento, #9a4f69); border-radius: 6px; min-width: 0; padding: 15px 16px; }
    .resumen-metrica:nth-child(2) { --metrica-acento: #477a59; }
    .resumen-metrica:nth-child(3) { --metrica-acento: #b27628; }
    .resumen-metrica:nth-child(4) { --metrica-acento: #397c7a; }
    .resumen-metrica:nth-child(5) { --metrica-acento: #806b9a; }
    .metrica-titulo { color: #66564d; font-size: 12px; font-weight: 700; margin: 0 0 12px; }
    .metrica-principal { color: var(--metrica-acento, #9a4f69); font: 700 25px Georgia, "Times New Roman", serif; line-height: 1.15; margin: 0; overflow-wrap: anywhere; }
    .metrica-detalle { align-items: center; color: #786a61; display: flex; flex-wrap: wrap; font-size: 11px; gap: 7px; margin-top: 8px; }
    .metrica-detalle strong { color: #45372f; font-size: 12px; }
    .metrica-punto { background: var(--metrica-acento, #9a4f69); border-radius: 50%; height: 6px; width: 6px; }
    .resumen-metrica-ingresos { background: #edf4ed; border-color: #d7e5d7; border-left-color: #477a59; grid-column: span 2; }
    .resumen-metrica-ingresos .metrica-principal { color: #326b46; }
    .resumen-metrica-actividad { background: #f0edf4; border-color: #e2dce9; border-left-color: #806b9a; }
    .resumen-metrica-actividad .metrica-principal { color: #715c86; }
    @keyframes reporte-aparece { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    @media (max-width: 900px) {
        .reportes-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 600px) {
        .reportes-cabecera { align-items: flex-start; flex-direction: column; }
        .reportes-cabecera h1 { font-size: 27px; }
        .reportes-grid { gap: 10px; }
        .reporte-card { padding: 16px; }
        .reporte-numero { font-size: 29px; }
        .resumen-cabecera { align-items: flex-start; flex-direction: column; }
        .resumen-metricas { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .resumen-metrica-ingresos { grid-column: span 2; }
    }
    @media (prefers-reduced-motion: reduce) {
        .reporte-card { animation: none; transition: none; }
    }
</style>

<div class="reportes-container">
    <h2>Reportes administrativos</h2>

    <?php
    $usuarios = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM usuarios"));
    $productos = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM productos"));
    $actividades = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM historial_actividades"));
    $mensajes = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM mensajes_contacto"));
    $pedidos = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM pedidos"));
    $usuarios_activos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM usuarios WHERE activo=TRUE"))['total'];
    $pedidos_completados = (int) (mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS total FROM pedidos WHERE estado = 'completado'"))['total'] ?? 0);
    $pedidos_pendientes = (int) (mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS total FROM pedidos WHERE estado IN ('pendiente', 'procesando_pago')"))['total'] ?? 0);
    $monto_confirmado = (int) (mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COALESCE(SUM(total), 0) AS total FROM pedidos WHERE estado = 'completado'"))['total'] ?? 0);
    $productos_activos = (int) (mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS total FROM productos WHERE activo = 1"))['total'] ?? 0);
    $mensajes_pendientes = (int) (mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS total FROM mensajes_contacto WHERE estado = 'no_leido'"))['total'] ?? 0);
    ?>

    <div class="reportes-grid">
        <div class="reporte-card reporte-card-usuarios">
            <div class="reporte-icono" aria-hidden="true">U</div>
            <div class="reporte-titulo">Usuarios Totales</div>
            <div class="reporte-numero"><?php echo $usuarios; ?></div>
            <div class="reporte-meta"><?= $usuarios_activos ?> activos</div>
        </div>

        <div class="reporte-card reporte-card-productos">
            <div class="reporte-icono" aria-hidden="true">P</div>
            <div class="reporte-titulo">Productos</div>
            <div class="reporte-numero"><?php echo $productos; ?></div>
            <div class="reporte-meta"><?= $productos_activos ?> activos en catálogo</div>
        </div>

        <div class="reporte-card reporte-card-mensajes">
            <div class="reporte-icono" aria-hidden="true">M</div>
            <div class="reporte-titulo">Mensajes de Contacto</div>
            <div class="reporte-numero"><?php echo $mensajes; ?></div>
            <div class="reporte-meta"><?= $mensajes_pendientes ?> sin leer</div>
        </div>

        <div class="reporte-card reporte-card-actividades">
            <div class="reporte-icono" aria-hidden="true">V</div>
            <div class="reporte-titulo">Pedidos Realizados</div>
            <div class="reporte-numero"><?php echo $pedidos; ?></div>
            <div class="reporte-meta"><?= $pedidos_completados ?> pagados · <?= $pedidos_pendientes ?> pendientes</div>
        </div>
    </div>

    <div class="resumen-general">
        <h3>Resumen general del sistema</h3>
        <div class="info-item">
            <span class="info-label">Total de Usuarios Registrados:</span>
            <span class="info-valor"><?php echo $usuarios; ?></span>
        </div>
    </section>
</div>