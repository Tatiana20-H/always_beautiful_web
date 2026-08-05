<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<header style="background:#111; color:white; padding:15px; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:10px;">
    <h2 style="margin:0; font-size:20px;">Panel Administrador</h2>

    <nav style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
        <a href="index.php">Inicio</a>
        <a href="productos.php">Productos</a>
        <a href="usuarios.php">Usuarios</a>
        <a href="ventas.php">Ventas</a>
        <a href="reportes.php">Reportes</a>
        <a href="logout.php">Cerrar sesión</a>
    </nav>

    <div style="color:#ff9ad4; font-weight:600;">👤 <?php echo htmlspecialchars($_SESSION['usuario'] ?? 'Admin'); ?></div>
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