<?php
session_start();
include("conexion.php");
$conexion = $GLOBALS['conexion'];

if (!isset($_SESSION['usuario_id'])) {
    header("Location: inicio.php");
    exit();
}

$mensajeFoto = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil'])) {
    $archivo = $_FILES['foto_perfil'];
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $tipo = $archivo['tmp_name'] ? mime_content_type($archivo['tmp_name']) : '';

    if ($archivo['error'] !== UPLOAD_ERR_OK || !in_array($tipo, $tiposPermitidos, true)) {
        $mensajeFoto = 'Selecciona una imagen JPG, PNG, GIF o WEBP válida.';
    } elseif ($archivo['size'] > 4 * 1024 * 1024) {
        $mensajeFoto = 'La imagen no puede superar 4 MB.';
    } else {
        $directorio = __DIR__ . '/IMG/perfiles';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $nombreArchivo = 'usuario_' . (int) $_SESSION['usuario_id'] . '_' . time() . '.' . $extension;
        $rutaRelativa = 'IMG/perfiles/' . $nombreArchivo;
        if (move_uploaded_file($archivo['tmp_name'], $directorio . '/' . $nombreArchivo)) {
            $_SESSION['foto_perfil'] = $rutaRelativa;
            guardarDatosUsuario($conexion);
            $mensajeFoto = 'Foto de perfil actualizada.';
        } else {
            $mensajeFoto = 'No se pudo guardar la imagen.';
        }
    }
}

$nombreUsuario = $_SESSION['nombre'] ?? 'Usuario';
$totalCompras = count($_SESSION['historial'] ?? []);
$productosCarrito = 0;
foreach ($_SESSION['carrito'] ?? [] as $producto) {
    $productosCarrito += (int) ($producto['cantidad'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mi Perfil - Always Beautiful</title>
<link rel="stylesheet" href="styles.css">

<style>
.perfil-container{
    max-width: 850px;
    width: calc(100% - 40px);
    margin: 40px auto;
    padding: 30px;
    background: #f5f5f5;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    text-align: center;
}

.foto-perfil { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid #ebc9d9; }
.foto-predeterminada { display: inline-flex; align-items: center; justify-content: center; font-size: 48px; background: #f3c6d3; }
.foto-form { margin: 18px auto 28px; max-width: 420px; }
.foto-form input { box-sizing: border-box; }
.foto-form button { width: auto; background: #525252; }
.resumen-cuenta { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 25px 0; }
.resumen-cuenta div { background: #fff; border: 1px solid #ebc9d9; border-radius: 10px; padding: 14px; }
.historial-compra { border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 10px; text-align: left; }
@media (max-width: 600px) { .resumen-cuenta { grid-template-columns: 1fr; } }

.perfil-container h2{
    color: #888888;
}

.dato{
    margin: 15px 0;
    font-size: 18px;
    color: #d4a5b8;
}

.btn{
    background: #525252;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
</style>

</head>
<body>

<?php include("header.php"); ?>

<div class="perfil-container">

    <h2>Mi Perfil</h2>

    <?php if (!empty($_SESSION['foto_perfil'])): ?>
        <img src="<?= htmlspecialchars($_SESSION['foto_perfil']) ?>" alt="Foto de perfil" class="foto-perfil">
    <?php else: ?>
        <div class="foto-perfil foto-predeterminada">👤</div>
    <?php endif; ?>

    <form action="perfil.php" method="POST" enctype="multipart/form-data" class="foto-form">
        <input type="file" id="foto-perfil-input" name="foto_perfil" accept="image/jpeg,image/png,image/gif,image/webp" required onchange="this.form.submit()">
        <label for="foto-perfil-input" class="btn">Elegir foto</label>
    </form>
    <?php if ($mensajeFoto): ?><p><?= htmlspecialchars($mensajeFoto) ?></p><?php endif; ?>

    <div class="dato">
        <strong>Usuario:</strong> <?= htmlspecialchars($nombreUsuario); ?><br>
        <strong>Correo:</strong> <?= htmlspecialchars($_SESSION['correo'] ?? ''); ?><br>
        <?php if (!empty($_SESSION['fecha_nacimiento'])): ?>
            <strong>Fecha de nacimiento:</strong> <?= htmlspecialchars($_SESSION['fecha_nacimiento']); ?>
        <?php endif; ?>
    </div>

    <div class="resumen-cuenta">
        <div><strong>Compras realizadas</strong><br><?= $totalCompras ?></div>
        <div><strong>Productos en carrito</strong><br><?= $productosCarrito ?></div>
        <div><strong>Estado</strong><br>Cuenta activa</div>
    </div>

    <h3>Historial de compras</h3>

<?php if(!empty($_SESSION['historial'])): ?>

    <?php foreach($_SESSION['historial'] as $compra): ?>

        <div class="historial-compra">

            <strong>Fecha:</strong> <?= $compra['fecha'] ?> <br>
            <strong>Hora:</strong> <?= $compra['hora'] ?> <br>
            <strong>Total de productos:</strong> <?= $compra['cantidad_total'] ?? 0 ?> <br><br>

            <strong>Productos:</strong><br>

            <?php foreach($compra['productos'] as $nombre => $producto): ?>
                • <?= $nombre ?> — Cantidad: <?= $producto['cantidad'] ?> <br>
            <?php endforeach; ?>

            <br>
            <strong>Total pagado:</strong> $<?= number_format($compra['total'], 0, ',', '.') ?>

        </div>

    <?php endforeach; ?>

<?php else: ?>

    <p>No has realizado compras aún</p>

<?php endif; ?>

    <br>

    <a href="inicio.php">
        <button class="btn">Volver al inicio</button>
    </a>

</div>

</body>
</html>