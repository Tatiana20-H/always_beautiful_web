<?php
session_start();

/* DATOS DE PRUEBA */
if(!isset($_SESSION['usuario'])){
    $_SESSION['usuario'] = "Invitada";
}

/* CONTAR COMPRAS AUTOMÁTICO */
$totalCompras = isset($_SESSION['historial']) ? count($_SESSION['historial']) : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mi Perfil - Always Beautiful</title>
<link rel="stylesheet" href="styles.css">

<style>
.perfil-container{
    width: 60%;
    margin: 40px auto;
    padding: 30px;
    background: #f5f5f5;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    text-align: center;
}

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

    <div class="dato">
        <strong>Usuario:</strong> <?= $_SESSION['usuario']; ?>
    </div>

    <div class="dato">
        <strong>Compras realizadas:</strong> <?= $totalCompras; ?>
    </div>

    <h3>Historial de compras</h3>

<?php if(!empty($_SESSION['historial'])): ?>

    <?php foreach($_SESSION['historial'] as $compra): ?>

        <div style="border:1px solid #ddd; padding:15px; margin:15px 0; border-radius:10px; text-align:left;">

            <strong>Fecha:</strong> <?= $compra['fecha'] ?> <br>
            <strong>Hora:</strong> <?= $compra['hora'] ?> <br>
            <strong>Total de productos:</strong> <?= $compra['cantidad_total'] ?? 0 ?> <br><br>

            <strong>Productos:</strong><br>

            <?php foreach($compra['productos'] as $nombre => $producto): ?>
                • <?= $nombre ?> — Cantidad: <?= $producto['cantidad'] ?> <br>
            <?php endforeach; ?>

            <br>
            <strong>Total pagado:</strong> $<?= $compra['total'] ?>

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