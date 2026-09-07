<?php include("Seguridad.php"); ?>
<?php include("../conexion.php"); ?>
<?php $conexion = $GLOBALS['conexion']; ?>
<?php include("header-admin.php"); ?>

<style>
    .productos-container {
        padding: 30px;
        background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
        min-height: calc(100vh - 100px);
    }

    .productos-container h2 {
        color: #28a745;
        font-size: 2em;
        margin-bottom: 30px;
        font-weight: bold;
    }

    .formulario-producto {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 30px;
        max-width: 500px;
    }

    .formulario-producto h3 {
        color: #28a745;
        margin-top: 0;
    }

    .formulario-producto input {
        width: 100%;
        padding: 12px;
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-family: Arial;
        box-sizing: border-box;
        transition: border 0.3s;
    }

    .formulario-producto input:focus {
        outline: none;
        border-color: #28a745;
        box-shadow: 0 0 5px rgba(40, 167, 69, 0.3);
    }

    .formulario-producto button {
        width: 100%;
        background: linear-gradient(135deg, #28a745 0%, #34a954 100%);
        color: white;
        padding: 12px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        font-size: 1em;
        transition: all 0.3s;
    }

    .formulario-producto button:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
    }

    .tabla-productos {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }

    .tabla-productos table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .tabla-productos th {
        background: linear-gradient(135deg, #28a745 0%, #34a954 100%);
        color: white;
        padding: 20px;
        text-align: left;
        font-weight: 600;
        border: none;
    }

    .tabla-productos td {
        padding: 18px 20px;
        border-bottom: 1px solid #e9ecef;
        color: #555;
    }

    .tabla-productos tr:hover {
        background: #f8f9fa;
    }

    .tabla-productos tr:last-child td {
        border-bottom: none;
    }

    .alerta-exito {
        background: #d4edda;
        color: #155724;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        border-left: 4px solid #28a745;
    }

    .alerta-error {
        background: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        border-left: 4px solid #dc3545;
    }

    @media (max-width: 768px) {
        .productos-container {
            padding: 15px;
        }
    }
</style>

<div class="productos-container">
    <h2>🛍️ Gestión de Productos</h2>

    <div class="formulario-producto">
        <h3>➕ Agregar Nuevo Producto</h3>
        <form method="POST">
            <input type="text" name="nombre" placeholder="Nombre del producto" required>
            <input type="number" name="precio" placeholder="Precio" min="0" required>
            <input type="number" name="stock" placeholder="Stock disponible" min="0" required>
            <button type="submit" name="guardar">Guardar Producto</button>
        </form>
    </div>

    <?php
    if (isset($_POST['guardar'])) {
        $nombre = $conexion->real_escape_string($_POST['nombre']);
        $precio = (int) $_POST['precio'];
        $stock = (int) $_POST['stock'];

        if ($precio >= 0 && $stock >= 0) {
            mysqli_query($conexion, "INSERT INTO productos (nombre, precio, stock) VALUES ('$nombre','$precio','$stock')");
            echo '<div class="alerta-exito">✅ Producto agregado correctamente</div>';
        } else {
            echo '<div class="alerta-error">❌ Precio y stock deben ser valores positivos</div>';
        }
    }
    ?>

    <div class="tabla-productos">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Fecha Creación</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $resultado = mysqli_query($conexion, "SELECT * FROM productos ORDER BY fecha_creacion DESC");
                if (mysqli_num_rows($resultado) > 0) {
                    while ($row = mysqli_fetch_assoc($resultado)) {
                        $stock_color = $row['stock'] == 0 ? 'color: #dc3545;' : '';
                        echo "<tr>
                            <td><strong>#{$row['id']}</strong></td>
                            <td>" . htmlspecialchars($row['nombre']) . "</td>
                            <td>\$" . number_format($row['precio'], 0, ',', '.') . "</td>
                            <td style=\"$stock_color\"><strong>{$row['stock']}</strong> unidades</td>
                            <td>" . date('d/m/Y', strtotime($row['fecha_creacion'])) . "</td>
                        </tr>";
                    }
                } else {
                    echo '<tr><td colspan="5" style="text-align: center; padding: 30px; color: #999;">📭 No hay productos registrados</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>