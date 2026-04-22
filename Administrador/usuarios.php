<?php include("Seguridad.php"); ?>
<?php include("../conexion.php"); ?>
<?php include("header_admin.php"); ?>

<h2>Usuarios Registrados</h2>

<table border="1">
<tr>
    <th>ID</th>
    <th>Nombre</th>
    <th>Correo</th>
    <th>Rol</th>
</tr>

<?php
$resultado = mysqli_query($conexion, "SELECT * FROM usuarios");

while ($row = mysqli_fetch_assoc($resultado)) {
    echo "<tr>
        <td>{$row['id']}</td>
        <td>{$row['nombre']}</td>
        <td>{$row['correo']}</td>
        <td>{$row['rol']}</td>
    </tr>";
}
?>
</table>