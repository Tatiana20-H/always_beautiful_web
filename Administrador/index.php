<?php include("Seguridad.php"); ?>
<?php include("../conexion.php"); ?>
<?php include("header_admin.php"); ?>

<h2>Bienvenido al panel de administración</h2>

<?php
// Conteos rápidos
$usuarios = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM usuarios"));
$productos = mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM productos"));
?>

<div style="display:flex; gap:20px; margin-top:20px;">
    <div style="background:#eee; padding:20px;">
        <h3>Usuarios</h3>
        <p><?php echo $usuarios; ?></p>
    </div>

    <div style="background:#eee; padding:20px;">
        <h3>Productos</h3>
        <p><?php echo $productos; ?></p>
    </div>
</div>