<?php include("Seguridad.php"); ?>
<?php include("../conexion.php"); ?>
<?php include("header_admin.php"); ?>

<h2>Gestión de Productos</h2>

<!-- FORMULARIO -->
<form method="POST">
    <input type="text" name="nombre" placeholder="Nombre" required>
    <input type="number" name="precio" placeholder="Precio" required>
    <input type="number" name="stock" placeholder="Stock" required>
    <button type="submit" name="guardar">Guardar</button>
</form>

<?php
if (isset($_POST['guardar'])) {
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];

    mysqli_query($conexion, "INSERT INTO productos (nombre, precio, stock) VALUES ('$nombre','$precio','$stock')");
    echo "Producto agregado";
}
?>

<!-- LISTA -->
<table border="1">
<tr>
    <th>ID</th>
    <th>Nombre</th>
    <th>Precio</th>
    <th>Stock</th>
</tr>

<?php
$resultado = mysqli_query($conexion, "SELECT * FROM productos");

while ($row = mysqli_fetch_assoc($resultado)) {
    echo "<tr>
        <td>{$row['id']}</td>
        <td>{$row['nombre']}</td>
        <td>{$row['precio']}</td>
        <td>{$row['stock']}</td>
    </tr>";
}
?>
</table>
</table>