<?php include("Seguridad.php"); ?>
<?php include("../conexion.php"); ?>
<?php include("header-admin.php"); ?>

<div style="padding:25px;">
    <h2>Reportes administrativos</h2>

<div style="padding:25px;">
    <h2>Reportes administrativos</h2>

    <?php
    $usuarios = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM usuarios"));
    $productos = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM productos"));
    $actividades = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM historial_actividades"));
    ?>

    <div style="display:flex; gap:20px; flex-wrap:wrap; margin-top:20px;">
        <div style="background:#fff; padding:20px; border-radius:10px; min-width:200px;">Usuarios<br><strong><?php echo $usuarios; ?></strong></div>
        <div style="background:#fff; padding:20px; border-radius:10px; min-width:200px;">Productos<br><strong><?php echo $productos; ?></strong></div>
        <div style="background:#fff; padding:20px; border-radius:10px; min-width:200px;">Actividades<br><strong><?php echo $actividades; ?></strong></div>
    </div>
</div>