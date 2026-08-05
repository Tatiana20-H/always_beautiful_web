<?php include("Seguridad.php"); ?>
<?php include("../conexion.php"); ?>
<?php include("header-admin.php"); ?>

<div style="padding:25px;">
    <h2>Bienvenido al panel de administración</h2>

    <?php
    $usuarios = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM usuarios"));
    $productos = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM productos"));
    $actividades = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM historial_actividades"));
    ?>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-top:25px;">
        <div style="background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 18px rgba(0,0,0,0.08);">
            <h3 style="margin-top:0;">Usuarios</h3>
            <p style="font-size:32px; font-weight:700; margin:0;"><?php echo $usuarios; ?></p>
        </div>
        <div style="background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 18px rgba(0,0,0,0.08);">
            <h3 style="margin-top:0;">Productos</h3>
            <p style="font-size:32px; font-weight:700; margin:0;"><?php echo $productos; ?></p>
        </div>
        <div style="background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 18px rgba(0,0,0,0.08);">
            <h3 style="margin-top:0;">Actividades</h3>
            <p style="font-size:32px; font-weight:700; margin:0;"><?php echo $actividades; ?></p>
        </div>
    </div>
</div>