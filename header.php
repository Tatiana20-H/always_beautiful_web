<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Always Beautiful</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<header class="header">

    <h1 class="titulo">ALWAYS BEAUTIFUL</h1>

    <?php
$totalCarrito = 0;
if(isset($_SESSION['carrito'])){
    foreach($_SESSION['carrito'] as $p){
        $totalCarrito += $p['cantidad'];
    }
}
?>

<a href="ver_carrito.php" class="carrito">
    🛒 <span class="contador-carrito"><?= $totalCarrito ?></span>
</a>
    <nav class="menu">

        <a href="inicio.php" class="item">Inicio</a>

        <div class="item dropdown">
    <span>Productos</span>

    <div class="submenu">
        <a href="maquillaje.php">Maquillaje</a>
        <a href="accesorios.php">Accesorios</a>
        <a href="cabello.php">Cuidado del cabello</a>
        <a href="piel.php">Cuidado de la piel</a>
    </div>
</div>

        <div class="item dropdown">
            Nosotros
            <div class="submenu">
                <a href="quienes.php">Quiénes somos</a>
                <a href="historia.php">Historia</a>
                <a href="mision.php">Misión</a>
                <a href="vision.php">Visión</a>
                <a href="valores.php">Valores</a>
            </div>
        </div>

        <div class="item dropdown">
            Blog
            <div class="submenu">
                <a href="articulos.php">Artículos</a>
            </div>
        </div>

        <div class="item dropdown">
            Contacto
            <div class="submenu">
                <a href="formulario.php">Formulario</a>
                <a href="ubicacion.php">Ubicación</a>
            </div>
        </div>

        <div class="item dropdown">
            Mi cuenta
            <div class="submenu">
                <a href="perfil.php">Perfil</a>
                <a href="logout.php">Cerrar sesión</a>
            </div>
        </div>

        <div class="item dropdown">
            Políticas
            <div class="submenu">
                <a href="terminos.php">Términos</a>
                <a href="privacidad.php">Privacidad</a>
                <a href="preguntas.php">Preguntas frecuentes</a>
            </div>
        </div>

    </nav>

</header>

<script>
let items = document.querySelectorAll(".dropdown");

items.forEach(item => {
    item.addEventListener("click", function (e) {

        e.stopPropagation();

        items.forEach(i => {
            if (i !== item) i.classList.remove("active");
        });

        item.classList.toggle("active");
    });
});

document.addEventListener("click", function () {
    items.forEach(i => i.classList.remove("active"));
});
</script>

