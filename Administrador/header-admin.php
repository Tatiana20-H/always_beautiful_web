<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<header style="background:#111; color:white; padding:15px; display:flex; justify-content:space-between; align-items:center;">
    <h2 style="margin:0;">Panel Administrador</h2>

    <nav>
        <a href="index.php">Inicio</a>
        <a href="productos.php">Productos</a>
        <a href="usuarios.php">Usuarios</a>
        <a href="ventas.php">Ventas</a>
        <a href="reportes.php">Reportes</a>
        <a href="logout.php">Cerrar sesión</a>
    </nav>

    <div>👤 <?php echo $_SESSION['usuario']; ?></div>
</header>

<style>
header nav a {
    color: white;
    margin: 0 10px;
    text-decoration: none;
    font-weight: bold;
}
header nav a:hover {
    color: #ff69b4;
}
</style>