<?php include("Seguridad.php"); ?>
<?php include("../conexion.php"); ?>
<?php include("header-admin.php"); ?>

<div style="padding: 25px;">
    <h2>Gestión de Productos</h2>

    <form method="POST" style="display:grid; gap:12px; max-width:360px; margin-bottom:20px;">
        <input type="text" name="nombre" placeholder="Nombre" required>
        <input type="number" name="precio" placeholder="Precio" required>
        <input type="number" name="stock" placeholder="Stock" required>
        <button type="submit" name="guardar" style="background:#ff66a3; color:white; padding:10px; border:none; border-radius:8px; cursor:pointer;">Guardar</button>
    </form>

    <?php
    if (isset($_POST['guardar'])) {
        $nombre = $conexion->real_escape_string($_POST['nombre']);
        $precio = (int) $_POST['precio'];
        $stock = (int) $_POST['stock'];

        if ($precio >= 0 && $stock >= 0) {
            mysqli_query($conexion, "INSERT INTO productos (nombre, precio, stock) VALUES ('$nombre','$precio','$stock')");
            echo '<div style="background:#d4ffd4; color:#2a682a; padding:12px; border-radius:8px; margin-bottom:20px;">Producto agregado correctamente</div>';
        } else {
            echo '<div style="background:#ffd4d4; color:#9a1f1f; padding:12px; border-radius:8px; margin-bottom:20px;">Precio y stock deben ser valores positivos</div>';
        }
    }
    ?>

    <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse:collapse; background:#fff;">
    <tr style="background:#f8f8f8;">
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
            <td>" . htmlspecialchars($row['nombre']) . "</td>
            <td>$" . number_format($row['precio'], 0, ',', '.') . "</td>
            <td>{$row['stock']}</td>
        </tr>";
    }
    ?>
    </table>
</div>